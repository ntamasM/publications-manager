<?php

/**
 * Custom Post Type Registration
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class PM_Post_Type
{

    /**
     * Initialize - Register immediately, don't wait for another init hook
     */
    public static function init()
    {
        // Register immediately
        self::register_post_type();

        // Add filter for Gutenberg
        add_filter('use_block_editor_for_post_type', array(__CLASS__, 'disable_gutenberg'), 10, 2);

        // Add custom columns
        add_filter('manage_publication_posts_columns', array(__CLASS__, 'add_custom_columns'));
        add_action('manage_publication_posts_custom_column', array(__CLASS__, 'custom_column_content'), 10, 2);

        // Make columns sortable
        add_filter('manage_edit-publication_sortable_columns', array(__CLASS__, 'sortable_columns'));

        // Add filters/dropdowns
        add_action('restrict_manage_posts', array(__CLASS__, 'add_admin_filters'));
        add_filter('parse_query', array(__CLASS__, 'filter_by_meta'));
    }

    /**
     * Register the Publications custom post type
     */
    public static function register_post_type()
    {

        $labels = array(
            'name'                  => _x('Publications', 'Post type general name', 'publications-manager'),
            'singular_name'         => _x('Publication', 'Post type singular name', 'publications-manager'),
            'menu_name'             => _x('Publications', 'Admin Menu text', 'publications-manager'),
            'name_admin_bar'        => _x('Publication', 'Add New on Toolbar', 'publications-manager'),
            'add_new'               => __('Add New', 'publications-manager'),
            'add_new_item'          => __('Add New Publication', 'publications-manager'),
            'new_item'              => __('New Publication', 'publications-manager'),
            'edit_item'             => __('Edit Publication', 'publications-manager'),
            'view_item'             => __('View Publication', 'publications-manager'),
            'all_items'             => __('All Publications', 'publications-manager'),
            'search_items'          => __('Search Publications', 'publications-manager'),
            'parent_item_colon'     => __('Parent Publications:', 'publications-manager'),
            'not_found'             => __('No publications found.', 'publications-manager'),
            'not_found_in_trash'    => __('No publications found in Trash.', 'publications-manager'),
            'featured_image'        => _x('Publication Cover Image', 'Overrides the "Featured Image" phrase', 'publications-manager'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'publications-manager'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'publications-manager'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'publications-manager'),
            'archives'              => _x('Publication archives', 'The post type archive label used in nav menus', 'publications-manager'),
            'insert_into_item'      => _x('Insert into publication', 'Overrides the "Insert into post" phrase', 'publications-manager'),
            'uploaded_to_this_item' => _x('Uploaded to this publication', 'Overrides the "Uploaded to this post" phrase', 'publications-manager'),
            'filter_items_list'     => _x('Filter publications list', 'Screen reader text for the filter links', 'publications-manager'),
            'items_list_navigation' => _x('Publications list navigation', 'Screen reader text for the pagination', 'publications-manager'),
            'items_list'            => _x('Publications list', 'Screen reader text for the items list', 'publications-manager'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('Publication entries with full academic metadata', 'publications-manager'),
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_admin_bar'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'publications'),
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-book-alt',
            'supports'              => array('title', 'thumbnail', 'custom-fields', 'revisions'),
            'show_in_rest'          => true, // Enable REST API for Bricks Builder and other page builders
        );

        register_post_type('publication', $args);
    }

    /**
     * Disable Gutenberg editor for Publications
     */
    public static function disable_gutenberg($use_block_editor, $post_type)
    {
        if ('publication' === $post_type) {
            return false;
        }
        return $use_block_editor;
    }

    /**
     * Add custom columns to publications list
     */
    public static function add_custom_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['pm_type'] = __('Type', 'publications-manager');
        $new_columns['pm_authors'] = __('Authors', 'publications-manager');
        $new_columns['pm_year'] = __('Year', 'publications-manager');

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public static function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'pm_type':
                $type = get_post_meta($post_id, 'pm_type', true);
                if ($type) {
                    $type_data = PM_Publication_Types::get($type);
                    echo $type_data ? esc_html($type_data['i18n_singular']) : esc_html($type);
                } else {
                    echo '—';
                }
                break;

            case 'pm_authors':
                $authors = get_post_meta($post_id, 'pm_authors', false);
                if (!empty($authors)) {
                    if (count($authors) > 2) {
                        echo esc_html($authors[0]) . ' et al.';
                    } else {
                        echo esc_html(implode(', ', $authors));
                    }
                } else {
                    echo '—';
                }
                break;

            case 'pm_year':
                $date = get_post_meta($post_id, 'pm_date', true);
                if ($date) {
                    echo esc_html(substr($date, 0, 4));
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public static function sortable_columns($columns)
    {
        $columns['pm_type'] = 'pm_type';
        $columns['pm_authors'] = 'pm_authors';
        $columns['pm_year'] = 'pm_year';
        return $columns;
    }

    /**
     * Add filter dropdowns to admin list
     */
    public static function add_admin_filters($post_type)
    {
        if ('publication' !== $post_type) {
            return;
        }

        // Type filter
        $types = PM_Publication_Types::get_all();
        $current_type = isset($_GET['pm_type']) ? sanitize_text_field($_GET['pm_type']) : '';

        echo '<select name="pm_type" id="pm_type">';
        echo '<option value="">' . __('All Types', 'publications-manager') . '</option>';
        foreach ($types as $type_key => $type_data) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($type_key),
                selected($current_type, $type_key, false),
                esc_html($type_data['i18n_singular'])
            );
        }
        echo '</select>';

        // Year filter
        global $wpdb;
        $years = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'pm_year' 
            AND meta_value != '' 
            ORDER BY meta_value DESC
        ");

        if (!empty($years)) {
            $current_year = isset($_GET['pm_year']) ? sanitize_text_field($_GET['pm_year']) : '';

            echo '<select name="pm_year" id="pm_year">';
            echo '<option value="">' . __('All Years', 'publications-manager') . '</option>';
            foreach ($years as $year) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($year),
                    selected($current_year, $year, false),
                    esc_html($year)
                );
            }
            echo '</select>';
        }

        // Author filter
        global $wpdb;
        $authors = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'pm_authors' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ");

        if (!empty($authors)) {
            $current_author = isset($_GET['pm_author_filter']) ? sanitize_text_field($_GET['pm_author_filter']) : '';

            echo '<select name="pm_author_filter" id="pm_author_filter">';
            echo '<option value="">' . __('All Authors', 'publications-manager') . '</option>';
            foreach ($authors as $author) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($author),
                    selected($current_author, $author, false),
                    esc_html($author)
                );
            }
            echo '</select>';
        }
    }

    /**
     * Filter publications by meta values
     */
    public static function filter_by_meta($query)
    {
        global $pagenow;

        if (!is_admin() || $pagenow !== 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== 'publication') {
            return $query;
        }

        $meta_query = array();

        // Filter by type
        if (isset($_GET['pm_type']) && !empty($_GET['pm_type'])) {
            $meta_query[] = array(
                'key' => 'pm_type',
                'value' => sanitize_text_field($_GET['pm_type']),
                'compare' => '='
            );
        }

        // Filter by year
        if (isset($_GET['pm_year']) && !empty($_GET['pm_year'])) {
            $meta_query[] = array(
                'key' => 'pm_year',
                'value' => sanitize_text_field($_GET['pm_year']),
                'compare' => '='
            );
        }

        // Filter by author
        if (isset($_GET['pm_author_filter']) && !empty($_GET['pm_author_filter'])) {
            $meta_query[] = array(
                'key' => 'pm_authors',
                'value' => sanitize_text_field($_GET['pm_author_filter']),
                'compare' => '='
            );
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }

        // Handle sorting
        $orderby = $query->get('orderby');

        // Set default sorting to pm_date DESC if no sorting is specified
        if (empty($orderby)) {
            $query->set('meta_key', 'pm_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        } elseif (in_array($orderby, array('pm_type', 'pm_authors', 'pm_year'))) {
            // Handle custom column sorting
            $query->set('meta_key', $orderby);
            $query->set('orderby', 'meta_value');
        }

        return $query;
    }
}
