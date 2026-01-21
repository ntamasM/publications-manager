<?php

/**
 * Admin Pages
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class PM_Admin_Pages
{

    /**
     * Initialize
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_menu_pages'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('wp_ajax_pm_import_doi', array(__CLASS__, 'ajax_import_doi'));

        // Clean up team member connections when a publication is permanently deleted
        add_action('before_delete_post', array(__CLASS__, 'cleanup_publication_connections'));
    }

    /**
     * Add menu pages
     */
    public static function add_menu_pages()
    {
        add_submenu_page(
            'edit.php?post_type=publication',
            __('Import/Export', 'publications-manager'),
            __('Import/Export', 'publications-manager'),
            'manage_options',
            'pm-import-export',
            array(__CLASS__, 'render_import_export_page')
        );

        add_submenu_page(
            'edit.php?post_type=publication',
            __('Settings and Info', 'publications-manager'),
            __('Settings and Info', 'publications-manager'),
            'manage_options',
            'pm-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Render import/export page
     */
    public static function render_import_export_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="pm-import-export-container">

                <!-- Import Section -->
                <div class="pm-section pm-import-section">
                    <h2><?php _e('Import from Crossref', 'publications-manager'); ?></h2>
                    <p class="description">
                        <?php _e('Import publications using DOI (Digital Object Identifier) from Crossref.org. You can import multiple publications by entering multiple DOIs separated by spaces or new lines.', 'publications-manager'); ?>
                    </p>

                    <form id="pm-import-form" method="post">
                        <?php wp_nonce_field('pm_import_action', 'pm_import_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="pm_doi_input"><?php _e('DOI(s)', 'publications-manager'); ?></label>
                                </th>
                                <td>
                                    <textarea
                                        name="pm_doi_input"
                                        id="pm_doi_input"
                                        rows="10"
                                        class="large-text"
                                        placeholder="<?php esc_attr_e('Enter one or more DOIs (e.g., 10.1000/xyz123)', 'publications-manager'); ?>"></textarea>
                                    <p class="description">
                                        <?php _e('Examples:', 'publications-manager'); ?>
                                        <code>10.1038/nature12373</code> or
                                        <code>10.1126/science.1259855</code>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary" id="pm-import-btn">
                                <?php _e('Import Publications', 'publications-manager'); ?>
                            </button>
                            <span class="spinner" id="pm-import-spinner"></span>
                        </p>
                    </form>

                    <div id="pm-import-results" class="pm-results"></div>
                </div>

                <!-- Export Section -->
                <div class="pm-section pm-export-section">
                    <h2><?php _e('Export Publications', 'publications-manager'); ?></h2>
                    <p class="description">
                        <?php _e('Export your publications in various formats.', 'publications-manager'); ?>
                    </p>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('pm_export_action', 'pm_export_nonce'); ?>
                        <input type="hidden" name="action" value="pm_export_publications" />

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="pm_export_format"><?php _e('Export Format', 'publications-manager'); ?></label>
                                </th>
                                <td>
                                    <select name="pm_export_format" id="pm_export_format" class="regular-text">
                                        <option value="bibtex"><?php _e('BibTeX (.bib)', 'publications-manager'); ?></option>
                                        <option value="csv"><?php _e('CSV (.csv)', 'publications-manager'); ?></option>
                                        <option value="json"><?php _e('JSON (.json)', 'publications-manager'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pm_export_type"><?php _e('Publication Type', 'publications-manager'); ?></label>
                                </th>
                                <td>
                                    <select name="pm_export_type" id="pm_export_type" class="regular-text">
                                        <option value=""><?php _e('All Types', 'publications-manager'); ?></option>
                                        <?php echo PM_Publication_Types::get_options(); ?>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-secondary">
                                <?php _e('Export Publications', 'publications-manager'); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Statistics -->
                <div class="pm-section pm-stats-section">
                    <h2><?php _e('Statistics', 'publications-manager'); ?></h2>
                    <?php self::render_statistics(); ?>
                </div>

            </div>
        </div>

        <style>
            .pm-import-export-container {
                max-width: 900px;
            }

            .pm-section {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .pm-section h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            #pm-import-results {
                margin-top: 20px;
            }

            .pm-result-item {
                padding: 10px;
                margin: 5px 0;
                border-left: 4px solid #46b450;
                background: #f7f7f7;
            }

            .pm-result-item.error {
                border-left-color: #dc3232;
            }

            .pm-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }

            .pm-stat-box {
                background: #f7f7f7;
                padding: 15px;
                border-left: 4px solid #2271b1;
                text-align: center;
            }

            .pm-stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #2271b1;
            }

            .pm-stat-label {
                color: #666;
                margin-top: 5px;
            }

            #pm-import-spinner {
                float: none;
                margin-left: 10px;
            }
        </style>
    <?php
    }

    /**
     * Render statistics
     */
    private static function render_statistics()
    {
        $total_pubs = wp_count_posts('publication');
        $total_count = $total_pubs->publish;

        // Count by type
        $types = array();
        $all_types = PM_Publication_Types::get_all();

        foreach ($all_types as $type_slug => $type_data) {
            $args = array(
                'post_type'      => 'publication',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => 'pm_type',
                        'value'   => $type_slug,
                        'compare' => '='
                    )
                ),
                'fields'         => 'ids'
            );

            $query = new WP_Query($args);
            if ($query->found_posts > 0) {
                $types[$type_data['i18n_singular']] = $query->found_posts;
            }
        }

        arsort($types);

    ?>
        <div class="pm-stats-grid">
            <div class="pm-stat-box">
                <div class="pm-stat-number"><?php echo number_format_i18n($total_count); ?></div>
                <div class="pm-stat-label"><?php _e('Total Publications', 'publications-manager'); ?></div>
            </div>

            <?php foreach (array_slice($types, 0, 5) as $type_name => $count) : ?>
                <div class="pm-stat-box">
                    <div class="pm-stat-number"><?php echo number_format_i18n($count); ?></div>
                    <div class="pm-stat-label"><?php echo esc_html($type_name); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($types) > 5) : ?>
            <details style="margin-top: 15px;">
                <summary style="cursor: pointer;"><?php _e('Show all publication types', 'publications-manager'); ?></summary>
                <ul style="margin-top: 10px;">
                    <?php foreach ($types as $type_name => $count) : ?>
                        <li><?php echo esc_html($type_name); ?>: <strong><?php echo number_format_i18n($count); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    <?php
    }

    /**
     * AJAX handler for DOI import
     */
    public static function ajax_import_doi()
    {
        check_ajax_referer('pm-import-nonce', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'publications-manager')));
        }

        $doi_input = isset($_POST['doi_input']) ? sanitize_textarea_field($_POST['doi_input']) : '';

        if (empty($doi_input)) {
            wp_send_json_error(array('message' => __('Please enter at least one DOI', 'publications-manager')));
        }

        // Import from Crossref
        $results = PM_Crossref_Import::import_from_doi($doi_input);

        if ($results['success']) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error($results);
        }
    }

    /**
     * Register settings
     */
    public static function register_settings()
    {
        register_setting('pm_settings', 'pm_team_cpt_slug', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default' => 'team_member'
        ));
    }

    /**
     * Render settings page
     */
    public static function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';

        // Handle cleanup orphaned connections action
        if ($current_tab === 'debug' && isset($_GET['action']) && $_GET['action'] === 'cleanup_orphaned') {
            check_admin_referer('pm_cleanup_orphaned');
            $cleaned = self::cleanup_orphaned_connections();
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Successfully cleaned up %d orphaned connections.', 'publications-manager'), $cleaned) . '</p></div>';
        }

        // Handle bulk process action
        $bulk_process_results = null;
        if ($current_tab === 'bulk-process' && isset($_GET['action']) && $_GET['action'] === 'process') {
            check_admin_referer('pm_bulk_process');
            $bulk_process_results = self::process_all_publications();
        }

        // Save settings if form submitted
        if (isset($_POST['pm_settings_submit'])) {
            check_admin_referer('pm_settings_action', 'pm_settings_nonce');

            $team_cpt_slug = isset($_POST['pm_team_cpt_slug']) ? sanitize_key($_POST['pm_team_cpt_slug']) : 'team_member';
            update_option('pm_team_cpt_slug', $team_cpt_slug);

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'publications-manager') . '</p></div>';
        }

        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="?post_type=publication&page=pm-settings&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Settings', 'publications-manager'); ?>
                </a>
                <a href="?post_type=publication&page=pm-settings&tab=bulk-process" class="nav-tab <?php echo $current_tab === 'bulk-process' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Bulk Process', 'publications-manager'); ?>
                </a>
                <a href="?post_type=publication&page=pm-settings&tab=debug" class="nav-tab <?php echo $current_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Analytics', 'publications-manager'); ?>
                </a>
            </h2>

            <div class="pm-tab-content" style="margin-top: 20px;">
                <?php
                switch ($current_tab) {
                    case 'settings':
                        self::render_settings_tab($team_cpt_slug);
                        break;
                    case 'bulk-process':
                        self::render_bulk_process_tab($bulk_process_results);
                        break;
                    case 'debug':
                        self::render_debug_tab($team_cpt_slug);
                        break;
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Render settings tab
     */
    private static function render_settings_tab($team_cpt_slug)
    {
    ?>
        <form method="post" action="">
            <?php wp_nonce_field('pm_settings_action', 'pm_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pm_team_cpt_slug"><?php _e('Team CPT Slug', 'publications-manager'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="pm_team_cpt_slug"
                            id="pm_team_cpt_slug"
                            value="<?php echo esc_attr($team_cpt_slug); ?>"
                            class="regular-text" />
                        <p class="description">
                            <?php _e('Enter the slug of the Custom Post Type that contains team member profiles (e.g., team_member). Publications will be automatically linked to team members based on author names matching the team member post titles.', 'publications-manager'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input
                    type="submit"
                    name="pm_settings_submit"
                    class="button button-primary"
                    value="<?php esc_attr_e('Save Settings', 'publications-manager'); ?>" />
            </p>
        </form>

        <hr>

        <h2><?php _e('Author Linking Information', 'publications-manager'); ?></h2>
        <div class="notice notice-info inline">
            <p><strong><?php _e('How it works:', 'publications-manager'); ?></strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php _e('Authors in publications should be formatted as "GivenName FamilyName" separated by commas.', 'publications-manager'); ?></li>
                <li><?php _e('Example: "John Doe, Jane Smith, Bob Lee"', 'publications-manager'); ?></li>
                <li><?php _e('The plugin will automatically match author names with team member post titles or name variations.', 'publications-manager'); ?></li>
                <li><?php _e('When a match is found, a bidirectional relationship is created for Bricks Builder Query Loops.', 'publications-manager'); ?></li>
                <li><?php _e('On the frontend, matched authors will be displayed as clickable links to their team member pages.', 'publications-manager'); ?></li>
            </ul>
        </div>

        <div class="notice notice-warning inline" style="margin-top: 15px;">
            <p><strong><?php _e('📝 Setting Up Name Variations for Team Members', 'publications-manager'); ?></strong></p>
            <p><?php _e('Authors often appear in different formats across publications (e.g., "John Smith", "J. Smith", "John A. Smith").', 'publications-manager'); ?></p>
            <p><?php _e('To ensure accurate matching:', 'publications-manager'); ?></p>
            <ol style="margin-left: 20px;">
                <li><?php printf(__('Edit each team member in <strong>%s</strong>', 'publications-manager'), esc_html($team_cpt_slug)); ?></li>
                <li><?php _e('Find the <strong>"Publication Name Variations"</strong> meta box', 'publications-manager'); ?></li>
                <li><?php _e('Enter all possible name variations, separated by commas', 'publications-manager'); ?></li>
                <li><?php _e('Example: <code>John Smith, J. Smith, John A. Smith</code>', 'publications-manager'); ?></li>
                <li><?php _e('The matching system will check all variations when linking publications', 'publications-manager'); ?></li>
            </ol>
            <p style="margin-top: 10px;"><em><?php _e('💡 Tip: If no variations are set, the plugin will fallback to matching the team member post title.', 'publications-manager'); ?></em></p>
        </div>

        <h2><?php _e('How to Use in Bricks Builder', 'publications-manager'); ?></h2>
        <div class="notice notice-info inline">
            <p><strong><?php _e('On Team Member Page - Query Publications:', 'publications-manager'); ?></strong></p>
            <ol>
                <li><?php _e('Add Query Loop element', 'publications-manager'); ?></li>
                <li><?php _e('Query Type: Posts', 'publications-manager'); ?></li>
                <li><?php _e('Post Type: Publication', 'publications-manager'); ?></li>
                <li><?php _e('The plugin will automatically filter to show only this member\'s publications', 'publications-manager'); ?></li>
            </ol>

            <p><strong><?php _e('Alternative - Manual Meta Query:', 'publications-manager'); ?></strong></p>
            <ol>
                <li><?php _e('Meta Query → Key:', 'publications-manager'); ?> <code>pm_publication_id</code></li>
                <li><?php _e('Value:', 'publications-manager'); ?> <code>{post_id}</code></li>
                <li><?php _e('Compare: = (equals)', 'publications-manager'); ?></li>
            </ol>

            <p><strong><?php _e('Display Authors with Links:', 'publications-manager'); ?></strong></p>
            <p><?php _e('Use dynamic data:', 'publications-manager'); ?> <code>{cf_pm_author}</code></p>
        </div>
    <?php
    }

    /**
     * Render bulk process tab
     */
    private static function render_bulk_process_tab($results = null)
    {
        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
        $pub_count = wp_count_posts('publication');
        $team_count = post_type_exists($team_cpt_slug) ? wp_count_posts($team_cpt_slug) : null;
    ?>
        <div class="pm-bulk-process">
            <?php if ($results): ?>
                <!-- Results Section -->
                <div class="notice notice-success">
                    <h2><?php _e('✅ Processing Complete!', 'publications-manager'); ?></h2>
                    <p><strong style="font-size: 24px; color: #2271b1;"><?php echo $results['processed']; ?> / <?php echo $results['total']; ?></strong></p>
                    <p><?php _e('Publications processed successfully.', 'publications-manager'); ?></p>
                    <p><strong><?php _e('Publications with team member links:', 'publications-manager'); ?></strong> <?php echo $results['linked']; ?></p>
                </div>

                <?php if (!empty($results['details'])): ?>
                    <h2><?php _e('Results', 'publications-manager'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'publications-manager'); ?></th>
                                <th><?php _e('Publication Title', 'publications-manager'); ?></th>
                                <th><?php _e('Authors', 'publications-manager'); ?></th>
                                <th><?php _e('Links Before', 'publications-manager'); ?></th>
                                <th><?php _e('Links After', 'publications-manager'); ?></th>
                                <th><?php _e('New Links', 'publications-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['details'] as $detail): ?>
                                <tr <?php echo $detail['new_links'] > 0 ? 'style="background:#d7f0d8;"' : ''; ?>>
                                    <td><?php echo $detail['id']; ?></td>
                                    <td><?php echo esc_html($detail['title']); ?></td>
                                    <td><?php echo esc_html(substr($detail['authors'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo $detail['links_before']; ?></td>
                                    <td><?php echo $detail['links_after']; ?></td>
                                    <td><strong><?php echo ($detail['new_links'] > 0 ? '+' : '') . $detail['new_links']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php else: ?>
                <!-- Action Button -->
                <?php if (!post_type_exists($team_cpt_slug) || ($team_count && $team_count->publish == 0)): ?>
                    <div class="notice notice-warning">
                        <h2><?php _e('⚠️ Warning', 'publications-manager'); ?></h2>
                        <p><?php _e('No team members found. Please check:', 'publications-manager'); ?></p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <li><?php _e('The Team CPT Slug is correct in Settings', 'publications-manager'); ?></li>
                            <li><?php _e('You have published team member posts', 'publications-manager'); ?></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=publication&page=pm-settings&tab=bulk-process&action=process'), 'pm_bulk_process'); ?>" class="button button-primary button-hero">
                            <?php _e('Start Processing Publications', 'publications-manager'); ?>
                        </a>
                    </p>
                    <p class="description">
                        <?php printf(__('This will process %d publications and link them to %d team members.', 'publications-manager'), $pub_count->publish, $team_count ? $team_count->publish : 0); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <hr style="margin: 30px 0;">

            <!-- Informational Content -->
            <h2><?php _e('About This Tool', 'publications-manager'); ?></h2>
            <div class="notice notice-info inline">
                <p><?php _e('This utility will process all existing publications and create relationships with team members based on author names.', 'publications-manager'); ?></p>
                <p><strong><?php _e('What it does:', 'publications-manager'); ?></strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Parses author names from publications (comma-separated format)', 'publications-manager'); ?></li>
                    <li><?php _e('Searches for matching team member post titles', 'publications-manager'); ?></li>
                    <li><?php _e('Creates bidirectional relationships', 'publications-manager'); ?></li>
                    <li><?php _e('Stores link data for frontend display', 'publications-manager'); ?></li>
                </ul>
            </div>

            <div class="notice notice-info inline">
                <p><strong><?php _e('Before You Start:', 'publications-manager'); ?></strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Make sure your Team CPT Slug is configured correctly in Settings', 'publications-manager'); ?></li>
                    <li><?php _e('Ensure author names in publications match team member post titles exactly', 'publications-manager'); ?></li>
                    <li><?php _e('Author format should be: "John Doe, Jane Smith" (comma-separated)', 'publications-manager'); ?></li>
                    <li><?php _e('Backup your database (recommended for large sites)', 'publications-manager'); ?></li>
                </ul>
            </div>
        </div>
    <?php
    }

    /**
     * Render debug tab
     */
    private static function render_debug_tab($team_cpt_slug)
    {
        // Calculate statistics
        $total_publications = wp_count_posts('publication');
        $total_team_members = post_type_exists($team_cpt_slug) ? wp_count_posts($team_cpt_slug) : null;

        // Count publications with/without links
        $all_pubs = get_posts(array(
            'post_type' => 'publication',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));

        $pubs_with_links = 0;
        $pubs_without_links = 0;
        $total_connections = 0;

        foreach ($all_pubs as $pub_id) {
            $team_members = get_post_meta($pub_id, 'pm_team_members', true);
            if (is_array($team_members) && !empty($team_members)) {
                $pubs_with_links++;
                $total_connections += count($team_members);
            } else {
                $pubs_without_links++;
            }
        }

        // Get top authors with most publications
        $all_team_members = post_type_exists($team_cpt_slug) ? get_posts(array(
            'post_type' => $team_cpt_slug,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        )) : array();

        $author_stats = array();
        $duplicate_count = 0;
        $actual_total_connections = 0; // Count unique connections
        $orphaned_count = 0; // Count connections to deleted publications

        foreach ($all_team_members as $member) {
            $pub_ids = get_post_meta($member->ID, 'pm_publication_id', false);

            // Remove empty values and duplicates
            $pub_ids = array_filter($pub_ids);
            $total_entries = count($pub_ids);
            $unique_pub_ids = array_unique($pub_ids);

            // Validate that publications actually exist
            $valid_pub_ids = array();
            $orphaned_for_member = 0;
            foreach ($unique_pub_ids as $pub_id) {
                $post = get_post($pub_id);
                if ($post && $post->post_type === 'publication' && $post->post_status === 'publish') {
                    $valid_pub_ids[] = $pub_id;
                } else {
                    $orphaned_for_member++;
                    $orphaned_count++;
                }
            }

            $valid_count = count($valid_pub_ids);
            $unique_count = count($unique_pub_ids);

            $actual_total_connections += $valid_count;

            if ($total_entries > $unique_count) {
                $duplicate_count += ($total_entries - $unique_count);
            }

            if ($valid_count > 0 || $orphaned_for_member > 0) {
                $author_stats[] = array(
                    'name' => $member->post_title,
                    'count' => $valid_count,
                    'total_entries' => $total_entries,
                    'duplicates' => $total_entries - $unique_count,
                    'orphaned' => $orphaned_for_member,
                    'member_id' => $member->ID
                );
            }
        }

        usort($author_stats, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Recalculate average based on unique connections
        $avg_links_per_pub = $pubs_with_links > 0 ? round($actual_total_connections / $pubs_with_links, 1) : 0;

        $avg_links_per_pub = $pubs_with_links > 0 ? round($total_connections / $pubs_with_links, 1) : 0;
    ?>
        <style>
            .pm-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .pm-stat-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .pm-stat-number {
                font-size: 48px;
                font-weight: bold;
                color: #2271b1;
                line-height: 1;
                margin: 10px 0;
            }

            .pm-stat-label {
                color: #50575e;
                font-size: 14px;
                margin-top: 8px;
            }

            .pm-stat-card.success .pm-stat-number {
                color: #00a32a;
            }

            .pm-stat-card.warning .pm-stat-number {
                color: #dba617;
            }

            .pm-stat-card.error .pm-stat-number {
                color: #d63638;
            }

            .pm-top-authors {
                background: #fff;
                border: 1px solid #c3c4c7;
                padding: 0;
                margin: 20px 0;
            }

            .pm-top-authors h3 {
                margin: 0;
                padding: 15px 20px;
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
            }

            .pm-author-list {
                padding: 15px 20px;
            }

            .pm-author-item {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f1;
            }

            .pm-author-item:last-child {
                border-bottom: none;
            }

            .pm-author-name {
                font-weight: 500;
            }

            .pm-author-count {
                color: #2271b1;
                font-weight: bold;
            }

            .pm-status-indicator {
                display: inline-block;
                width: 12px;
                height: 12px;
                border-radius: 50%;
                margin-right: 8px;
            }

            .pm-status-indicator.active {
                background: #00a32a;
            }

            .pm-status-indicator.inactive {
                background: #d63638;
            }
        </style>

        <div class="pm-analytics">
            <h2><?php _e('Connection Statistics', 'publications-manager'); ?></h2>

            <div class="pm-stats-grid">
                <div class="pm-stat-card">
                    <div class="pm-stat-label"><?php _e('Total Publications', 'publications-manager'); ?></div>
                    <div class="pm-stat-number"><?php echo number_format_i18n($total_publications->publish); ?></div>
                </div>

                <div class="pm-stat-card success">
                    <div class="pm-stat-label"><?php _e('With Team Links', 'publications-manager'); ?></div>
                    <div class="pm-stat-number"><?php echo number_format_i18n($pubs_with_links); ?></div>
                    <div class="pm-stat-label"><?php echo $total_publications->publish > 0 ? round(($pubs_with_links / $total_publications->publish) * 100) . '%' : '0%'; ?></div>
                </div>

                <div class="pm-stat-card <?php echo $pubs_without_links > 0 ? 'warning' : ''; ?>">
                    <div class="pm-stat-label"><?php _e('Without Links', 'publications-manager'); ?></div>
                    <div class="pm-stat-number"><?php echo number_format_i18n($pubs_without_links); ?></div>
                    <div class="pm-stat-label"><?php echo $total_publications->publish > 0 ? round(($pubs_without_links / $total_publications->publish) * 100) . '%' : '0%'; ?></div>
                </div>

                <div class="pm-stat-card">
                    <div class="pm-stat-label"><?php _e('Total Connections', 'publications-manager'); ?></div>
                    <div class="pm-stat-number"><?php echo number_format_i18n($actual_total_connections); ?></div>
                    <?php if ($duplicate_count > 0): ?>
                        <div class="pm-stat-label" style="color: #d63638; font-size: 12px;">
                            <?php printf(__('(%d with duplicates)', 'publications-manager'), $total_connections); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pm-stat-card">
                    <div class="pm-stat-label"><?php _e('Avg Links/Publication', 'publications-manager'); ?></div>
                    <div class="pm-stat-number"><?php echo $avg_links_per_pub; ?></div>
                </div>

                <div class="pm-stat-card">
                    <div class="pm-stat-label"><?php _e('Team Members', 'publications-manager'); ?></div>
                    <div class="pm-stat-number"><?php echo $total_team_members ? number_format_i18n($total_team_members->publish) : 0; ?></div>
                </div>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Team CPT Status:', 'publications-manager'); ?></th>
                    <td>
                        <span class="pm-status-indicator <?php echo post_type_exists($team_cpt_slug) ? 'active' : 'inactive'; ?>"></span>
                        <?php echo esc_html($team_cpt_slug); ?>
                        <?php echo post_type_exists($team_cpt_slug) ? ' (' . __('Active', 'publications-manager') . ')' : ' (' . __('Not Found', 'publications-manager') . ')'; ?>
                    </td>
                </tr>
            </table>

            <?php if (!empty($author_stats)): ?>
                <div class="pm-top-authors">
                    <h3><?php _e('Top Authors by Publication Count', 'publications-manager'); ?></h3>
                    <div class="pm-author-list">
                        <?php foreach (array_slice($author_stats, 0, 10) as $author): ?>
                            <div class="pm-author-item">
                                <span class="pm-author-name">
                                    <?php echo esc_html($author['name']); ?>
                                    <?php if (isset($author['orphaned']) && $author['orphaned'] > 0): ?>
                                        <span style="color: #d63638; font-size: 12px; font-weight: normal;">
                                            (<?php printf(__('%d deleted publications', 'publications-manager'), $author['orphaned']); ?>)
                                        </span>
                                    <?php elseif (isset($author['duplicates']) && $author['duplicates'] > 0): ?>
                                        <span style="color: #d63638; font-size: 12px; font-weight: normal;">
                                            (<?php printf(__('%d total, %d duplicates', 'publications-manager'), $author['total_entries'], $author['duplicates']); ?>)
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <span class="pm-author-count"><?php echo number_format_i18n($author['count']); ?> <?php echo _n('publication', 'publications', $author['count'], 'publications-manager'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($orphaned_count > 0): ?>
                <div class="notice notice-error inline" style="margin-top: 20px;">
                    <p><strong><?php _e('Orphaned Connections Detected:', 'publications-manager'); ?></strong></p>
                    <p><?php printf(__('Found %d connections to deleted publications. These are references to publications that no longer exist in your database.', 'publications-manager'), $orphaned_count); ?></p>
                    <p>
                        <strong><?php _e('Solution:', 'publications-manager'); ?></strong>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=publication&page=pm-settings&tab=debug&action=cleanup_orphaned'), 'pm_cleanup_orphaned'); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('This will remove all connections to deleted publications. Continue?', 'publications-manager'); ?>');">
                            <?php _e('Clean Up Orphaned Connections', 'publications-manager'); ?>
                        </a>
                    </p>
                </div>
            <?php elseif ($duplicate_count > 0): ?>
                <div class="notice notice-error inline" style="margin-top: 20px;">
                    <p><strong><?php _e('Data Integrity Issue Detected:', 'publications-manager'); ?></strong></p>
                    <p><?php printf(__('Found %d duplicate meta entries. This happens when publications are processed multiple times. The counts above show unique publications only, but your database has duplicate entries.', 'publications-manager'), $duplicate_count); ?></p>
                    <p><strong><?php _e('Solution:', 'publications-manager'); ?></strong> <?php _e('Go to the Bulk Process tab and click "Start Processing Publications" to clean up and rebuild all relationships.', 'publications-manager'); ?></p>
                </div>
            <?php elseif ($pubs_without_links > 0): ?>
                <div class="notice notice-warning inline" style="margin-top: 20px;">
                    <p><strong><?php _e('Action Required:', 'publications-manager'); ?></strong></p>
                    <p><?php printf(__('You have %d publications without team member links.', 'publications-manager'), $pubs_without_links); ?></p>
                    <p>
                        <a href="<?php echo admin_url('edit.php?post_type=publication&page=pm-settings&tab=bulk-process'); ?>" class="button button-primary">
                            <?php _e('Go to Bulk Process', 'publications-manager'); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-success inline" style="margin-top: 20px;">
                    <p><strong><?php _e('Great!', 'publications-manager'); ?></strong> <?php _e('All publications are linked to team members.', 'publications-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>
<?php
    }

    /**
     * Process all publications and create relationships
     */
    private static function process_all_publications()
    {
        // Check if the function exists
        if (!function_exists('pm_process_author_relationships')) {
            return array(
                'total' => 0,
                'processed' => 0,
                'linked' => 0,
                'details' => array(),
                'error' => 'Function pm_process_author_relationships not found'
            );
        }

        $publications = get_posts(array(
            'post_type' => 'publication',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        $total = count($publications);
        $processed = 0;
        $linked = 0;
        $details = array();

        foreach ($publications as $publication) {
            $before_links = get_post_meta($publication->ID, 'pm_team_members', true);
            $before_count = is_array($before_links) ? count($before_links) : 0;

            // Process relationships
            pm_process_author_relationships($publication->ID);

            $after_links = get_post_meta($publication->ID, 'pm_team_members', true);
            $after_count = is_array($after_links) ? count($after_links) : 0;

            $authors = get_post_meta($publication->ID, 'pm_author', true);

            $details[] = array(
                'id' => $publication->ID,
                'title' => $publication->post_title,
                'authors' => $authors,
                'links_before' => $before_count,
                'links_after' => $after_count,
                'new_links' => $after_count - $before_count
            );

            $processed++;
            if ($after_count > 0) {
                $linked++;
            }
        }

        return array(
            'total' => $total,
            'processed' => $processed,
            'linked' => $linked,
            'details' => $details
        );
    }

    /**
     * Clean up orphaned connections (team member meta pointing to deleted publications)
     */
    private static function cleanup_orphaned_connections()
    {
        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        if (!post_type_exists($team_cpt_slug)) {
            return 0;
        }

        $all_team_members = get_posts(array(
            'post_type' => $team_cpt_slug,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));

        $cleaned_count = 0;

        foreach ($all_team_members as $member_id) {
            $pub_ids = get_post_meta($member_id, 'pm_publication_id', false);

            foreach ($pub_ids as $pub_id) {
                $post = get_post($pub_id);

                // If publication doesn't exist or is not published, remove the meta
                if (!$post || $post->post_type !== 'publication' || $post->post_status !== 'publish') {
                    // Delete the specific pm_publication_id entry
                    delete_post_meta($member_id, 'pm_publication_id', $pub_id);

                    // Delete the corresponding pm_publication_{id} entry
                    delete_post_meta($member_id, 'pm_publication_' . $pub_id);

                    $cleaned_count++;
                }
            }
        }

        return $cleaned_count;
    }

    /**
     * Clean up team member connections when a publication is deleted
     */
    public static function cleanup_publication_connections($post_id)
    {
        // Only proceed if this is a publication post type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'publication') {
            return;
        }

        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        if (!post_type_exists($team_cpt_slug)) {
            return;
        }

        // Get team members linked to this publication
        $team_members = get_post_meta($post_id, 'pm_team_members', true);

        if (is_array($team_members) && !empty($team_members)) {
            foreach ($team_members as $member_id) {
                // Delete the pm_publication_id entry
                delete_post_meta($member_id, 'pm_publication_id', $post_id);

                // Delete the pm_publication_{id} entry
                delete_post_meta($member_id, 'pm_publication_' . $post_id);
            }
        }

        // Also clean up any orphaned connections (in case the team_members array is outdated)
        // Search all team members for this publication ID
        $all_team_members = get_posts(array(
            'post_type' => $team_cpt_slug,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'pm_publication_id',
                    'value' => $post_id,
                    'compare' => '='
                )
            )
        ));

        foreach ($all_team_members as $member_id) {
            delete_post_meta($member_id, 'pm_publication_id', $post_id);
            delete_post_meta($member_id, 'pm_publication_' . $post_id);
        }
    }
}
