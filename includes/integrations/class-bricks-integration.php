<?php

/**
 * Bricks Builder Integration
 * Handles all Bricks Builder related functionality
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
        // Filter dynamic data rendering
        add_filter('bricks/dynamic_data/render_content', array(__CLASS__, 'filter_dynamic_data'), 20, 3);
        add_filter('bricks/dynamic_data/render_tag', array(__CLASS__, 'filter_dynamic_data'), 20, 3);
        add_filter('bricks/dynamic_data/post_meta', array(__CLASS__, 'filter_post_meta'), 20, 3);

        // Filter term meta for author taxonomy custom fields
        add_filter('bricks/dynamic_data/term_meta', array(__CLASS__, 'filter_term_meta'), 20, 3);

        // Filter queries for team member publications
        add_filter('bricks/query/run', array(__CLASS__, 'filter_team_publications_query'), 10, 2);
    }

    /**
     * Filter Bricks dynamic data for pm_authors and pm_type fields
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
     */
    public static function filter_post_meta($meta_value, $post_id, $meta_key)
    {
        if (get_post_type($post_id) !== 'publication') {
            return $meta_value;
        }

        if ($meta_key === 'pm_authors') {
            return PM_Author_Taxonomy::get_authors_html($post_id);
        }

        if ($meta_key === 'pm_type') {
            return pm_get_formatted_type($post_id);
        }

        return $meta_value;
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
     * Filter publications query on team member pages
     */
    public static function filter_team_publications_query($results, $query_obj)
    {
        if (!isset($query_obj->settings['post_type']) || $query_obj->settings['post_type'] !== 'publication') {
            return $results;
        }

        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        if (is_singular($team_cpt_slug)) {
            $team_member_id = get_the_ID();
            $publication_ids = get_post_meta($team_member_id, 'pm_publication_id', false);

            if (!empty($publication_ids)) {
                $query_obj->query_vars['post__in'] = $publication_ids;
                $query_obj->query_vars['orderby'] = 'post__in';
            } else {
                $query_obj->query_vars['post__in'] = array(0);
            }
        }

        return $results;
    }
}
