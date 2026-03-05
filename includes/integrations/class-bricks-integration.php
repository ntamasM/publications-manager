<?php

/**
 * Bricks Builder Integration
 * Handles all Bricks Builder related functionality:
 * - Dynamic data filters for publication fields (pm_authors, pm_type, pm_url)
 * - Auto-filtering publication queries on team member single pages
 *
 * @package Publications_Manager
 * @since 2.0.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PM_Bricks_Integration
{
    /**
     * Initialize Bricks integration
     */
    public static function init()
    {
        // Only load if Bricks is active
        if (!defined('BRICKS_VERSION')) {
            return;
        }

        // Filter dynamic data rendering
        add_filter('bricks/dynamic_data/render_content', array(__CLASS__, 'filter_dynamic_data'), 20, 3);
        add_filter('bricks/dynamic_data/render_tag', array(__CLASS__, 'filter_dynamic_data'), 20, 3);
        add_filter('bricks/dynamic_data/post_meta', array(__CLASS__, 'filter_post_meta'), 20, 3);

        // Filter term meta for author taxonomy custom fields
        add_filter('bricks/dynamic_data/term_meta', array(__CLASS__, 'filter_term_meta'), 20, 3);

        // Auto-filter publication queries on team member single pages
        // Uses bricks/posts/query_vars (fires BEFORE WP_Query creation)
        // Note: bricks/query/run only fires for non-post query types (API, array, etc.)
        add_filter('bricks/posts/query_vars', array(__CLASS__, 'filter_team_publications_query'), 10, 3);
    }

    /**
     * Filter Bricks dynamic data for pm_authors, pm_type, and pm_url fields
     */
    public static function filter_dynamic_data($content, $post = null, $context = array())
    {
        if (!$post) {
            $post = get_post();
        }

        if (!is_object($post) || $post->post_type !== 'publication') {
            return $content;
        }

        if (is_array($context) && isset($context['tag'])) {
            if (strpos($context['tag'], 'pm_type') !== false || strpos($context['tag'], 'post_meta:pm_type') !== false) {
                return pm_get_formatted_type($post->ID);
            }

            if (strpos($context['tag'], 'pm_authors') !== false || strpos($context['tag'], 'post_meta:pm_authors') !== false) {
                return PM_Author_Taxonomy::get_authors_html($post->ID);
            }

            if (strpos($context['tag'], 'pm_url') !== false || strpos($context['tag'], 'post_meta:pm_url') !== false) {
                return self::get_publication_url($post->ID);
            }
        }

        // Check if content matches raw type value
        $raw_type = get_post_meta($post->ID, 'pm_type', true);
        if (!empty($raw_type) && $content === $raw_type) {
            return pm_get_formatted_type($post->ID);
        }

        return $content;
    }

    /**
     * Filter post meta specifically
     * Handles: pm_authors (taxonomy-based), pm_type (formatted label), pm_url (smart fallback)
     *
     * Note: On team member pages, Bricks may pass the team member's post ID instead of
     * the publication's post ID for link URL fields inside a query loop. We detect this
     * by checking if the global $post is a publication (set by Bricks' WP_Query loop).
     */
    public static function filter_post_meta($meta_value, $post_id, $meta_key)
    {
        // Only intercept our pm_ keys
        if (!in_array($meta_key, array('pm_authors', 'pm_type', 'pm_url'), true)) {
            return $meta_value;
        }

        // If the given post_id is not a publication, check if the global $post
        // is a publication (happens inside Bricks query loops on non-publication pages)
        if (get_post_type($post_id) !== 'publication') {
            global $post;
            if ($post && is_object($post) && $post->post_type === 'publication') {
                $post_id = $post->ID;
            } else {
                return $meta_value;
            }
        }

        if ($meta_key === 'pm_authors') {
            return PM_Author_Taxonomy::get_authors_html($post_id);
        }

        if ($meta_key === 'pm_type') {
            return pm_get_formatted_type($post_id);
        }

        if ($meta_key === 'pm_url') {
            return self::get_publication_url($post_id);
        }

        return $meta_value;
    }

    /**
     * Get the best available URL for a publication
     * Priority: pm_url meta → DOI URL → publication permalink
     *
     * @param int $post_id Publication post ID
     * @return string URL
     */
    public static function get_publication_url($post_id)
    {
        // 1. Check pm_url meta field (external URL)
        $url = get_post_meta($post_id, 'pm_url', true);
        if (!empty($url)) {
            return esc_url($url);
        }

        // 2. Construct URL from DOI if available
        $doi = get_post_meta($post_id, 'pm_doi', true);
        if (!empty($doi)) {
            return esc_url('https://doi.org/' . $doi);
        }

        // 3. Fall back to the publication's WordPress permalink
        return get_permalink($post_id);
    }

    /**
     * Filter term meta for author taxonomy
     * Makes cf_pm_author_team_url accessible in Bricks Builder
     */
    public static function filter_term_meta($meta_value, $term_id, $meta_key)
    {
        // Check if this is an author term
        $term = get_term($term_id);
        if (!$term || is_wp_error($term) || $term->taxonomy !== 'pm_author') {
            return $meta_value;
        }

        // Return the stored URL for cf_pm_author_team_url
        if ($meta_key === 'pm_author_team_url') {
            $stored_url = get_term_meta($term_id, 'pm_author_team_url', true);
            return $stored_url ? $stored_url : $meta_value;
        }

        return $meta_value;
    }

    /**
     * Auto-filter publication WP_Query on team member single pages
     *
     * When a Bricks query loop queries the "publication" post type on a team member
     * single page, this automatically adds a tax_query to show only publications
     * whose pm_author terms are linked to that team member.
     *
     * Hooks into bricks/posts/query_vars which fires BEFORE WP_Query is constructed.
     *
     * How it works:
     * 1. Detects you're on a team member single page
     * 2. Finds all pm_author taxonomy terms linked to that team member
     * 3. Adds a tax_query so only publications by those authors are returned
     *
     * @param array  $query_vars WP_Query arguments
     * @param array  $settings   Bricks element settings
     * @param string $element_id Bricks element ID
     * @return array Modified query vars
     */
    public static function filter_team_publications_query($query_vars, $settings, $element_id)
    {
        // Only filter queries for publications
        $post_type = isset($query_vars['post_type']) ? $query_vars['post_type'] : '';

        // Handle post_type as both string and array (Bricks may use either)
        if (is_array($post_type)) {
            if (!in_array('publication', $post_type, true)) {
                return $query_vars;
            }
        } else {
            if ($post_type !== 'publication') {
                return $query_vars;
            }
        }

        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        // Auto-detect: are we on a team member single page?
        $team_member_id = false;

        if (is_singular($team_cpt_slug)) {
            $team_member_id = get_queried_object_id();
        } else {
            // Fallback for Bricks editor preview
            $post = get_post();
            if ($post && $post->post_type === $team_cpt_slug) {
                $team_member_id = $post->ID;
            }
        }

        if (!$team_member_id) {
            return $query_vars;
        }

        // Find author term IDs linked to this team member
        $author_term_ids = self::get_author_terms_for_team_member($team_member_id);

        if (!empty($author_term_ids)) {
            // Add tax_query to filter by the team member's author terms
            if (!isset($query_vars['tax_query'])) {
                $query_vars['tax_query'] = array();
            }

            $query_vars['tax_query'][] = array(
                'taxonomy' => 'pm_author',
                'field'    => 'term_id',
                'terms'    => $author_term_ids,
                'operator' => 'IN',
            );
        } else {
            // No author terms found — return no results
            $query_vars['post__in'] = array(0);
        }

        return $query_vars;
    }

    /**
     * Get pm_author taxonomy term IDs linked to a team member
     * Looks up author terms that have pm_team_member_id pointing to this team member
     *
     * @param int $team_member_id Team member post ID
     * @return array Array of term IDs
     */
    private static function get_author_terms_for_team_member($team_member_id)
    {
        // Primary method: get pm_author_term_id values stored on the team member
        $term_ids = get_post_meta($team_member_id, 'pm_author_term_id', false);

        if (!empty($term_ids)) {
            // Validate that these terms still exist
            $valid_ids = array();
            foreach ($term_ids as $term_id) {
                $term = get_term(intval($term_id), 'pm_author');
                if ($term && !is_wp_error($term)) {
                    $valid_ids[] = intval($term_id);
                }
            }
            if (!empty($valid_ids)) {
                return $valid_ids;
            }
        }

        // Fallback: query terms that have this team member ID in their meta
        $terms = get_terms(array(
            'taxonomy'   => 'pm_author',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'   => 'pm_team_member_id',
                    'value' => $team_member_id,
                    'type'  => 'NUMERIC',
                ),
            ),
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            return wp_list_pluck($terms, 'term_id');
        }

        return array();
    }
}
