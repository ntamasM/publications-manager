<?php

/**
 * Helper Functions
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get publication meta field
 */
function pm_get_meta($post_id, $key, $single = true)
{
    return get_post_meta($post_id, 'pm_' . $key, $single);
}

/**
 * Format authors for display
 */
function pm_format_authors($authors, $max = 0, $separator = ', ')
{
    if (empty($authors)) {
        return '';
    }

    $author_array = explode(' and ', $authors);

    if ($max > 0 && count($author_array) > $max) {
        $author_array = array_slice($author_array, 0, $max);
        return implode($separator, $author_array) . ' et al.';
    }

    return implode($separator, $author_array);
}

/**
 * Parse authors string into array
 * Supports comma-separated format: "John Doe, Jane Smith, Bob Lee"
 * 
 * @param string $authors_string The authors string
 * @return array Array of author names
 * @since 1.0.4
 */
function pm_parse_authors($authors_string)
{
    if (empty($authors_string)) {
        return array();
    }

    // Split by comma
    $authors = array_map('trim', explode(',', $authors_string));

    // Remove empty entries
    $authors = array_filter($authors);

    return $authors;
}

/**
 * Find team member by name
 * 
 * @param string $author_name The author name to search for
 * @return int|false Post ID if found, false otherwise
 * @since 1.0.4
 */
function pm_find_team_member_by_name($author_name)
{
    $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

    // Check if the post type exists
    if (!post_type_exists($team_cpt_slug)) {
        return false;
    }

    // Query for team member with matching title
    $args = array(
        'post_type'      => $team_cpt_slug,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'title'          => $author_name,
        'fields'         => 'ids'
    );

    // WordPress doesn't support 'title' in WP_Query by default, use custom filter
    add_filter('posts_where', 'pm_title_filter', 10, 2);
    $query = new WP_Query($args);
    remove_filter('posts_where', 'pm_title_filter', 10);

    if ($query->have_posts()) {
        return $query->posts[0];
    }

    return false;
}

/**
 * Filter to search by exact post title
 * 
 * @param string $where The WHERE clause
 * @param WP_Query $query The query object
 * @return string Modified WHERE clause
 * @since 1.0.4
 */
function pm_title_filter($where, $query)
{
    global $wpdb;

    if ($title = $query->get('title')) {
        $where .= ' AND ' . $wpdb->posts . '.post_title = \'' . esc_sql($title) . '\'';
    }

    return $where;
}

/**
 * Create bidirectional relationship between publication and team member
 * Uses individual meta entries instead of arrays for better Bricks compatibility
 * 
 * @param int $publication_id Publication post ID
 * @param int $team_member_id Team member post ID
 * @param string $publication_title Publication title
 * @param string $publication_slug Publication slug
 * @since 1.0.4
 */
function pm_create_team_relationship($publication_id, $team_member_id, $publication_title = '', $publication_slug = '')
{
    // Store relationship on publication (array for backward compatibility)
    $pub_team_members = get_post_meta($publication_id, 'pm_team_members', true);
    if (!is_array($pub_team_members)) {
        $pub_team_members = array();
    }

    if (!in_array($team_member_id, $pub_team_members)) {
        $pub_team_members[] = $team_member_id;
        update_post_meta($publication_id, 'pm_team_members', $pub_team_members);
    }

    // Store individual meta entries on team member for Bricks compatibility
    // Each publication gets its own meta entry with a unique key
    $meta_key = 'pm_publication_' . $publication_id;

    // Get publication data if not provided
    if (empty($publication_title)) {
        $publication_title = get_the_title($publication_id);
    }
    if (empty($publication_slug)) {
        $post = get_post($publication_id);
        $publication_slug = $post ? $post->post_name : '';
    }

    // Store as individual meta entry
    update_post_meta($team_member_id, $meta_key, array(
        'publication_id' => $publication_id,
        'title' => $publication_title,
        'slug' => $publication_slug,
        'url' => get_permalink($publication_id)
    ));

    // Also add to simple ID list meta for queries
    add_post_meta($team_member_id, 'pm_publication_id', $publication_id);
}

/**
 * Process author relationships for a publication
 * This is called when a publication is saved
 * 
 * @param int $post_id The publication post ID
 * @since 1.0.4
 */
