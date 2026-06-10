<?php
if (! defined('ABSPATH')) exit;

class PM_Fields
{
    public static function get_all()
    {
        return array(
            // export-only identity
            'id'    => array('meta'=>null,'label'=>__('ID','publications-manager'),'bibtex'=>null,'sanitize'=>null,'role'=>'identity'),
            'slug'  => array('meta'=>null,'label'=>__('Slug','publications-manager'),'bibtex'=>null,'sanitize'=>null,'role'=>'identity'),
            // special-handled
            'title'   => array('meta'=>null,'label'=>__('Title','publications-manager'),'bibtex'=>null,'sanitize'=>'text','role'=>'title'),
            'type'    => array('meta'=>'pm_type','label'=>__('Publication Type','publications-manager'),'bibtex'=>null,'sanitize'=>'text','role'=>'type'),
            'bibtex'  => array('meta'=>'pm_bibtex','label'=>__('BibTeX Key','publications-manager'),'bibtex'=>null,'sanitize'=>'text','role'=>'cite_key'),
            'authors' => array('meta'=>null,'label'=>__('Authors','publications-manager'),'bibtex'=>null,'sanitize'=>null,'role'=>'authors'),
            'editor'  => array('meta'=>'pm_editor','label'=>__('Editors','publications-manager'),'bibtex'=>'editor','sanitize'=>'textarea','role'=>null),
            'date'    => array('meta'=>'pm_date','label'=>__('Date','publications-manager'),'bibtex'=>null,'sanitize'=>'text','role'=>null),
            'year'    => array('meta'=>'pm_year','label'=>__('Year','publications-manager'),'bibtex'=>'year','sanitize'=>'text','role'=>'year'),
            'award'   => array('meta'=>'pm_award','label'=>__('Award','publications-manager'),'bibtex'=>'award','sanitize'=>'text','role'=>null),
            // ordinary fields
            'journal'      => array('meta'=>'pm_journal','label'=>__('Journal','publications-manager'),'bibtex'=>'journal','sanitize'=>'text','role'=>null),
            'booktitle'    => array('meta'=>'pm_booktitle','label'=>__('Book Title','publications-manager'),'bibtex'=>'booktitle','sanitize'=>'text','role'=>null),
            'issuetitle'   => array('meta'=>'pm_issuetitle','label'=>__('Issue Title','publications-manager'),'bibtex'=>'issuetitle','sanitize'=>'text','role'=>null),
            'volume'       => array('meta'=>'pm_volume','label'=>__('Volume','publications-manager'),'bibtex'=>'volume','sanitize'=>'text','role'=>null),
            'number'       => array('meta'=>'pm_number','label'=>__('Number','publications-manager'),'bibtex'=>'number','sanitize'=>'text','role'=>null),
            'issue'        => array('meta'=>'pm_issue','label'=>__('Issue','publications-manager'),'bibtex'=>'issue','sanitize'=>'text','role'=>null),
            'pages'        => array('meta'=>'pm_pages','label'=>__('Pages','publications-manager'),'bibtex'=>'pages','sanitize'=>'text','role'=>null),
            'chapter'      => array('meta'=>'pm_chapter','label'=>__('Chapter','publications-manager'),'bibtex'=>'chapter','sanitize'=>'text','role'=>null),
            'publisher'    => array('meta'=>'pm_publisher','label'=>__('Publisher','publications-manager'),'bibtex'=>'publisher','sanitize'=>'text','role'=>null),
            'address'      => array('meta'=>'pm_address','label'=>__('Address','publications-manager'),'bibtex'=>'address','sanitize'=>'text','role'=>null),
            'edition'      => array('meta'=>'pm_edition','label'=>__('Edition','publications-manager'),'bibtex'=>'edition','sanitize'=>'text','role'=>null),
            'series'       => array('meta'=>'pm_series','label'=>__('Series','publications-manager'),'bibtex'=>'series','sanitize'=>'text','role'=>null),
            'institution'  => array('meta'=>'pm_institution','label'=>__('Institution','publications-manager'),'bibtex'=>'institution','sanitize'=>'text','role'=>null),
            'organization' => array('meta'=>'pm_organization','label'=>__('Organization','publications-manager'),'bibtex'=>'organization','sanitize'=>'text','role'=>null),
            'school'       => array('meta'=>'pm_school','label'=>__('School','publications-manager'),'bibtex'=>'school','sanitize'=>'text','role'=>null),
            'howpublished' => array('meta'=>'pm_howpublished','label'=>__('How Published','publications-manager'),'bibtex'=>'howpublished','sanitize'=>'text','role'=>null),
            'techtype'     => array('meta'=>'pm_techtype','label'=>__('Tech/Thesis Type','publications-manager'),'bibtex'=>'type','sanitize'=>'text','role'=>null), // BibTeX tag is "type"
            'isbn'         => array('meta'=>'pm_isbn','label'=>__('ISBN/ISSN','publications-manager'),'bibtex'=>'isbn','sanitize'=>'text','role'=>null),
            'crossref'     => array('meta'=>'pm_crossref','label'=>__('Cross Reference','publications-manager'),'bibtex'=>'crossref','sanitize'=>'text','role'=>null),
            'key'          => array('meta'=>'pm_key','label'=>__('Sort Key','publications-manager'),'bibtex'=>'key','sanitize'=>'text','role'=>null), // NOT the BibTeX key
            'url'          => array('meta'=>'pm_url','label'=>__('URL','publications-manager'),'bibtex'=>'url','sanitize'=>'url','role'=>null),
            'doi'          => array('meta'=>'pm_doi','label'=>__('DOI','publications-manager'),'bibtex'=>'doi','sanitize'=>'text','role'=>null),
            'urldate'      => array('meta'=>'pm_urldate','label'=>__('URL Access Date','publications-manager'),'bibtex'=>'urldate','sanitize'=>'text','role'=>null),
            'image_url'    => array('meta'=>'pm_image_url','label'=>__('Image URL','publications-manager'),'bibtex'=>'image_url','sanitize'=>'url','role'=>null),
            'image_ext'    => array('meta'=>'pm_image_ext','label'=>__('External Image Link','publications-manager'),'bibtex'=>'image_ext','sanitize'=>'url','role'=>null),
            'rel_page'     => array('meta'=>'pm_rel_page','label'=>__('Related Page','publications-manager'),'bibtex'=>'rel_page','sanitize'=>'text','role'=>null),
            'import_id'    => array('meta'=>'pm_import_id','label'=>__('Import ID','publications-manager'),'bibtex'=>null,'sanitize'=>'text','role'=>'import_id'),
            'abstract'     => array('meta'=>'pm_abstract','label'=>__('Abstract','publications-manager'),'bibtex'=>'abstract','sanitize'=>'textarea','role'=>null),
            'note'         => array('meta'=>'pm_note','label'=>__('Note','publications-manager'),'bibtex'=>'note','sanitize'=>'textarea','role'=>null),
            'comment'      => array('meta'=>'pm_comment','label'=>__('Internal Comment','publications-manager'),'bibtex'=>'comment','sanitize'=>'textarea','role'=>null),
            'status'       => array('meta'=>'pm_status','label'=>__('Status','publications-manager'),'bibtex'=>'status','sanitize'=>'text','role'=>null),
        );
    }

