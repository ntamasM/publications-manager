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
        add_action('wp_ajax_pm_import_doi', array(__CLASS__, 'ajax_import_doi'));
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
}