function pm_process_author_relationships($post_id)
{
    // Get authors
    $authors_string = get_post_meta($post_id, 'pm_author', true);

    if (empty($authors_string)) {
        return;
    }

    // Get publication data
    $publication_title = get_the_title($post_id);
    $post = get_post($post_id);
    $publication_slug = $post ? $post->post_name : '';

    // Clear existing relationships for this publication
    delete_post_meta($post_id, 'pm_team_members');
    delete_post_meta($post_id, 'pm_author_links');

    // Clear old relationships from team members
    $old_relationships = get_posts(array(
        'post_type' => get_option('pm_team_cpt_slug', 'team_member'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'pm_publication_id',
                'value' => $post_id,
                'compare' => '='
            )
        ),
        'fields' => 'ids'
    ));

    foreach ($old_relationships as $team_member_id) {
        delete_post_meta($team_member_id, 'pm_publication_' . $post_id);
        delete_post_meta($team_member_id, 'pm_publication_id', $post_id);
    }

    // Parse authors
    $authors = pm_parse_authors($authors_string);
    $author_links = array();

    foreach ($authors as $author_name) {
        $team_member_id = pm_find_team_member_by_name($author_name);

        if ($team_member_id) {
            // Create relationship with publication data
            pm_create_team_relationship($post_id, $team_member_id, $publication_title, $publication_slug);

            // Store link info for frontend display
            $author_links[$author_name] = array(
                'team_member_id' => $team_member_id,
                'url' => get_permalink($team_member_id)
            );
        }
    }

    // Save author links for easy retrieval
    if (!empty($author_links)) {
        update_post_meta($post_id, 'pm_author_links', $author_links);
    }
}

/**
 * Hook to process author relationships when publication is saved
 */
add_action('save_post_publication', 'pm_process_author_relationships_hook', 20, 1);

function pm_process_author_relationships_hook($post_id)
{
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    pm_process_author_relationships($post_id);
}

/**
 * Format authors with links for display
 * This function wraps matched authors in anchor tags
 * 
 * @param int $post_id Publication post ID
 * @return string Formatted authors with links
 * @since 1.0.4
 */
function pm_get_authors_with_links($post_id)
{
    $authors_string = get_post_meta($post_id, 'pm_author', true);

    if (empty($authors_string)) {
        return '';
    }

    // Get author links
    $author_links = get_post_meta($post_id, 'pm_author_links', true);

    // Parse authors
    $authors = pm_parse_authors($authors_string);

    $formatted_authors = array();

    foreach ($authors as $author_name) {
        if (!empty($author_links) && isset($author_links[$author_name])) {
            // Author has a linked team member - create link
            $url = $author_links[$author_name]['url'];
            $formatted_authors[] = '<a href="' . esc_url($url) . '">' . esc_html($author_name) . '</a>';
        } else {
            // No match - plain text
            $formatted_authors[] = esc_html($author_name);
        }
    }

    return implode(', ', $formatted_authors);
}

/**
 * Filter Bricks Builder dynamic data for pm_author field
 * Adds links to matched authors
 * 
 * @param string $content The content to filter
 * @param object $post The post object
 * @param string|array $context The context/tag information
 * @return string Filtered content
 */
function pm_filter_bricks_author_field($content, $post = null, $context = array())
{
    // Ensure we have a post object
    if (!$post) {
        $post = get_post();
    }

    // Only process if we're dealing with a publication
    if (!is_object($post) || !isset($post->post_type) || $post->post_type !== 'publication') {
        return $content;
    }

    // Get raw author meta
    $raw_author = get_post_meta($post->ID, 'pm_author', true);

    // Check if we should process this field
    $should_process = false;

    // Check tag in context
    if (is_array($context) && isset($context['tag'])) {
        if (strpos($context['tag'], 'pm_author') !== false || strpos($context['tag'], 'post_meta:pm_author') !== false) {
            $should_process = true;
        }
    }

    // Check if content matches raw author string
    if (!empty($raw_author) && $content === $raw_author) {
        $should_process = true;
    }

    // Check if this looks like an author field (contains comma-separated names)
    if (!empty($content) && strpos($content, ',') !== false && !empty($raw_author)) {
        $should_process = true;
    }

    if ($should_process) {
        return pm_get_authors_with_links($post->ID);
    }

    return $content;
}

// Hook into Bricks with proper priority
add_filter('bricks/dynamic_data/render_content', 'pm_filter_bricks_author_field', 20, 3);
add_filter('bricks/dynamic_data/render_tag', 'pm_filter_bricks_author_field', 20, 3);

// Also hook into post meta specifically
add_filter('bricks/dynamic_data/post_meta', 'pm_filter_bricks_author_post_meta', 20, 3);

