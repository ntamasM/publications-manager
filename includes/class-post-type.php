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
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions'),
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
        $new_columns['date'] = $columns['date'];

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
                $authors = get_post_meta($post_id, 'pm_author', true);
                if ($authors) {
                    $author_list = explode(' and ', $authors);
                    if (count($author_list) > 2) {
                        echo esc_html($author_list[0]) . ' et al.';
                    } else {
                        echo esc_html(str_replace(' and ', ', ', $authors));
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
}
