<?php

/**
 * Crossref Import Functionality
 * Based on teachPress Crossref import system
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class PM_Crossref_Import
{

    /**
     * Import publications from Crossref using DOI
     * 
     * @param string $input DOI string (single or multiple separated by spaces)
     * @return array Results of import operation
     */
    public static function import_from_doi($input)
    {
        // Set time limit
        set_time_limit(300);

        // Split DOIs by whitespace
        $dois = preg_split('/\s+/', trim($input));

        if (empty($dois)) {
            return array(
                'success' => false,
                'message' => __('No DOIs provided', 'publications-manager')
            );
        }

        $results = array(
            'success' => true,
            'imported' => array(),
            'failed' => array(),
            'total' => count($dois)
        );

        $delay = null;
        $now = null;

        foreach ($dois as $doi) {
            if (empty($doi)) {
                continue;
            }

            // Honor rate limiting (50 requests per second default)
            if (isset($now)) {
                if (! isset($delay)) {
                    $delay = 1 / 50; // 50 requests per second
                }
                time_sleep_until($now + $delay);
            }
            $now = microtime(true);

            // Query Crossref API
            $response = wp_remote_get(
                'https://api.crossref.org/v1/works/' . urlencode($doi),
                array(
                    'timeout' => 30,
                    'headers' => array(
                        'User-Agent' => 'Publications-Manager-WordPress-Plugin/1.0 (mailto:admin@example.com)'
                    )
                )
            );

            // Check for errors
            if (is_wp_error($response)) {
                $results['failed'][] = array(
                    'doi' => $doi,
                    'error' => $response->get_error_message()
                );
                continue;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if (intdiv($response_code, 100) !== 2) {
                $results['failed'][] = array(
                    'doi' => $doi,
                    'error' => sprintf(__('HTTP Error: %d', 'publications-manager'), $response_code)
                );
                continue;
            }

            // Parse JSON response
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (! $data || $data->status !== 'ok') {
                $results['failed'][] = array(
                    'doi' => $doi,
                    'error' => __('Invalid JSON response from Crossref', 'publications-manager')
                );
                continue;
            }

            // Extract publication data
            $work = $data->message;
            $publication_data = self::parse_crossref_data($work);

            if ($publication_data) {
                // Check if publication with this DOI already exists
                $existing_post_id = self::find_publication_by_doi($publication_data['doi']);

                if ($existing_post_id) {
                    // Update existing publication
                    $post_id = self::update_publication($existing_post_id, $publication_data);
                    $action = 'updated';
                } else {
                    // Create new publication post
                    $post_id = self::create_publication($publication_data);
                    $action = 'created';
                }

                if ($post_id) {
                    // Process author relationships immediately after creation/update
                    if (function_exists('pm_process_author_relationships')) {
                        pm_process_author_relationships($post_id);
                    }

                    $results['imported'][] = array(
                        'doi' => $doi,
                        'post_id' => $post_id,
                        'title' => $publication_data['title'],
                        'action' => $action
                    );
                } else {
                    $results['failed'][] = array(
                        'doi' => $doi,
                        'error' => __('Failed to create/update publication post', 'publications-manager')
                    );
                }
            } else {
                $results['failed'][] = array(
                    'doi' => $doi,
                    'error' => __('Failed to parse Crossref data', 'publications-manager')
                );
            }
        }

        if (! empty($results['failed'])) {
            $results['success'] = count($results['imported']) > 0;
        }

        return $results;
    }

    /**
     * Parse Crossref API response data
     * 
     * @param object $work Crossref work data
     * @return array|false Publication data array or false on failure
     */
    private static function parse_crossref_data($work)
    {
        if (! $work) {
            return false;
        }

        $data = array();

        // Title
        if (isset($work->title) && is_array($work->title) && count($work->title) > 0) {
            $data['title'] = (string) $work->title[0];
        } else {
            return false; // Title is required
        }

        // DOI
        $data['doi'] = isset($work->DOI) ? (string) $work->DOI : '';

        // Type mapping from Crossref to our types
        $data['type'] = self::map_crossref_type(isset($work->type) ? $work->type : 'misc');

        // Date
        if (isset($work->published) && isset($work->published->{'date-parts'})) {
            $data['date'] = self::parse_date_parts($work->published->{'date-parts'});
        } elseif (isset($work->created) && isset($work->created->{'date-parts'})) {
            $data['date'] = self::parse_date_parts($work->created->{'date-parts'});
        } else {
            $data['date'] = current_time('Y-m-d');
        }

        // Authors - Store as array of individual authors
        $data['author'] = array();
        if (isset($work->author) && is_array($work->author)) {
            foreach ($work->author as $author) {
                $name_parts = array();
                // Given name first
                if (isset($author->given)) {
                    $name_parts[] = $author->given;
                }
                // Family name last
                if (isset($author->family)) {
                    $name_parts[] = $author->family;
                }
                if (! empty($name_parts)) {
                    $full_name = implode(' ', $name_parts);
                    $data['author'][] = $full_name;
                    // Debug logging
                    error_log('PM Crossref Import: Adding author: ' . $full_name);
                }
            }
        }

        // Debug logging
        error_log('PM Crossref Import: Total authors found: ' . count($data['author']));

        // Editors - Format as "GivenName FamilyName" separated by commas
        $data['editor'] = '';
        if (isset($work->editor) && is_array($work->editor)) {
            $editors = array();
            foreach ($work->editor as $editor) {
                $name_parts = array();
                // Given name first
                if (isset($editor->given)) {
                    $name_parts[] = $editor->given;
                }
                // Family name last
                if (isset($editor->family)) {
                    $name_parts[] = $editor->family;
                }
                if (! empty($name_parts)) {
                    $editors[] = implode(' ', $name_parts);
                }
            }
            // Join with commas
            $data['editor'] = implode(', ', $editors);
        }

        // Generate BibTeX key
        if (! empty($data['author']) && is_array($data['author'])) {
            // Authors are now stored as array
            $first_author = $data['author'][0];
            $author_parts = explode(' ', $first_author);
            // Last part is the family name
            $last_name = isset($author_parts[count($author_parts) - 1]) ? $author_parts[count($author_parts) - 1] : 'Unknown';
        } else {
            $last_name = 'Unknown';
        }

        $year = substr($data['date'], 0, 4);
        $data['bibtex'] = self::generate_unique_bibtex_key($last_name . $year);

        // Volume
        $data['volume'] = isset($work->volume) ? (string) $work->volume : '';

        // Issue/Number
        $data['issue'] = isset($work->issue) ? (string) $work->issue : '';
        $data['number'] = $data['issue'];

        // Pages
        $data['pages'] = isset($work->page) ? (string) $work->page : '';

        // Publisher
        $data['publisher'] = isset($work->publisher) ? (string) $work->publisher : '';

        // Journal/Container title
        if (isset($work->{'container-title'}) && is_array($work->{'container-title'}) && count($work->{'container-title'}) > 0) {
            $container = (string) $work->{'container-title'}[0];
            if ($data['type'] === 'article') {
                $data['journal'] = $container;
            } else {
                $data['booktitle'] = $container;
            }
        }

        // ISBN/ISSN
        $data['isbn'] = '';
        if (isset($work->ISBN) && is_array($work->ISBN)) {
            foreach ($work->ISBN as $isbn_obj) {
                if (isset($isbn_obj)) {
                    $data['isbn'] = (string) $isbn_obj;
                    break;
                }
            }
        }
        if (empty($data['isbn']) && isset($work->ISSN) && is_array($work->ISSN) && count($work->ISSN) > 0) {
            $data['isbn'] = (string) $work->ISSN[0];
        }

        // Abstract
        $data['abstract'] = isset($work->abstract) ? wp_strip_all_tags((string) $work->abstract) : '';

        // URL
        if (isset($work->URL)) {
            $data['url'] = (string) $work->URL;
        } elseif (! empty($data['doi'])) {
            $data['url'] = 'https://doi.org/' . $data['doi'];
        }

        // Edition
        $data['edition'] = isset($work->edition) ? (string) $work->edition : '';

        return $data;
    }

    /**
     * Parse date parts from Crossref response
     * 
     * @param array $date_parts Nested array of date parts
     * @return string Date in Y-m-d format
     */
    private static function parse_date_parts($date_parts)
    {
        if (! is_array($date_parts) || empty($date_parts)) {
            return current_time('Y-m-d');
        }

        $parts = $date_parts[0];

        switch (count($parts)) {
            case 1:
                return sprintf('%d-01-01', (int) $parts[0]);
            case 2:
                return sprintf('%d-%02d-01', (int) $parts[0], (int) $parts[1]);
            case 3:
                return sprintf('%d-%02d-%02d', (int) $parts[0], (int) $parts[1], (int) $parts[2]);
            default:
                return current_time('Y-m-d');
        }
    }

    /**
     * Map Crossref publication type to our types
     * 
     * @param string $crossref_type Crossref type
     * @return string Our publication type
     */
    private static function map_crossref_type($crossref_type)
    {
        $type_map = array(
            'journal-article'       => 'article',
            'book'                  => 'book',
            'book-chapter'          => 'inbook',
            'book-section'          => 'incollection',
            'proceedings-article'   => 'inproceedings',
            'proceedings'           => 'proceedings',
            'dissertation'          => 'phdthesis',
            'report'                => 'techreport',
            'dataset'               => 'misc',
            'standard'              => 'techreport',
            'monograph'             => 'book',
            'reference-entry'       => 'incollection',
            'posted-content'        => 'misc',
        );

        return isset($type_map[$crossref_type]) ? $type_map[$crossref_type] : 'misc';
    }

    /**
     * Generate unique BibTeX key
     * 
     * @param string $base Base key
     * @return string Unique BibTeX key
     */
    private static function generate_unique_bibtex_key($base)
    {
        // Sanitize base key
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);

        // Check if key exists
        $key = $base;
        $counter = 1;

        while (self::bibtex_key_exists($key)) {
            $key = $base . chr(96 + $counter); // a, b, c, etc.
            $counter++;

            if ($counter > 26) {
                $key = $base . rand(100, 999);
                break;
            }
        }

        return $key;
    }

    /**
     * Check if BibTeX key exists
     * 
     * @param string $key BibTeX key
     * @return bool
     */
    private static function bibtex_key_exists($key)
    {
        $args = array(
            'post_type'      => 'publication',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => 'pm_bibtex_key',
                    'value'   => $key,
                    'compare' => '='
                )
            )
        );

        $query = new WP_Query($args);
        return $query->have_posts();
    }

    /**
     * Find publication by DOI
     * 
     * @param string $doi DOI to search for
     * @return int|false Post ID if found, false otherwise
     */
    private static function find_publication_by_doi($doi)
    {
        if (empty($doi)) {
            return false;
        }

        // Normalize DOI: remove https://doi.org/ prefix and trim whitespace
        $normalized_doi = trim($doi);
        $normalized_doi = preg_replace('#^https?://doi\.org/#i', '', $normalized_doi);
        $normalized_doi = strtolower($normalized_doi);

        // Get all publications with any DOI
        $args = array(
            'post_type'      => 'publication',
            'post_status'    => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'pm_doi',
                    'compare' => 'EXISTS'
                )
            ),
            'fields' => 'ids'
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            // Check each publication's DOI after normalization
            foreach ($query->posts as $post_id) {
                $stored_doi = get_post_meta($post_id, 'pm_doi', true);
                if (!empty($stored_doi)) {
                    // Normalize stored DOI
                    $stored_normalized = trim($stored_doi);
                    $stored_normalized = preg_replace('#^https?://doi\.org/#i', '', $stored_normalized);
                    $stored_normalized = strtolower($stored_normalized);

                    // Compare normalized DOIs
                    if ($stored_normalized === $normalized_doi) {
                        return $post_id;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Create publication post from data
     * 
     * @param array $data Publication data
     * @return int|false Post ID on success, false on failure
     */
    private static function create_publication($data)
    {
        if (empty($data['title'])) {
            return false;
        }

        // Create post
        $post_data = array(
            'post_title'   => $data['title'],
            'post_type'    => 'publication',
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id) || ! $post_id) {
            return false;
        }

        // Save meta data
        $meta_mapping = array(
            'type'        => 'pm_type',
            'bibtex'      => 'pm_bibtex',
            'date'        => 'pm_date',
            'editor'      => 'pm_editor',
            'doi'         => 'pm_doi',
            'url'         => 'pm_url',
            'volume'      => 'pm_volume',
            'number'      => 'pm_number',
            'issue'       => 'pm_issue',
            'pages'       => 'pm_pages',
            'publisher'   => 'pm_publisher',
            'journal'     => 'pm_journal',
            'booktitle'   => 'pm_booktitle',
            'isbn'        => 'pm_isbn',
            'abstract'    => 'pm_abstract',
            'edition'     => 'pm_edition',
        );

        foreach ($meta_mapping as $key => $meta_key) {
            if (isset($data[$key]) && ! empty($data[$key])) {
                update_post_meta($post_id, $meta_key, $data[$key]);
            }
        }

        // Handle authors as taxonomy terms
        error_log('[PM Import CREATE] Post ID: ' . $post_id);
        error_log('[PM Import CREATE] Author data exists: ' . (isset($data['author']) ? 'YES' : 'NO'));
        error_log('[PM Import CREATE] Author is array: ' . (isset($data['author']) && is_array($data['author']) ? 'YES' : 'NO'));
        error_log('[PM Import CREATE] Author count: ' . (isset($data['author']) && is_array($data['author']) ? count($data['author']) : '0'));

        if (isset($data['author']) && is_array($data['author']) && !empty($data['author'])) {
            error_log('[PM Import CREATE] Authors: ' . print_r($data['author'], true));
            $author_term_ids = array();

            foreach ($data['author'] as $author_name) {
                error_log('[PM Import CREATE] Processing author: ' . $author_name);
                if (!empty($author_name) && class_exists('PM_Author_Taxonomy')) {
                    $term_id = PM_Author_Taxonomy::get_or_create_author_term($author_name);
                    error_log('[PM Import CREATE] Term ID returned: ' . $term_id);
                    if ($term_id && !is_wp_error($term_id)) {
                        $author_term_ids[] = $term_id;
                    }
                }
            }

            error_log('[PM Import CREATE] Total term IDs: ' . count($author_term_ids));
            if (!empty($author_term_ids)) {
                $result = wp_set_object_terms($post_id, $author_term_ids, 'pm_author', false);
                error_log('[PM Import CREATE] wp_set_object_terms result: ' . print_r($result, true));
            }
        }

        // Extract and save year from date
        if (isset($data['date']) && !empty($data['date'])) {
            $year = substr($data['date'], 0, 4);
            if (!empty($year) && is_numeric($year)) {
                update_post_meta($post_id, 'pm_year', $year);
            }
        }

        // Set default status
        update_post_meta($post_id, 'pm_status', 'published');

        // Mark as imported
        update_post_meta($post_id, 'pm_import_id', current_time('timestamp'));

        return $post_id;
    }

    /**
     * Update existing publication post with new data
     * 
     * @param int $post_id Existing post ID
     * @param array $data Publication data
     * @return int|false Post ID on success, false on failure
     */
    private static function update_publication($post_id, $data)
    {
        if (empty($data['title']) || !$post_id) {
            return false;
        }

        // Update post title if different
        $current_title = get_the_title($post_id);
        if ($current_title !== $data['title']) {
            wp_update_post(array(
                'ID'         => $post_id,
                'post_title' => $data['title']
            ));
        }

        // Update meta data
        $meta_mapping = array(
            'type'        => 'pm_type',
            'bibtex'      => 'pm_bibtex',
            'date'        => 'pm_date',
            'editor'      => 'pm_editor',
            'doi'         => 'pm_doi',
            'url'         => 'pm_url',
            'volume'      => 'pm_volume',
            'number'      => 'pm_number',
            'issue'       => 'pm_issue',
            'pages'       => 'pm_pages',
            'publisher'   => 'pm_publisher',
            'journal'     => 'pm_journal',
            'booktitle'   => 'pm_booktitle',
            'isbn'        => 'pm_isbn',
            'abstract'    => 'pm_abstract',
            'edition'     => 'pm_edition',
        );

        foreach ($meta_mapping as $key => $meta_key) {
            if (isset($data[$key]) && !empty($data[$key])) {
                update_post_meta($post_id, $meta_key, $data[$key]);
            }
        }

        // Handle authors as taxonomy terms
        error_log('[PM Import UPDATE] Post ID: ' . $post_id);
        error_log('[PM Import UPDATE] Author data exists: ' . (isset($data['author']) ? 'YES' : 'NO'));
        error_log('[PM Import UPDATE] Author is array: ' . (isset($data['author']) && is_array($data['author']) ? 'YES' : 'NO'));
        error_log('[PM Import UPDATE] Author count: ' . (isset($data['author']) && is_array($data['author']) ? count($data['author']) : '0'));

        if (isset($data['author']) && is_array($data['author']) && !empty($data['author'])) {
            error_log('[PM Import UPDATE] Authors: ' . print_r($data['author'], true));
            $author_term_ids = array();

            foreach ($data['author'] as $author_name) {
                error_log('[PM Import UPDATE] Processing author: ' . $author_name);
                if (!empty($author_name) && class_exists('PM_Author_Taxonomy')) {
                    $term_id = PM_Author_Taxonomy::get_or_create_author_term($author_name);
                    error_log('[PM Import UPDATE] Term ID returned: ' . $term_id);
                    if ($term_id && !is_wp_error($term_id)) {
                        $author_term_ids[] = $term_id;
                    }
                }
            }

            error_log('[PM Import UPDATE] Total term IDs: ' . count($author_term_ids));
            if (!empty($author_term_ids)) {
                $result = wp_set_object_terms($post_id, $author_term_ids, 'pm_author', false);
                error_log('[PM Import UPDATE] wp_set_object_terms result: ' . print_r($result, true));
            }
        }

        // Extract and save year from date
        if (isset($data['date']) && !empty($data['date'])) {
            $year = substr($data['date'], 0, 4);
            if (!empty($year) && is_numeric($year)) {
                update_post_meta($post_id, 'pm_year', $year);
            }
        }

        // Update import timestamp
        update_post_meta($post_id, 'pm_import_id', current_time('timestamp'));
        update_post_meta($post_id, 'pm_last_updated', current_time('timestamp'));

        return $post_id;
    }
}