function pm_filter_bricks_author_post_meta($meta_value, $post_id, $meta_key)
{
    // Only process pm_author field
    if ($meta_key === 'pm_author' && get_post_type($post_id) === 'publication') {
        return pm_get_authors_with_links($post_id);
    }

    return $meta_value;
}

/**
 * Add Bricks query filter for publications linked to team members
 * This allows querying publications from a team member's page
 * 
 * @param array $results Query results
 * @param object $query_obj Bricks query object
 * @return array Modified results
 */
function pm_bricks_query_filter_publications($results, $query_obj)
{
    // Only process publication queries
    if (!isset($query_obj->settings['post_type']) || $query_obj->settings['post_type'] !== 'publication') {
        return $results;
    }

    // Check if we're on a team member page
    $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

    if (is_singular($team_cpt_slug)) {
        $team_member_id = get_the_ID();

        // Get all publication IDs linked to this team member
        $publication_ids = get_post_meta($team_member_id, 'pm_publication_id', false);

        if (!empty($publication_ids)) {
            // Modify the query to only include these publications
            $query_obj->query_vars['post__in'] = $publication_ids;
            $query_obj->query_vars['orderby'] = 'post__in'; // Maintain order
        } else {
            // No publications found - return empty
            $query_obj->query_vars['post__in'] = array(0);
        }
    }

    return $results;
}

add_filter('bricks/query/run', 'pm_bricks_query_filter_publications', 10, 2);

/**
 * Get all publications linked to a team member
 * Returns array of publication data for easy looping in Bricks
 * 
 * @param int $team_member_id Team member post ID
 * @return array Array of publication data
 * @since 1.0.4
 */
function pm_get_team_member_publications($team_member_id)
{
    global $wpdb;

    // Get all pm_publication_{id} meta keys for this team member
    $publication_metas = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_key, meta_value FROM {$wpdb->postmeta} 
        WHERE post_id = %d 
        AND meta_key LIKE 'pm_publication_%%' 
        AND meta_key != 'pm_publication_id'",
        $team_member_id
    ));

    $publications = array();

    foreach ($publication_metas as $meta) {
        $data = maybe_unserialize($meta->meta_value);
        if (is_array($data) && isset($data['publication_id'])) {
            $publications[] = $data;
        }
    }

    return $publications;
}

/**
 * Shortcode to display authors with links
 * Usage: [pm_authors id="123"] or [pm_authors] (uses current post)
 * 
 * @since 1.0.4
 */
add_shortcode('pm_authors', 'pm_authors_shortcode');

function pm_authors_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'id' => get_the_ID()
    ), $atts);

    $post_id = absint($atts['id']);

    if (!$post_id || get_post_type($post_id) !== 'publication') {
        return '';
    }

    return pm_get_authors_with_links($post_id);
}


/**
 * Get publication citation
 */
function pm_get_citation($post_id, $style = 'apa')
{
    $type = pm_get_meta($post_id, 'type');
    $author = pm_get_meta($post_id, 'author');
    $title = get_the_title($post_id);
    $year = substr(pm_get_meta($post_id, 'date'), 0, 4);

    $citation = '';

    switch ($style) {
        case 'apa':
            $citation = pm_format_authors($author, 7) . ' (' . $year . '). ' . $title . '. ';

            if ($type === 'article') {
                $journal = pm_get_meta($post_id, 'journal');
                $volume = pm_get_meta($post_id, 'volume');
                $pages = pm_get_meta($post_id, 'pages');

                if ($journal) {
                    $citation .= '<em>' . $journal . '</em>';
                    if ($volume) {
                        $citation .= ', ' . $volume;
                    }
                    if ($pages) {
                        $citation .= ', ' . $pages;
                    }
                    $citation .= '.';
                }
            } elseif ($type === 'book') {
                $publisher = pm_get_meta($post_id, 'publisher');
                if ($publisher) {
                    $citation .= $publisher . '.';
                }
            }
            break;

        default:
            $citation = $author . ' (' . $year . '). ' . $title;
            break;
    }

    $doi = pm_get_meta($post_id, 'doi');
    if ($doi) {
        $citation .= ' https://doi.org/' . $doi;
    }

    return $citation;
}

/**
 * Get publication link
 */
function pm_get_publication_link($post_id)
{
    $url = pm_get_meta($post_id, 'url');
    $doi = pm_get_meta($post_id, 'doi');

    if ($url) {
        return $url;
    } elseif ($doi) {
        return 'https://doi.org/' . $doi;
    }

    return get_permalink($post_id);
}

