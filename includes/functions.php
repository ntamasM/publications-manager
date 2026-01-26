<?php

/**
 * Core Helper Functions
 * Contains essential utility functions for the Publications Manager plugin
 *
 * @package Publications_Manager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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
    return get_post_meta($post_id, 'pm_' . $key, $single);
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
    if (empty($authors)) {
        return '';
    }

    $author_array = explode(', ', $authors);

    if ($max > 0 && count($author_array) > $max) {
        $author_array = array_slice($author_array, 0, $max);
        return implode($separator, $author_array) . ' et al.';
    }

    return implode($separator, $author_array);
}

/**
 * Parse authors into array
 * 
 * @param array $authors_input The authors array
 * @return array Array of cleaned author names
 * @since 1.0.4
 */
function pm_parse_authors($authors_input)
{
    if (empty($authors_input) || !is_array($authors_input)) {
        return array();
    }

    // Clean and filter the array
    return array_filter(array_map('trim', $authors_input));
}

/**
 * Find team member by author name
 * Searches for existing author term and returns linked team member
 * 
 * @param string $author_name The author name to search for
 * @return int|false Post ID if found, false otherwise
 * @since 1.0.4
 * @since 2.1.0 Updated to use author taxonomy connections
 */
function pm_find_team_member_by_name($author_name)
{
    $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

    // Check if the post type exists
    if (!post_type_exists($team_cpt_slug)) {
        return false;
    }

    // Normalize the author name
    $normalized_author = trim($author_name);

    // First, check if an author term exists for this name
    $term = get_term_by('name', $normalized_author, 'pm_author');

    if ($term && !is_wp_error($term)) {
        // Check if this author term is linked to a team member
        $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);

        if ($team_member_id && get_post_status($team_member_id) === 'publish') {
            return (int) $team_member_id;
        }
    }

    // Fallback: check if team member title matches author name exactly
    $team_members = get_posts(array(
        'post_type'      => $team_cpt_slug,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'title'          => $normalized_author,
        'fields'         => 'ids'
    ));

    if (!empty($team_members)) {
        return $team_members[0];
    }

    return false;
}

/**
 * Create bidirectional relationship between publication and team member
 * DEPRECATED: Relationships are now managed through the pm_author taxonomy
 * 
 * @deprecated 2.2.0 Use PM_Author_Taxonomy methods instead
 * @param int $publication_id Publication post ID
 * @param int $team_member_id Team member post ID
 * @param string $publication_title Publication title (unused)
 * @param string $publication_slug Publication slug (unused)
 * @since 1.0.4
 */
function pm_create_team_relationship($publication_id, $team_member_id, $publication_title = '', $publication_slug = '')
{
    // Deprecated: Relationships are now managed through pm_author taxonomy
    _deprecated_function(__FUNCTION__, '2.2.0', 'PM_Author_Taxonomy methods');
    return;
}

/**
 * Process author relationships for a publication - DEPRECATED
 * This function is no longer used as author relationships are now handled
 * automatically by PM_Author_Taxonomy::get_or_create_author_term()
 * Kept for backward compatibility
 * 
 * @param int $post_id The publication post ID
 * @since 1.0.4
 * @deprecated 2.1.0 Use PM_Author_Taxonomy instead
 */
function pm_process_author_relationships($post_id)
{
    // This function is deprecated
    // Author-to-team-member linking now happens automatically
    // in PM_Author_Taxonomy::get_or_create_author_term()
    // when author terms are created or saved
    return;
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
 * Returns authors with HTML anchor tags for linked team members
 * 
 * @param int $post_id Publication post ID
 * @return string Formatted authors with links
 * @since 1.0.4
 */
function pm_get_authors_with_links($post_id)
{
    // Use the taxonomy class method
    return PM_Author_Taxonomy::get_authors_html($post_id);
}

/**
 * Get formatted publication type name
 * 
 * @param int $post_id Publication post ID
 * @return string Formatted type name
 */
function pm_get_formatted_type($post_id)
{
    $type_slug = get_post_meta($post_id, 'pm_type', true);

    if (empty($type_slug)) {
        return '';
    }

    $type_data = PM_Publication_Types::get($type_slug);

    if ($type_data && isset($type_data['i18n_singular'])) {
        return $type_data['i18n_singular'];
    }

    return $type_slug;
}

/**
 * Get all publications linked to a team member
 * Returns array of publication data
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