    /** Meta fields saved by the editor + registered for REST + written by import.
     *  = has a 'meta' key AND role is null OR 'type'/'cite_key'/'import_id'.
     *  (Excludes title/authors/year/identity — those are handled specially.) */
    public static function get_editable_meta_map() // record_key => 'pm_xxx'
    {
        $out = array();
        foreach (self::get_all() as $rk => $f) {
            if (empty($f['meta'])) continue;
            if (in_array($f['role'], array('year'), true)) continue; // year is derived
            $out[$rk] = $f['meta'];
        }
        return $out;
    }

    public static function get_editable_meta_keys() // array of 'pm_xxx'
    {
        return array_values(self::get_editable_meta_map());
    }

    /** record_key => label for the custom-export checkbox UI (exclude id + slug). */
    public static function get_field_labels()
    {
        $out = array();
        foreach (self::get_all() as $rk => $f) {
            if ($f['role'] === 'identity') continue;
            $out[$rk] = $f['label'];
        }
        return $out;
    }

    /** All record keys in order (CSV columns / JSON shape), including id + slug. */
    public static function get_record_keys()
    {
        return array_keys(self::get_all());
    }

    /** record_key => bibtex_tag for normal BibTeX field lines (only entries with a 'bibtex' tag). */
    public static function get_bibtex_field_map()
    {
        $out = array();
        foreach (self::get_all() as $rk => $f) {
            if (! empty($f['bibtex'])) $out[$rk] = $f['bibtex'];
        }
        return $out;
    }

    /** Sanitize a value by record key. */
    public static function sanitize($record_key, $value)
    {
        $all = self::get_all();
        $type = isset($all[$record_key]['sanitize']) ? $all[$record_key]['sanitize'] : 'text';
        if ($type === 'textarea') return sanitize_textarea_field($value);
        if ($type === 'url')      return esc_url_raw($value);
        return sanitize_text_field($value);
    }

    /** Sanitize by meta key (used by the save loop). */
    public static function sanitize_by_meta($meta_key, $value)
    {
        foreach (self::get_all() as $rk => $f) {
            if ($f['meta'] === $meta_key) return self::sanitize($rk, $value);
        }
        return sanitize_text_field($value);
    }
}
