<?php

/**
 * File Import
 *
 * Imports publications from the plugin's own export formats (JSON, CSV, BibTeX).
 * Designed as the inverse of the export in PM_Admin_Pages: it writes the FULL field
 * set returned by PM_Admin_Pages::get_export_meta_fields() so an export -> import
 * round-trip does not lose data.
 *
 * NOTE: This intentionally does NOT route through PM_Crossref_Import::create_publication(),
 * which only maps a small subset of fields.
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class PM_File_Import
{
    /**
     * Import publications from raw file contents.
     *
     * @param string $contents Raw file contents.
     * @param string $format   One of: json, csv, bibtex.
     * @return array {
     *     @type bool  success
     *     @type int   total
     *     @type array imported  List of ['action','title','post_id'].
     *     @type array failed     List of ['title','error'].
     *     @type string message   Set when success is false.
     * }
     */
    public static function import_from_file($contents, $format)
    {
        set_time_limit(300);

        switch ($format) {
            case 'json':
                $records = self::parse_json($contents);
                break;
            case 'csv':
                $records = self::parse_csv($contents);
                break;
            case 'bibtex':
                $records = self::parse_bibtex($contents);
                break;
            default:
                return array(
                    'success' => false,
                    'message' => __('Unsupported import format.', 'publications-manager'),
                );
        }

        if (is_wp_error($records)) {
            return array(
                'success' => false,
                'message' => $records->get_error_message(),
            );
        }

        if (empty($records)) {
            return array(
                'success' => false,
                'message' => __('No publications found in the uploaded file.', 'publications-manager'),
            );
        }

        $results = array(
            'success'  => true,
            'total'    => count($records),
            'imported' => array(),
            'failed'   => array(),
        );

        foreach ($records as $record) {
            $outcome = self::import_record($record);

            if (is_wp_error($outcome)) {
                $results['failed'][] = array(
                    'title' => isset($record['title']) ? $record['title'] : '',
                    'error' => $outcome->get_error_message(),
                );
            } else {
                $results['imported'][] = $outcome;
            }
        }

        return $results;
    }

    /**
     * Create or update a single publication from a normalized record.
     *
     * @param array $record Record keyed like the export (e.g. 'title', 'authors', 'doi', ...).
     * @return array|WP_Error ['action','title','post_id'] or error.
     */
    private static function import_record($record)
    {
        $title = isset($record['title']) ? trim($record['title']) : '';
        if ($title === '') {
            return new WP_Error('pm_no_title', __('Skipped a record with no title.', 'publications-manager'));
        }

        // Create-vs-update: match by DOI, then BibTeX key, then slug. Never match on incoming id.
        $existing_id = self::find_existing_publication($record);

        if ($existing_id) {
            $post_id = wp_update_post(array(
                'ID'         => $existing_id,
                'post_title' => sanitize_text_field($title),
            ), true);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post(array(
                'post_type'   => 'publication',
                'post_status' => 'publish',
                'post_title'  => sanitize_text_field($title),
            ), true);
            $action = 'created';
        }

        if (is_wp_error($post_id) || ! $post_id) {
            return new WP_Error('pm_save_failed', __('Failed to save publication.', 'publications-manager'));
        }

        // Write every exportable meta field that is present and non-empty.
        foreach (PM_Fields::get_editable_meta_map() as $record_key => $meta_key) {
            // import_id is set fresh below; year is derived from date below.
            if ($record_key === 'import_id' || $record_key === 'year') {
                continue;
            }

            if (! array_key_exists($record_key, $record)) {
                continue;
            }

            $value = $record[$record_key];
            if (is_array($value)) {
                continue; // arrays handled elsewhere (authors)
            }

            $value = PM_Fields::sanitize($record_key, $value);

            // Only write non-empty values so a partial import never wipes existing data.
            if ($value !== '' && $value !== null) {
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Authors -> pm_author taxonomy terms.
        if (! empty($record['authors']) && is_array($record['authors']) && class_exists('PM_Author_Taxonomy')) {
            $author_term_ids = array();
            foreach ($record['authors'] as $author_name) {
                $author_name = trim($author_name);
                if ($author_name === '') {
                    continue;
                }
                $term_id = PM_Author_Taxonomy::get_or_create_author_term($author_name);
                if ($term_id && ! is_wp_error($term_id)) {
                    $author_term_ids[] = $term_id;
                }
            }
            if (! empty($author_term_ids)) {
                wp_set_object_terms($post_id, $author_term_ids, 'pm_author', false);
                // Persist the author order (import order = desired display order)
                update_post_meta($post_id, 'pm_author_order', array_map('intval', $author_term_ids));
            }
        }

        // Derive year from date (matches the save/Crossref paths).
        if (! empty($record['date'])) {
            $year = substr($record['date'], 0, 4);
            if (is_numeric($year)) {
                update_post_meta($post_id, 'pm_year', $year);
            }
        } elseif (! empty($record['year']) && is_numeric($record['year'])) {
            update_post_meta($post_id, 'pm_year', sanitize_text_field($record['year']));
        }

        // Guarantee a pm_date. Manual entry requires it, and the admin list orders by
        // pm_date (INNER JOIN) — without it, imported publications are hidden from the list.
        // BibTeX has no day precision, so reconstruct from year (+ month when present).
        if (get_post_meta($post_id, 'pm_date', true) === '') {
            $year = get_post_meta($post_id, 'pm_year', true);
            if ($year && is_numeric($year)) {
                $month = isset($record['month']) ? self::month_to_number($record['month']) : '01';
                update_post_meta($post_id, 'pm_date', sprintf('%04d-%s-01', $year, $month));
            }
        }

        // Default status when none supplied.
        if (empty($record['status'])) {
            update_post_meta($post_id, 'pm_status', 'published');
        }

        // Mark as imported.
        update_post_meta($post_id, 'pm_import_id', current_time('timestamp'));

        return array(
            'action'  => $action,
            'title'   => $title,
            'post_id' => (int) $post_id,
        );
    }

    /**
     * Locate an existing publication for a record: DOI -> BibTeX key -> slug.
     *
     * @param array $record
     * @return int Post ID, or 0 if none.
     */
    private static function find_existing_publication($record)
    {
        if (! empty($record['doi'])) {
            $id = self::find_by_meta('pm_doi', $record['doi']);
            if ($id) {
                return $id;
            }
        }

        if (! empty($record['bibtex'])) {
            $id = self::find_by_meta('pm_bibtex', $record['bibtex']);
            if ($id) {
                return $id;
            }
        }

        if (! empty($record['slug'])) {
            $posts = get_posts(array(
                'post_type'      => 'publication',
                'name'           => sanitize_title($record['slug']),
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ));
            if (! empty($posts)) {
                return (int) $posts[0];
            }
        }

        return 0;
    }

    /**
     * Find the first publication whose meta key equals the given value.
     */
    private static function find_by_meta($meta_key, $value)
    {
        $posts = get_posts(array(
            'post_type'      => 'publication',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => $meta_key,
                    'value' => $value,
                ),
            ),
        ));

        return ! empty($posts) ? (int) $posts[0] : 0;
    }

    /**
     * Sanitize a value the same way the meta-box save path does.
     */
    private static function sanitize_field($meta_key, $value)
    {
        if (in_array($meta_key, array('pm_abstract', 'pm_note', 'pm_comment', 'pm_editor'), true)) {
            return sanitize_textarea_field($value);
        }
        if (in_array($meta_key, array('pm_url', 'pm_image_url', 'pm_image_ext'), true)) {
            return esc_url_raw($value);
        }
        return sanitize_text_field($value);
    }

    // ------------------------------------------------------------------
    // Parsers — each returns an array of normalized records (or WP_Error).
    // ------------------------------------------------------------------

    /**
     * Parse a JSON export (array of record objects).
     */
    private static function parse_json($contents)
    {
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            return new WP_Error('pm_bad_json', __('The uploaded file is not valid JSON.', 'publications-manager'));
        }

        // Allow a single object as well as an array of objects.
        if (isset($data['title']) || isset($data['authors'])) {
            $data = array($data);
        }

        $records = array();
        foreach ($data as $row) {
            if (is_array($row)) {
                $records[] = $row;
            }
        }

        return $records;
    }

    /**
     * Parse a CSV export. The first row is the header (matches PM_Admin_Pages::render_csv).
     */
    private static function parse_csv($contents)
    {
        // Strip a UTF-8 BOM if present (the export prepends one for Excel).
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle);
        if ($header === false || empty($header)) {
            fclose($handle);
            return new WP_Error('pm_bad_csv', __('The uploaded CSV file is empty or invalid.', 'publications-manager'));
        }

        $header = array_map('trim', $header);

        $records = array();
        while (($row = fgetcsv($handle)) !== false) {
            // Skip fully blank lines.
            if (count(array_filter($row, function ($v) {
                return $v !== null && $v !== '';
            })) === 0) {
                continue;
            }

            $record = array();
            foreach ($header as $i => $col) {
                $col = (string) $col;
                if ($col === '') {
                    continue;
                }
                $value = isset($row[$i]) ? $row[$i] : '';

                if ($col === 'authors') {
                    $record['authors'] = self::split_list($value, ';');
                } else {
                    $record[$col] = $value;
                }
            }
            $records[] = $record;
        }

        fclose($handle);

        return $records;
    }

    /**
     * Parse a BibTeX document. Reliably handles the plugin's own export
     * (PM_Admin_Pages::render_bibtex); best-effort for arbitrary .bib files.
     */
    private static function parse_bibtex($contents)
    {
        $records = array();

        // Match each @type{key, ... } entry. Body capture is greedy up to the
        // closing brace that precedes the next entry / end of file.
        if (! preg_match_all('/@(\w+)\s*\{([^,]*),(.*?)\n\}/s', $contents, $matches, PREG_SET_ORDER)) {
            return $records;
        }

        $bib_to_record = array_flip(PM_Fields::get_bibtex_field_map());

        foreach ($matches as $entry) {
            $entry_type = strtolower(trim($entry[1]));
            $cite_key   = trim($entry[2]);
            $body       = $entry[3];

            $record = array(
                'bibtex' => $cite_key,
            );

            // Reverse-map the BibTeX entry type to a publication type slug.
            $type_slug = self::bibtex_type_to_slug($entry_type);
            if ($type_slug) {
                $record['type'] = $type_slug;
            }

            // Parse "field = {value}" and bare "field = value" lines.
            if (preg_match_all('/(\w+)\s*=\s*(\{(.*?)\}|([^,\n]+))\s*,?\s*\n/s', $body . "\n", $fields, PREG_SET_ORDER)) {
                foreach ($fields as $field) {
                    $name  = strtolower(trim($field[1]));
                    $value = isset($field[3]) && $field[3] !== '' ? $field[3] : (isset($field[4]) ? $field[4] : '');
                    $value = self::unescape_bibtex(trim($value));

                    // title is emitted specially by render_bibtex (not via the field map),
                    // so it is absent from get_bibtex_field_map() — map it back explicitly.
                    if ($name === 'title') {
                        $record['title'] = $value;
                        continue;
                    }

                    if ($name === 'author') {
                        $record['authors'] = self::split_list($value, ' and ');
                        continue;
                    }

                    if ($name === 'month') {
                        $record['month'] = $value;
                        continue;
                    }

                    if (isset($bib_to_record[$name])) {
                        $record[$bib_to_record[$name]] = $value;
                    }
                }
            }

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Reverse the brace/backslash escaping applied by PM_Admin_Pages::bibtex_line().
     */
    private static function unescape_bibtex($value)
    {
        $value = str_replace(array('\\{', '\\}'), array('{', '}'), $value);
        $value = str_replace('\\\\', '\\', $value);
        return $value;
    }

    /**
     * Map a BibTeX entry type to a publication type slug (best-effort).
     */
    private static function bibtex_type_to_slug($entry_type)
    {
        if (! class_exists('PM_Publication_Types')) {
            return '';
        }
        foreach (PM_Publication_Types::get_all() as $slug => $def) {
            if (! empty($def['bibtex_key_ext']) && strtolower($def['bibtex_key_ext']) === $entry_type) {
                return $slug;
            }
        }
        return '';
    }

    /**
     * Convert a BibTeX month (e.g. "jan", "1", "01") to a two-digit month string.
     * Falls back to "01" when unrecognized.
     */
    private static function month_to_number($month)
    {
        $month = strtolower(trim((string) $month));

        if (is_numeric($month)) {
            $n = (int) $month;
            return ($n >= 1 && $n <= 12) ? sprintf('%02d', $n) : '01';
        }

        $map = array(
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
            'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12',
        );
        $key = substr($month, 0, 3);

        return isset($map[$key]) ? $map[$key] : '01';
    }

    /**
     * Split a delimited author/list string into a trimmed array.
     */
    private static function split_list($value, $delimiter)
    {
        $parts = explode($delimiter, (string) $value);
        $parts = array_map('trim', $parts);
        return array_values(array_filter($parts, function ($v) {
            return $v !== '';
        }));
    }
}
