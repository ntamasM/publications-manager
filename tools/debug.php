<?php

/**
 * Debug helper - Add this temporarily to check if plugin is working
 * 
 * Add this to your wp-config.php to see debug info:
 * define('WP_DEBUG', true);
 * define('WP_DEBUG_LOG', true);
 * define('WP_DEBUG_DISPLAY', false);
 */

add_action('admin_notices', function () {
    if (current_user_can('manage_options')) {
        $post_types = get_post_types(array('public' => true), 'objects');
        $has_publication = isset($post_types['publication']);

        echo '<div class="notice notice-info"><p>';
        echo '<strong>Publications Manager Debug:</strong><br>';
        echo 'Plugin Active: YES<br>';
        echo 'Publication CPT Registered: ' . ($has_publication ? 'YES' : 'NO') . '<br>';

        if ($has_publication) {
            echo 'Menu Position: ' . $post_types['publication']->menu_position . '<br>';
            echo 'Show in Menu: ' . ($post_types['publication']->show_in_menu ? 'YES' : 'NO') . '<br>';
            echo 'Show UI: ' . ($post_types['publication']->show_ui ? 'YES' : 'NO') . '<br>';
            echo 'Capability Type: ' . $post_types['publication']->capability_type . '<br>';
        }

        echo '</p></div>';
    }
});
