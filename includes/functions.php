<?php

/**
 * Core Helper Functions
 * Backward-compatible wrapper functions for the Publications Manager plugin
 *
 * @package Publications_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// PUBLICATION FUNCTIONS - Wrappers for PM_Publication_Helpers
// ============================================================================

/**
 * Get publication meta field
 * 
 * @param int $post_id Publication post ID
 * @param string $key Meta key (without pm_ prefix)
 * @param bool $single Whether to return single value
 * @return mixed Meta value
 */
function pm_get_meta($post_id, $key, $single = true)
{
    return PM_Publication_Helpers::get_meta($post_id, $key, $single);
}

/**
 * Get formatted publication type name
 * 
 * @param int $post_id Publication post ID
 * @return string Formatted type name
 */
function pm_get_formatted_type($post_id)
{
    return PM_Publication_Helpers::get_formatted_type($post_id);
}

/**
 * Format authors for display
 * 
 * @param string $authors Comma-separated author names
 * @param int $max Maximum authors to show (0 = all)
 * @param string $separator Separator between authors
 * @return string Formatted authors string
 */
function pm_format_authors($authors, $max = 0, $separator = ', ')
{
    return PM_Publication_Helpers::format_authors($authors, $max, $separator);
}

/**
 * Parse authors into array
 * 
 * @param array $authors_input The authors array
 * @return array Array of cleaned author names
 */
function pm_parse_authors($authors_input)
{
    return PM_Publication_Helpers::parse_authors($authors_input);
}

/**
 * Format authors with links for display
 * 
 * @param int $post_id Publication post ID
 * @return string Formatted authors with links
 */
function pm_get_authors_with_links($post_id)
{
    return PM_Publication_Helpers::get_authors_html($post_id);
}

// ============================================================================
// TEAM MEMBER FUNCTIONS - Wrappers for PM_Team_Member_Helpers
// ============================================================================

/**
 * Find team member by author name
 * 
 * @param string $author_name The author name to search for
 * @return int|false Post ID if found, false otherwise
 */
function pm_find_team_member_by_name($author_name)
{
    return PM_Team_Member_Helpers::find_by_name($author_name);
}

/**
 * Get all publications linked to a team member
 * 
 * @param int $team_member_id Team member post ID
 * @return array Array of publication data
 */
function pm_get_team_member_publications($team_member_id)
{
    return PM_Team_Member_Helpers::get_publications_data($team_member_id);
}

// ============================================================================
// DEPRECATED FUNCTIONS - Kept for backward compatibility
// ============================================================================

// ============================================================================
// DEPRECATED FUNCTIONS - Kept for backward compatibility
// ============================================================================

/**
 * Create bidirectional relationship between publication and team member
 * DEPRECATED: Relationships are now managed through the pm_author taxonomy
 * 
 * @deprecated 2.2.0 Use PM_Author_Taxonomy methods instead
 * @param int $publication_id Publication post ID
 * @param int $team_member_id Team member post ID (unused)
 * @param string $publication_title Publication title (unused)
 * @param string $publication_slug Publication slug (unused)
 */
function pm_create_team_relationship($publication_id, $team_member_id, $publication_title = '', $publication_slug = '')
{
    _deprecated_function(__FUNCTION__, '2.2.0', 'PM_Author_Taxonomy methods');
}

/**
 * Process author relationships for a publication
 * DEPRECATED: Now handled automatically by PM_Author_Taxonomy
 * 
 * @deprecated 2.1.0 Use PM_Author_Taxonomy instead
 * @param int $post_id The publication post ID
 */
function pm_process_author_relationships($post_id)
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'PM_Author_Taxonomy');
}

/**
 * Hook to process author relationships when publication is saved
 * DEPRECATED: Relationships now managed automatically via taxonomy
 * 
 * @deprecated 2.2.1
 */
add_action('save_post_publication', 'pm_process_author_relationships_hook', 20, 1);

function pm_process_author_relationships_hook($post_id)
{
    // No longer needed - taxonomy system handles this
    return;
}

// ============================================================================
// SHORTCODES
// ============================================================================
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
 * Get publication link
 * Returns the external URL, DOI link, or permalink
 * 
 * @param int $post_id Publication post ID
 * @return string Publication link
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
 * Display publication with formatted citation
 * 
 * @param int $post_id Publication post ID
 * @param array $args Display arguments
 * @return string HTML output
 */
function pm_display_publication($post_id, $args = array())
{
    $defaults = array(
        'show_abstract' => false,
        'show_links' => true,
    );

    $args = wp_parse_args($args, $defaults);

    $output = '<div class="pm-publication" data-id="' . $post_id . '">';

    // Title
    $output .= '<div class="pm-title">' . esc_html(get_the_title($post_id)) . '</div>';

    // Authors
    $authors_html = pm_get_authors_with_links($post_id);
    if ($authors_html) {
        $output .= '<div class="pm-authors">' . $authors_html . '</div>';
    }

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
        'pm_editor',
        'pm_doi',
        'pm_date',
        'pm_year',
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

    // pm_authors is now a taxonomy (pm_author) registered in PM_Author_Taxonomy
    // Legacy REST API registration removed in v2.1.0
    // Authors are now accessible via the taxonomy REST endpoint

    // Register relationship meta fields

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
