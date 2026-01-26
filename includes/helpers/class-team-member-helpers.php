<?php

/**
 * Team Member Helper Functions
 * Functions for managing team member relationships
 *
 * @package Publications_Manager
 * @since 2.2.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PM_Team_Member_Helpers
{
    /**
     * Find team member by author name
     * Searches for existing author term and returns linked team member
     * 
     * @param string $author_name The author name to search for
     * @return int|false Post ID if found, false otherwise
     */
    public static function find_by_name($author_name)
    {
        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        if (!post_type_exists($team_cpt_slug)) {
            return false;
        }

        $normalized_author = trim($author_name);

        // Check if an author term exists for this name
        $term = get_term_by('name', $normalized_author, 'pm_author');

        if ($term && !is_wp_error($term)) {
            $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);

            if ($team_member_id && get_post_status($team_member_id) === 'publish') {
                return (int) $team_member_id;
            }
        }

        // Fallback: check if team member title matches
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
     * Get all publications for a team member
     * 
     * @param int $team_member_id Team member post ID
     * @return array Publication IDs
     */
    public static function get_publications($team_member_id)
    {
        return get_post_meta($team_member_id, 'pm_author_term_id', false);
    }

    /**
     * Get publication data for a team member
     * 
     * @param int $team_member_id Team member post ID
     * @return array Array of publication data
     */
    public static function get_publications_data($team_member_id)
    {
        global $wpdb;

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
}