/**
 * Display publication
 */
function pm_display_publication($post_id, $args = array())
{
    $defaults = array(
        'show_abstract' => false,
        'show_links' => true,
        'citation_style' => 'apa'
    );

    $args = wp_parse_args($args, $defaults);

    $output = '<div class="pm-publication" data-id="' . $post_id . '">';

    // Citation
    $output .= '<div class="pm-citation">' . pm_get_citation($post_id, $args['citation_style']) . '</div>';

    // Abstract
    if ($args['show_abstract']) {
        $abstract = pm_get_meta($post_id, 'abstract');
        if ($abstract) {
            $output .= '<div class="pm-abstract">' . wpautop($abstract) . '</div>';
        }
    }

    // Links
    if ($args['show_links']) {
        $output .= '<div class="pm-links">';

        $url = pm_get_publication_link($post_id);
        if ($url) {
            $output .= '<a href="' . esc_url($url) . '" target="_blank" class="pm-link pm-link-view">' . __('View Publication', 'publications-manager') . '</a>';
        }

        $doi = pm_get_meta($post_id, 'doi');
        if ($doi) {
            $output .= ' <a href="https://doi.org/' . esc_attr($doi) . '" target="_blank" class="pm-link pm-link-doi">DOI</a>';
        }

        $bibtex = pm_get_meta($post_id, 'bibtex');
        if ($bibtex) {
            $output .= ' <a href="#" class="pm-link pm-link-bibtex" data-bibtex="' . esc_attr($bibtex) . '">' . __('BibTeX', 'publications-manager') . '</a>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}

/**
 * Register publication meta fields for REST API and page builders
 * Makes fields available in Bricks Builder, Elementor, and other page builders
 * 
 * @since 1.0.2
 */
function pm_register_meta_fields_for_rest()
{
    $fields = array(
        'pm_type',
        'pm_author',
        'pm_editor',
        'pm_doi',
        'pm_date',
        'pm_journal',
        'pm_booktitle',
        'pm_issuetitle',
        'pm_volume',
        'pm_number',
        'pm_issue',
        'pm_pages',
        'pm_chapter',
        'pm_publisher',
        'pm_address',
        'pm_edition',
        'pm_series',
        'pm_institution',
        'pm_organization',
        'pm_school',
        'pm_howpublished',
        'pm_techtype',
        'pm_isbn',
        'pm_crossref',
        'pm_key',
        'pm_url',
        'pm_urldate',
        'pm_image_url',
        'pm_image_ext',
        'pm_rel_page',
        'pm_abstract',
        'pm_note',
        'pm_comment',
        'pm_status',
        'pm_bibtex_key',
    );

    foreach ($fields as $field) {
        register_post_meta('publication', $field, array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'description' => sprintf(__('Publication field: %s', 'publications-manager'), str_replace('pm_', '', $field)),
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));
    }

    // Register relationship meta fields for Bricks Builder Query Loops
    // These store publication/team member relationships

    // For publications: stores array of linked team member IDs
    register_post_meta('publication', 'pm_team_members', array(
        'show_in_rest' => array(
            'schema' => array(
                'type' => 'array',
                'items' => array(
                    'type' => 'integer'
                )
            )
        ),
        'single' => true,
        'type' => 'array',
        'description' => __('Linked team members', 'publications-manager'),
        'sanitize_callback' => function ($value) {
            return is_array($value) ? array_map('absint', $value) : array();
        },
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));

    // For publications: stores author link data
    register_post_meta('publication', 'pm_author_links', array(
        'show_in_rest' => false,
        'single' => true,
        'type' => 'array',
        'description' => __('Author link data', 'publications-manager'),
        'sanitize_callback' => function ($value) {
            return is_array($value) ? $value : array();
        },
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));

    // For team members: register individual publication meta fields
    // Each publication creates its own meta entry: pm_publication_{id}
    $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
    if (post_type_exists($team_cpt_slug)) {
        // Register the publication ID meta (used for queries)
        register_post_meta($team_cpt_slug, 'pm_publication_id', array(
            'show_in_rest' => true,
            'single' => false, // Multiple values allowed
            'type' => 'integer',
            'description' => __('Linked publication ID', 'publications-manager'),
            'sanitize_callback' => 'absint',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));
    }
}
add_action('init', 'pm_register_meta_fields_for_rest', 20);
