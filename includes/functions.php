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
    return get_post_meta($post_id, '_pm_' . $key, $single);
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
        'pm_year',
        'pm_month',
        'pm_journal',
        'pm_volume',
        'pm_number',
        'pm_pages',
        'pm_publisher',
        'pm_address',
        'pm_isbn',
        'pm_issn',
        'pm_url',
        'pm_note',
        'pm_abstract',
        'pm_keywords',
        'pm_bibtex_key',
        'pm_booktitle',
        'pm_chapter',
        'pm_edition',
        'pm_series',
        'pm_howpublished',
        'pm_organization',
        'pm_institution',
        'pm_school',
        'pm_techtype',
        'pm_crossref',
        'pm_key',
        'pm_urldate',
        'pm_status',
        'pm_issuetitle',
        'pm_image_url',
        'pm_rel_page',
        'pm_comment',
        'pm_award',
        'pm_import_id',
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
}
add_action('init', 'pm_register_meta_fields_for_rest', 20);
