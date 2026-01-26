<?php

/**
 * Publication Helper Functions
 * Core utility functions for publication management
 *
 * @package Publications_Manager
 * @since 2.2.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PM_Publication_Helpers
{
    /**
     * Get publication meta field with pm_ prefix
     * 
     * @param int $post_id Publication post ID
     * @param string $key Meta key (without pm_ prefix)
     * @param bool $single Whether to return single value
     * @return mixed Meta value
     */
    public static function get_meta($post_id, $key, $single = true)
    {
        return get_post_meta($post_id, 'pm_' . $key, $single);
    }

    /**
     * Get formatted publication type name
     * 
     * @param int $post_id Publication post ID
     * @return string Formatted type name
     */
    public static function get_formatted_type($post_id)
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
     * Get authors with HTML links for display
     * 
     * @param int $post_id Publication post ID
     * @return string Authors HTML with team member links
     */
    public static function get_authors_html($post_id)
    {
        return PM_Author_Taxonomy::get_authors_html($post_id);
    }

    /**
     * Format authors for display
     * 
     * @param string $authors Comma-separated author names
     * @param int $max Maximum authors to show (0 = all)
     * @param string $separator Separator between authors
     * @return string Formatted authors string
     */
    public static function format_authors($authors, $max = 0, $separator = ', ')
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
     * Parse authors into clean array
     * 
     * @param array $authors_input Raw authors array
     * @return array Cleaned author names
     */
    public static function parse_authors($authors_input)
    {
        if (empty($authors_input) || !is_array($authors_input)) {
            return array();
        }

        return array_filter(array_map('trim', $authors_input));
    }
}
