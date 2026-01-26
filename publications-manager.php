<?php

/**
 * Plugin Name: Publications Manager
 * Plugin URI: https://ntamadakis.gr
 * Description: Advanced publication management using Custom Post Types with teachPress-compatible fields and Crossref import functionality
 * Version: 2.3.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Ntamadakis
 * Author URI: https://ntamadakis.gr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: publications-manager
 * Domain Path: /languages
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PM_VERSION', '2.3.0');
define('PM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PM_PLUGIN_FILE', __FILE__);

// Load plugin files
require_once PM_PLUGIN_DIR . 'includes/core/class-publication-types.php';
require_once PM_PLUGIN_DIR . 'includes/core/class-author-taxonomy.php';
require_once PM_PLUGIN_DIR . 'includes/core/class-post-type.php';
require_once PM_PLUGIN_DIR . 'includes/admin/class-meta-boxes.php';
require_once PM_PLUGIN_DIR . 'includes/admin/admin-pages.php';
require_once PM_PLUGIN_DIR . 'includes/integrations/class-crossref-import.php';
require_once PM_PLUGIN_DIR . 'includes/integrations/class-bricks-integration.php';
require_once PM_PLUGIN_DIR . 'includes/helpers/class-publication-helpers.php';
require_once PM_PLUGIN_DIR . 'includes/helpers/class-team-member-helpers.php';
require_once PM_PLUGIN_DIR . 'includes/functions.php';

/**
 * Main plugin class
 */
class Publications_Manager
{

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Initialize plugin
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(PM_PLUGIN_FILE), array($this, 'add_action_links'));

        // Activation/Deactivation hooks
        register_activation_hook(PM_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PM_PLUGIN_FILE, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Load text domain for translations
        $this->load_textdomain();

        // Initialize publication types first
        PM_Publication_Types::register_all();

        // Initialize author taxonomy
        PM_Author_Taxonomy::init();

        // Initialize components
        PM_Post_Type::init();
        PM_Meta_Boxes::init();
        PM_Admin_Pages::init();
        PM_Bricks_Integration::init();

        // Flush rewrite rules if needed (after activation)
        if (get_transient('pm_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_transient('pm_flush_rewrite_rules');
        }
    }

    /**
     * Load text domain for translations
     * Similar to teachPress approach - prioritize plugin's own translations
     * 
     * @since 1.0.0
     */
    private function load_textdomain()
    {
        $domain = 'publications-manager';
        $locale = apply_filters('plugin_locale', determine_locale(), $domain);
        $path = dirname(plugin_basename(PM_PLUGIN_FILE)) . '/languages/';
        $mofile = WP_PLUGIN_DIR . '/' . $path . $domain . '-' . $locale . '.mo';

        // Load the plugin's language files first instead of language files from WP languages directory
        // This ensures custom translations in the plugin's /languages folder take priority
        if (!load_textdomain($domain, $mofile)) {
            load_plugin_textdomain($domain, false, $path);
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook)
    {
        global $post;

        // Load on publications list page
        if ('edit.php' === $hook && isset($_GET['post_type']) && 'publication' === $_GET['post_type']) {
            wp_enqueue_style('pm-admin', PM_PLUGIN_URL . 'assets/css/admin.css', array(), PM_VERSION);
        }

        // Only load on our post type pages
        if (('post.php' === $hook || 'post-new.php' === $hook) &&
            isset($post->post_type) && 'publication' === $post->post_type
        ) {
            wp_enqueue_style('pm-admin', PM_PLUGIN_URL . 'assets/css/admin.css', array(), PM_VERSION);
            wp_enqueue_script('pm-admin', PM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PM_VERSION, true);
        }

        // Load on import/export page
        if (isset($_GET['page']) && 'pm-import-export' === $_GET['page']) {
            wp_enqueue_style('pm-admin', PM_PLUGIN_URL . 'assets/css/admin.css', array(), PM_VERSION);
            wp_enqueue_script('pm-import-export', PM_PLUGIN_URL . 'assets/js/import-export.js', array('jquery'), PM_VERSION, true);
            wp_localize_script('pm-import-export', 'pmImport', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pm-import-nonce')
            ));
        }
    }

    /**
     * Activation hook
     */
    public function activate()
    {
        // Make sure our post type is registered
        require_once PM_PLUGIN_DIR . 'includes/class-post-type.php';
        PM_Post_Type::register_post_type();

        // Set transient to flush rewrite rules on next init
        set_transient('pm_flush_rewrite_rules', true, 60);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook
     */
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Add action links on plugins page
     */
    public function add_action_links($links)
    {
        $custom_links = array(
            '<a href="https://ntamadakis.gr/support-me" target="_blank" style="color:#d54e21;font-weight:bold;">❤ Support Me</a>',
        );
        return array_merge($custom_links, $links);
    }
}

// Initialize plugin
Publications_Manager::get_instance();
