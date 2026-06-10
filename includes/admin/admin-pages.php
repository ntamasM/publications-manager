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
        add_action('admin_post_pm_export_publications', array(__CLASS__, 'handle_export_publications'));
        add_action('admin_post_pm_import_file', array(__CLASS__, 'handle_import_file'));

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
            __('Tools', 'publications-manager'),
            __('Tools', 'publications-manager'),
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

            <?php self::render_import_file_notice(); ?>

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

                <!-- Import from File Section -->
                <div class="pm-section pm-import-file-section">
                    <h2><?php _e('Import from File', 'publications-manager'); ?></h2>
                    <p class="description">
                        <?php _e('Import publications from a file exported by this plugin. Supported formats: JSON, CSV, and BibTeX. Existing publications are matched by DOI, then BibTeX key, then slug &mdash; matches are updated, everything else is created.', 'publications-manager'); ?>
                    </p>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('pm_import_file_action', 'pm_import_file_nonce'); ?>
                        <input type="hidden" name="action" value="pm_import_file" />

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="pm_import_file"><?php _e('File', 'publications-manager'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="pm_import_file" id="pm_import_file" accept=".json,.csv,.bib,.bibtex" required />
                                    <p class="description">
                                        <?php _e('Accepted file types: .json, .csv, .bib, .bibtex (max 5 MB).', 'publications-manager'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Import File', 'publications-manager'); ?>
                            </button>
                        </p>
                    </form>
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
                                        <option value="txt"><?php _e('DOIs (.txt, one per line)', 'publications-manager'); ?></option>
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

                        <details id="pm-export-custom-fields" style="margin: 15px 0;">
                            <summary style="cursor: pointer; font-weight: 600; padding: 8px 0;">
                                <?php _e('Custom field selection (for "Export Publications (Custom)")', 'publications-manager'); ?>
                            </summary>
                            <p class="description" style="margin-top: 10px;">
                                <?php _e('Tick the fields you want to include when using the Custom export button. Ignored when using "All Fields".', 'publications-manager'); ?>
                            </p>
                            <p style="margin: 8px 0;">
                                <a href="#" id="pm-export-fields-all"><?php _e('Select all', 'publications-manager'); ?></a>
                                &nbsp;|&nbsp;
                                <a href="#" id="pm-export-fields-none"><?php _e('Select none', 'publications-manager'); ?></a>
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px; max-height: 360px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; background: #fafafa;">
                                <?php foreach (self::get_export_field_definitions() as $field_key => $field_label) : ?>
                                    <label style="display: block;">
                                        <input type="checkbox" name="pm_export_fields[]" value="<?php echo esc_attr($field_key); ?>" checked />
                                        <?php echo esc_html($field_label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </details>

                        <p class="submit">
                            <button type="submit" name="pm_export_mode" value="all" class="button button-secondary">
                                <?php _e('Export Publications (All Fields)', 'publications-manager'); ?>
                            </button>
                            &nbsp;
                            <button type="submit" name="pm_export_mode" value="custom" class="button button-primary">
                                <?php _e('Export Publications (Custom)', 'publications-manager'); ?>
                            </button>
                        </p>

                        <script>
                            (function() {
                                var root = document.getElementById('pm-export-custom-fields');
                                if (!root) return;
                                var boxes = root.querySelectorAll('input[name="pm_export_fields[]"]');
                                var all = document.getElementById('pm-export-fields-all');
                                var none = document.getElementById('pm-export-fields-none');
                                if (all) all.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    boxes.forEach(function(b) { b.checked = true; });
                                });
                                if (none) none.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    boxes.forEach(function(b) { b.checked = false; });
                                });
                            })();
                        </script>
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
     * Handle the export form submission and stream a file download.
     */
    public static function handle_export_publications()
    {
        if (! isset($_POST['pm_export_nonce']) || ! wp_verify_nonce($_POST['pm_export_nonce'], 'pm_export_action')) {
            wp_die(__('Security check failed.', 'publications-manager'), '', array('response' => 400));
        }

        if (! current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'publications-manager'), '', array('response' => 403));
        }

        $format = isset($_POST['pm_export_format']) ? sanitize_key($_POST['pm_export_format']) : 'bibtex';
        $type   = isset($_POST['pm_export_type']) ? sanitize_key($_POST['pm_export_type']) : '';

        if (! in_array($format, array('bibtex', 'csv', 'json', 'txt'), true)) {
            wp_die(__('Invalid export format.', 'publications-manager'), '', array('response' => 400));
        }

        $mode = isset($_POST['pm_export_mode']) ? sanitize_key($_POST['pm_export_mode']) : 'all';
        if (! in_array($mode, array('all', 'custom'), true)) {
            $mode = 'all';
        }

        $allowed_fields = null;
        if ($mode === 'custom') {
            $valid_keys = array_keys(self::get_export_field_definitions());
            $posted     = isset($_POST['pm_export_fields']) && is_array($_POST['pm_export_fields'])
                ? array_map('sanitize_key', $_POST['pm_export_fields'])
                : array();
            $allowed_fields = array_values(array_intersect($valid_keys, $posted));

            if (empty($allowed_fields)) {
                wp_die(
                    __('Custom export requires at least one field to be selected.', 'publications-manager'),
                    '',
                    array('response' => 400)
                );
            }
        }

        $query_args = array(
            'post_type'      => 'publication',
            'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if (! empty($type)) {
            $query_args['meta_query'] = array(
                array(
                    'key'   => 'pm_type',
                    'value' => $type,
                ),
            );
        }

        $posts = get_posts($query_args);

        $records = array();
        foreach ($posts as $post) {
            $records[] = self::build_export_record($post);
        }

        $timestamp = gmdate('Ymd-His');
        $type_part = $type ? '-' . $type : '';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();

        switch ($format) {
            case 'bibtex':
                $filename = "publications{$type_part}-{$timestamp}.bib";
                header('Content-Type: application/x-bibtex; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo self::render_bibtex($records, $allowed_fields);
                break;

            case 'csv':
                $filename = "publications{$type_part}-{$timestamp}.csv";
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
                self::render_csv($records, $allowed_fields);
                break;

            case 'json':
                $filename = "publications{$type_part}-{$timestamp}.json";
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo wp_json_encode(self::filter_records_for_json($records, $allowed_fields), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;

            case 'txt':
                $filename = "publication-dois{$type_part}-{$timestamp}.txt";
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo self::render_txt_dois($records);
                break;
        }

        exit;
    }

    /**
     * Handle an uploaded import file: validate, parse, create/update, then redirect
     * back to the import/export page with result counts.
     */
    public static function handle_import_file()
    {
        if (! isset($_POST['pm_import_file_nonce']) || ! wp_verify_nonce($_POST['pm_import_file_nonce'], 'pm_import_file_action')) {
            wp_die(__('Security check failed.', 'publications-manager'), '', array('response' => 400));
        }

        if (! current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'publications-manager'), '', array('response' => 403));
        }

        $redirect = admin_url('edit.php?post_type=publication&page=pm-import-export');

        // Basic upload validation.
        if (empty($_FILES['pm_import_file']) || ! isset($_FILES['pm_import_file']['tmp_name']) || $_FILES['pm_import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_safe_redirect(add_query_arg('pm_import_error', 'upload', $redirect));
            exit;
        }

        $file = $_FILES['pm_import_file'];

        // Size guard: 5 MB.
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_safe_redirect(add_query_arg('pm_import_error', 'size', $redirect));
            exit;
        }

        // Determine format from the extension.
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $format_map = array(
            'json'   => 'json',
            'csv'    => 'csv',
            'bib'    => 'bibtex',
            'bibtex' => 'bibtex',
        );

        if (! isset($format_map[$ext])) {
            wp_safe_redirect(add_query_arg('pm_import_error', 'type', $redirect));
            exit;
        }

        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false || $contents === '') {
            wp_safe_redirect(add_query_arg('pm_import_error', 'empty', $redirect));
            exit;
        }

        $results = PM_File_Import::import_from_file($contents, $format_map[$ext]);

        if (empty($results['success'])) {
            wp_safe_redirect(add_query_arg('pm_import_error', 'parse', $redirect));
            exit;
        }

        $created = 0;
        $updated = 0;
        foreach ($results['imported'] as $item) {
            if (isset($item['action']) && $item['action'] === 'updated') {
                $updated++;
            } else {
                $created++;
            }
        }

        $redirect = add_query_arg(array(
            'pm_import_created' => $created,
            'pm_import_updated' => $updated,
            'pm_import_failed'  => count($results['failed']),
        ), $redirect);

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Render the success/error notice for a file import, based on redirect query args.
     */
    private static function render_import_file_notice()
    {
        if (isset($_GET['pm_import_error'])) {
            $errors = array(
                'upload' => __('File upload failed. Please try again.', 'publications-manager'),
                'size'   => __('The file is too large (max 5 MB).', 'publications-manager'),
                'type'   => __('Unsupported file type. Use .json, .csv, .bib or .bibtex.', 'publications-manager'),
                'empty'  => __('The uploaded file was empty.', 'publications-manager'),
                'parse'  => __('Could not read any publications from the file.', 'publications-manager'),
            );
            $key = sanitize_key($_GET['pm_import_error']);
            $message = isset($errors[$key]) ? $errors[$key] : __('Import failed.', 'publications-manager');
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
            return;
        }

        if (isset($_GET['pm_import_created']) || isset($_GET['pm_import_updated'])) {
            $created = isset($_GET['pm_import_created']) ? absint($_GET['pm_import_created']) : 0;
            $updated = isset($_GET['pm_import_updated']) ? absint($_GET['pm_import_updated']) : 0;
            $failed  = isset($_GET['pm_import_failed']) ? absint($_GET['pm_import_failed']) : 0;

            $class = $failed > 0 ? 'notice-warning' : 'notice-success';
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . sprintf(
                __('Import complete: %1$d created, %2$d updated, %3$d failed.', 'publications-manager'),
                $created,
                $updated,
                $failed
            ) . '</p></div>';
        }
    }

    /**
     * Field keys + human labels exposed in the custom-export UI.
     */
    private static function get_export_field_definitions()
    {
        return PM_Fields::get_field_labels();
    }

    /**
     * Meta fields exported for each publication.
     * Public so the file importer can invert the same field set (import/export can't drift).
     */
    public static function get_export_meta_fields()
    {
        return PM_Fields::get_editable_meta_keys();
    }

    /**
     * Build a normalized record for a single publication post.
     */
    private static function build_export_record($post)
    {
        $record = array(
            'id'    => (int) $post->ID,
            'slug'  => $post->post_name,
            'title' => $post->post_title,
        );

        $author_terms = get_the_terms($post->ID, 'pm_author');
        $authors = array();
        if ($author_terms && ! is_wp_error($author_terms)) {
            foreach ($author_terms as $term) {
                $authors[] = $term->name;
            }
        }
        $record['authors'] = $authors;

        // pm_year is derived (not in the editable-meta set), so capture it explicitly.
        $year = get_post_meta($post->ID, 'pm_year', true);
        $record['year'] = is_string($year) ? $year : '';

        foreach (self::get_export_meta_fields() as $meta_key) {
            $value = get_post_meta($post->ID, $meta_key, true);
            $key = preg_replace('/^pm_/', '', $meta_key);
            $record[$key] = is_string($value) ? $value : '';
        }

        return $record;
    }

    /**
     * Render an array of records as a BibTeX document.
     */
    private static function render_bibtex(array $records, $allowed_fields = null)
    {
        $field_map = PM_Fields::get_bibtex_field_map();

        $out = '';
        $is_allowed = function ($key) use ($allowed_fields) {
            return $allowed_fields === null || in_array($key, $allowed_fields, true);
        };
        foreach ($records as $record) {
            $type_slug = isset($record['type']) ? $record['type'] : '';
            $type_def = $type_slug ? PM_Publication_Types::get($type_slug) : null;
            $entry_type = ($type_def && ! empty($type_def['bibtex_key_ext'])) ? $type_def['bibtex_key_ext'] : 'misc';
            $cite_key = !empty($record['bibtex']) ? $record['bibtex'] : ($record['slug'] !== '' ? $record['slug'] : ('pub-' . $record['id']));

            $out .= '@' . $entry_type . '{' . $cite_key . ",\n";

            $lines = array();
            if ($is_allowed('title')) {
                $lines[] = self::bibtex_line('title', $record['title']);
            }

            if ($is_allowed('authors') && ! empty($record['authors'])) {
                $lines[] = self::bibtex_line('author', implode(' and ', $record['authors']));
            }

            if ($is_allowed('date') && ! empty($record['date'])) {
                $ts = strtotime($record['date']);
                if ($ts) {
                    $months = array('', 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
                    $m = (int) gmdate('n', $ts);
                    if ($m >= 1 && $m <= 12) {
                        $lines[] = '  month = ' . $months[$m];
                    }
                }
            }

            foreach ($field_map as $rec_key => $bib_field) {
                if (! $is_allowed($rec_key)) {
                    continue;
                }
                if (! isset($record[$rec_key]) || $record[$rec_key] === '') {
                    continue;
                }
                $lines[] = self::bibtex_line($bib_field, $record[$rec_key]);
            }

            $lines = array_filter($lines);
            $out .= implode(",\n", $lines) . "\n";
            $out .= "}\n\n";
        }

        return $out;
    }

    /**
     * Format a single BibTeX field line.
     */
    private static function bibtex_line($field, $value)
    {
        if ($value === '' || $value === null) {
            return '';
        }
        $value = (string) $value;
        // Strip HTML, normalize whitespace, escape braces and backslashes.
        $value = wp_strip_all_tags($value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);
        $value = str_replace(array('\\', '{', '}'), array('\\\\', '\\{', '\\}'), $value);
        return '  ' . $field . ' = {' . $value . '}';
    }

    /**
     * Stream records as CSV to the output buffer.
     */
    private static function render_csv(array $records, $allowed_fields = null)
    {
        $base_columns = PM_Fields::get_record_keys();

        if ($allowed_fields === null) {
            $columns = $base_columns;
        } else {
            $columns = array_values(array_unique(array_merge(
                array('id', 'slug'),
                array_intersect($base_columns, $allowed_fields)
            )));
        }

        $fh = fopen('php://output', 'w');
        fputcsv($fh, $columns);

        foreach ($records as $record) {
            $row = array();
            foreach ($columns as $col) {
                if ($col === 'authors') {
                    $row[] = isset($record['authors']) ? implode('; ', $record['authors']) : '';
                } else {
                    $row[] = isset($record[$col]) ? $record[$col] : '';
                }
            }
            fputcsv($fh, $row);
        }

        fclose($fh);
    }

    /**
     * Render records as a plain-text list of DOIs, one per line.
     * Records without a DOI are skipped.
     */
    private static function render_txt_dois(array $records)
    {
        $lines = array();
        foreach ($records as $record) {
            if (! empty($record['doi'])) {
                $lines[] = $record['doi'];
            }
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Filter records down to a whitelist of fields, preserving id + slug.
     */
    private static function filter_records_for_json(array $records, $allowed_fields)
    {
        if ($allowed_fields === null) {
            return $records;
        }
        $keep = array_flip(array_merge(array('id', 'slug'), $allowed_fields));
        $out = array();
        foreach ($records as $record) {
            $out[] = array_intersect_key($record, $keep);
        }
        return $out;
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

        // Save settings if form submitted
        if (isset($_POST['pm_settings_submit'])) {
            check_admin_referer('pm_settings_action', 'pm_settings_nonce');

            $team_cpt_slug = isset($_POST['pm_team_cpt_slug']) ? sanitize_key($_POST['pm_team_cpt_slug']) : 'team_member';
            update_option('pm_team_cpt_slug', $team_cpt_slug);

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'publications-manager') . '</p></div>';
        }

        // Re-sync cached author -> team member URLs (e.g. after a Team CPT slug change)
        if (isset($_POST['pm_resync_author_urls'])) {
            check_admin_referer('pm_resync_urls_action', 'pm_resync_urls_nonce');

            $count = self::resync_author_team_urls();
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Re-synced %d author URLs.', 'publications-manager'), $count) . '</p></div>';
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

        <h2><?php _e('Author URL Maintenance', 'publications-manager'); ?></h2>
        <p class="description">
            <?php _e('If you changed the Team CPT (the slug setting above, or team-member permalinks), click this to re-link each author to the matching team member in the current Team CPT (by name) and refresh the cached URLs used by Bricks Builder.', 'publications-manager'); ?>
        </p>
        <form method="post" action="">
            <?php wp_nonce_field('pm_resync_urls_action', 'pm_resync_urls_nonce'); ?>
            <p class="submit">
                <input
                    type="submit"
                    name="pm_resync_author_urls"
                    class="button button-secondary"
                    value="<?php esc_attr_e('Re-sync Author URLs', 'publications-manager'); ?>" />
            </p>
        </form>

        <hr>

        <h2><?php _e('Author Linking Information', 'publications-manager'); ?></h2>
        <div class="notice notice-info inline">
            <p><strong><?php _e('How it works:', 'publications-manager'); ?></strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php _e('Authors are stored as taxonomy terms in the pm_author taxonomy', 'publications-manager'); ?></li>
                <li><?php _e('When adding publications, enter authors as: "GivenName FamilyName, GivenName FamilyName"', 'publications-manager'); ?></li>
                <li><?php _e('Example: "John Doe, Jane Smith, Bob Lee"', 'publications-manager'); ?></li>
                <li><?php _e('Each author becomes a reusable term in Publications → Authors', 'publications-manager'); ?></li>
                <li><?php _e('Link authors to team members manually via Publications → Authors admin', 'publications-manager'); ?></li>
                <li><?php _e('Once linked, author names display as clickable links to team member pages', 'publications-manager'); ?></li>
            </ul>
        </div>

        <div class="notice notice-warning inline" style="margin-top: 15px;">
            <p><strong><?php _e('📝 Linking Authors to Team Members', 'publications-manager'); ?></strong></p>
            <p><?php _e('After creating publications, link author terms to team member profiles:', 'publications-manager'); ?></p>
            <ol style="margin-left: 20px;">
                <li><?php _e('Go to <strong>Publications → Authors</strong>', 'publications-manager'); ?></li>
                <li><?php _e('Click "Edit" on an author term', 'publications-manager'); ?></li>
                <li><?php _e('Select the corresponding team member from the "Linked Team Member" dropdown', 'publications-manager'); ?></li>
                <li><?php _e('Save - the plugin automatically stores the team member URL', 'publications-manager'); ?></li>
            </ol>
            <p style="margin-top: 10px;"><em><?php _e('💡 Tip: Once linked, use {term_meta:pm_author_team_url} in Bricks Builder for author term URLs.', 'publications-manager'); ?></em></p>
        </div>

        <h2><?php _e('How to Use in Bricks Builder', 'publications-manager'); ?></h2>
        <div class="notice notice-info inline">
            <p><strong><?php _e('On Team Member Page - Query Publications:', 'publications-manager'); ?></strong></p>
            <ol>
                <li><?php _e('Add Query Loop element', 'publications-manager'); ?></li>
                <li><?php _e('Query Type: Posts', 'publications-manager'); ?></li>
                <li><?php _e('Post Type: Publication', 'publications-manager'); ?></li>
                <li><?php _e('The plugin automatically filters to show only this member\'s publications', 'publications-manager'); ?></li>
            </ol>

            <p><strong><?php _e('Display Authors with Links:', 'publications-manager'); ?></strong></p>
            <p><?php _e('Use dynamic data:', 'publications-manager'); ?> <code>{post_meta:pm_authors}</code></p>
            <p><?php _e('This displays authors with automatic links to linked team members.', 'publications-manager'); ?></p>

            <p><strong><?php _e('Display Publication Type (Formatted):', 'publications-manager'); ?></strong></p>
            <p><?php _e('Use:', 'publications-manager'); ?> <code>{post_meta:pm_type}</code> <?php _e('- shows "Journal Article" instead of "article"', 'publications-manager'); ?></p>

            <p><strong><?php _e('Get Author Term URL (in Query Loops):', 'publications-manager'); ?></strong></p>
            <p><?php _e('When looping through author terms, use:', 'publications-manager'); ?> <code>{term_meta:pm_author_team_url}</code></p>
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

        // Count publications with/without links using author taxonomy
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
            // Get author terms for this publication
            $author_terms = get_the_terms($pub_id, 'pm_author');
            $has_link = false;

            if ($author_terms && !is_wp_error($author_terms)) {
                foreach ($author_terms as $term) {
                    $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);
                    if ($team_member_id && get_post_status($team_member_id) === 'publish') {
                        $has_link = true;
                        $total_connections++;
                    }
                }
            }

            if ($has_link) {
                $pubs_with_links++;
            } else {
                $pubs_without_links++;
            }
        }

        // Get top authors with most publications using taxonomy
        $all_author_terms = get_terms(array(
            'taxonomy' => 'pm_author',
            'hide_empty' => false
        ));

        $author_stats = array();
        $duplicate_count = 0;
        $actual_total_connections = 0;
        $orphaned_count = 0;

        if ($all_author_terms && !is_wp_error($all_author_terms)) {
            foreach ($all_author_terms as $term) {
                // Count publications for this author
                $pub_count = $term->count;

                // Check if linked to team member
                $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);

                if ($team_member_id) {
                    // Check if team member still exists
                    $member = get_post($team_member_id);
                    if ($member && $member->post_status === 'publish') {
                        $actual_total_connections += $pub_count;

                        // Find existing entry or create new one
                        $found = false;
                        foreach ($author_stats as &$stat) {
                            if ($stat['member_id'] === $team_member_id) {
                                $stat['count'] += $pub_count;
                                $found = true;
                                break;
                            }
                        }

                        if (!$found) {
                            $author_stats[] = array(
                                'name' => $member->post_title,
                                'count' => $pub_count,
                                'total_entries' => $pub_count,
                                'duplicates' => 0,
                                'orphaned' => 0,
                                'member_id' => $team_member_id
                            );
                        }
                    }
                }
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
                    <p><?php printf(__('Found %d duplicate meta entries. Please manually review your publications and re-link authors if needed.', 'publications-manager'), $duplicate_count); ?></p>
                </div>
            <?php elseif ($pubs_without_links > 0): ?>
                <div class="notice notice-warning inline" style="margin-top: 20px;">
                    <p><strong><?php _e('Action Required:', 'publications-manager'); ?></strong></p>
                    <p><?php printf(__('You have %d publications without team member links. Go to Publications → Authors to link them manually.', 'publications-manager'), $pubs_without_links); ?></p>
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
     * Re-sync author -> team member links and cached URLs.
     *
     * Used after the Team CPT changes (slug setting or URL rewrite). For each linked author
     * term it RE-RESOLVES the team member by name against the CURRENT team CPT (so links survive
     * pointing the plugin at a different CPT), updates pm_team_member_id if it moved, and refreshes
     * the cached pm_author_team_url permalink. Falls back to the stored member when no name match
     * exists; clears the link when the stored member is also gone.
     *
     * @return int Number of author terms whose link or cached URL was updated.
     */
    private static function resync_author_team_urls()
    {
        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        $terms = get_terms(array(
            'taxonomy'   => 'pm_author',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'     => 'pm_team_member_id',
                    'compare' => 'EXISTS',
                ),
            ),
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        $cpt_exists = post_type_exists($team_cpt_slug);
        $updated    = 0;

        foreach ($terms as $term) {
            $old_member = (int) get_term_meta($term->term_id, 'pm_team_member_id', true);
            $member_id  = 0;

            // Prefer a fresh title match in the CURRENT team CPT.
            if ($cpt_exists) {
                $found = get_posts(array(
                    'post_type'      => $team_cpt_slug,
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'title'          => $term->name,
                    'fields'         => 'ids',
                ));
                if (!empty($found)) {
                    $member_id = (int) $found[0];
                }
            }

            // No name match: keep the stored member if it still exists, else clear the link.
            if (!$member_id) {
                if ($old_member && get_post_status($old_member) !== false) {
                    $member_id = $old_member;
                } else {
                    delete_term_meta($term->term_id, 'pm_team_member_id');
                    delete_term_meta($term->term_id, 'pm_author_team_url');
                    $updated++;
                    continue;
                }
            }

            if ($member_id !== $old_member) {
                update_term_meta($term->term_id, 'pm_team_member_id', $member_id);
            }

            $fresh   = get_permalink($member_id);
            $old_url = get_term_meta($term->term_id, 'pm_author_team_url', true);

            if ($fresh && ($fresh !== $old_url || $member_id !== $old_member)) {
                update_term_meta($term->term_id, 'pm_author_team_url', $fresh);
                $updated++;
            }
        }

        return $updated;
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
