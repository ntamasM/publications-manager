<?php

/**
 * Publication Types Registry
 * Mirrors teachPress publication types system
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class PM_Publication_Types
{

    /**
     * All registered publication types
     */
    private static $pub_types = array();

    /**
     * Register all default publication types
     * Based on teachPress default-publication-types.php
     */
    public static function register_all()
    {

        // Article
        self::register(array(
            'type_slug'         => 'article',
            'bibtex_key_ext'    => 'article',
            'i18n_singular'     => __('Journal Article', 'publications-manager'),
            'i18n_plural'       => __('Journal Articles', 'publications-manager'),
            'default_fields'    => array('journal', 'volume', 'number', 'issue', 'pages'),
            'html_meta_row'     => '{IN}{journal}{volume}{issue}{number}{pages}{year}{isbn}{note}'
        ));

        // Book
        self::register(array(
            'type_slug'         => 'book',
            'bibtex_key_ext'    => 'book',
            'i18n_singular'     => __('Book', 'publications-manager'),
            'i18n_plural'       => __('Books', 'publications-manager'),
            'default_fields'    => array('volume', 'number', 'publisher', 'address', 'edition', 'series'),
            'html_meta_row'     => '{edition}{publisher}{address}{year}{isbn}{note}'
        ));

        // Booklet
        self::register(array(
            'type_slug'         => 'booklet',
            'bibtex_key_ext'    => 'booklet',
            'i18n_singular'     => __('Booklet', 'publications-manager'),
            'i18n_plural'       => __('Booklets', 'publications-manager'),
            'default_fields'    => array('volume', 'address', 'howpublished'),
            'html_meta_row'     => '{howpublished}{address}{edition}{year}{isbn}{note}'
        ));

        // Collection
        self::register(array(
            'type_slug'         => 'collection',
            'bibtex_key_ext'    => 'collection',
            'i18n_singular'     => __('Collection', 'publications-manager'),
            'i18n_plural'       => __('Collections', 'publications-manager'),
            'default_fields'    => array('booktitle', 'volume', 'number', 'pages', 'publisher', 'address', 'edition', 'chapter', 'series'),
            'html_meta_row'     => '{edition}{publisher}{address}{year}{isbn}{note}'
        ));

        // Conference
        self::register(array(
            'type_slug'         => 'conference',
            'bibtex_key_ext'    => 'conference',
            'i18n_singular'     => __('Conference', 'publications-manager'),
            'i18n_plural'       => __('Conferences', 'publications-manager'),
            'default_fields'    => array('booktitle', 'volume', 'number', 'pages', 'publisher', 'address', 'organization', 'series'),
            'html_meta_row'     => '{booktitle}{volume}{number}{series}{organization}{publisher}{address}{year}{isbn}{note}'
        ));

        // Bachelor Thesis
        self::register(array(
            'type_slug'         => 'bachelorthesis',
            'bibtex_key_ext'    => 'mastersthesis',
            'i18n_singular'     => __('Bachelor Thesis', 'publications-manager'),
            'i18n_plural'       => __('Bachelor Theses', 'publications-manager'),
            'default_fields'    => array('address', 'school', 'techtype'),
            'html_meta_row'     => '{school}{address}{year}{isbn}{note}'
        ));

        // Diploma Thesis
        self::register(array(
            'type_slug'         => 'diplomathesis',
            'bibtex_key_ext'    => 'mastersthesis',
            'i18n_singular'     => __('Diploma Thesis', 'publications-manager'),
            'i18n_plural'       => __('Diploma Theses', 'publications-manager'),
            'default_fields'    => array('address', 'school', 'techtype'),
            'html_meta_row'     => '{school}{address}{year}{isbn}{note}'
        ));

        // Inbook
        self::register(array(
            'type_slug'         => 'inbook',
            'bibtex_key_ext'    => 'inbook',
            'i18n_singular'     => __('Book Chapter', 'publications-manager'),
            'i18n_plural'       => __('Book Chapters', 'publications-manager'),
            'default_fields'    => array('volume', 'number', 'pages', 'publisher', 'address', 'edition', 'chapter', 'series'),
            'html_meta_row'     => '{IN}{editor}{booktitle}{volume}{number}{chapter}{pages}{publisher}{address}{edition}{year}{isbn}{note}'
        ));

        // Incollection
        self::register(array(
            'type_slug'         => 'incollection',
            'bibtex_key_ext'    => 'incollection',
            'i18n_singular'     => __('Book Section', 'publications-manager'),
            'i18n_plural'       => __('Book Sections', 'publications-manager'),
            'default_fields'    => array('volume', 'number', 'pages', 'publisher', 'address', 'edition', 'chapter', 'series', 'techtype'),
            'html_meta_row'     => '{IN}{editor}{booktitle}{volume}{number}{pages}{publisher}{address}{year}{isbn}{note}'
        ));

        // Inproceedings
        self::register(array(
            'type_slug'         => 'inproceedings',
            'bibtex_key_ext'    => 'inproceedings',
            'i18n_singular'     => __('Proceedings Article', 'publications-manager'),
            'i18n_plural'       => __('Proceedings Articles', 'publications-manager'),
            'default_fields'    => array('booktitle', 'volume', 'number', 'pages', 'publisher', 'address', 'organization', 'series'),
            'html_meta_row'     => '{IN}{editor}{booktitle}{pages}{organization}{publisher}{address}{year}{isbn}{note}'
        ));

        // Manual
        self::register(array(
            'type_slug'         => 'manual',
            'bibtex_key_ext'    => 'manual',
            'i18n_singular'     => __('Technical Manual', 'publications-manager'),
            'i18n_plural'       => __('Technical Manuals', 'publications-manager'),
            'default_fields'    => array('address', 'edition', 'organization', 'series'),
            'html_meta_row'     => '{editor}{organization}{address}{edition}{year}{isbn}{note}'
        ));

        // Masters Thesis
        self::register(array(
            'type_slug'         => 'mastersthesis',
            'bibtex_key_ext'    => 'mastersthesis',
            'i18n_singular'     => __('Masters Thesis', 'publications-manager'),
            'i18n_plural'       => __('Masters Theses', 'publications-manager'),
            'default_fields'    => array('address', 'school', 'techtype'),
            'html_meta_row'     => '{school}{address}{year}{isbn}{note}'
        ));

        // Media
        self::register(array(
            'type_slug'         => 'media',
            'bibtex_key_ext'    => 'misc',
            'i18n_singular'     => __('Medium', 'publications-manager'),
            'i18n_plural'       => __('Media', 'publications-manager'),
            'default_fields'    => array('publisher', 'address', 'howpublished'),
            'html_meta_row'     => '{publisher}{address}{howpublished}{year}{urldate}{note}'
        ));

        // Misc
        self::register(array(
            'type_slug'         => 'misc',
            'bibtex_key_ext'    => 'misc',
            'i18n_singular'     => __('Miscellaneous', 'publications-manager'),
            'i18n_plural'       => __('Miscellaneous', 'publications-manager'),
            'default_fields'    => array('howpublished'),
            'html_meta_row'     => '{howpublished}{year}{isbn}{note}'
        ));

        // Online
        self::register(array(
            'type_slug'         => 'online',
            'bibtex_key_ext'    => 'online',
            'i18n_singular'     => __('Online', 'publications-manager'),
            'i18n_plural'       => __('Online', 'publications-manager'),
            'default_fields'    => array('howpublished'),
            'html_meta_row'     => '{editor}{organization}{year}{urldate}{note}'
        ));

        // Patent
        self::register(array(
            'type_slug'         => 'patent',
            'bibtex_key_ext'    => 'patent',
            'i18n_singular'     => __('Patent', 'publications-manager'),
            'i18n_plural'       => __('Patents', 'publications-manager'),
            'default_fields'    => array('howpublished'),
            'html_meta_row'     => '{number}{year}{note}'
        ));

        // Periodical
        self::register(array(
            'type_slug'         => 'periodical',
            'bibtex_key_ext'    => 'periodical',
            'i18n_singular'     => __('Periodical', 'publications-manager'),
            'i18n_plural'       => __('Periodicals', 'publications-manager'),
            'default_fields'    => array('howpublished'),
            'html_meta_row'     => '{issuetitle}{series}{volume}{number}{year}{urldate}{isbn}{note}'
        ));

        // PhD Thesis
        self::register(array(
            'type_slug'         => 'phdthesis',
            'bibtex_key_ext'    => 'phdthesis',
            'i18n_singular'     => __('PhD Thesis', 'publications-manager'),
            'i18n_plural'       => __('PhD Theses', 'publications-manager'),
            'default_fields'    => array('school', 'address'),
            'html_meta_row'     => '{school}{address}{year}{isbn}{note}'
        ));

        // Presentation
        self::register(array(
            'type_slug'         => 'presentation',
            'bibtex_key_ext'    => 'presentation',
            'i18n_singular'     => __('Presentation', 'publications-manager'),
            'i18n_plural'       => __('Presentations', 'publications-manager'),
            'default_fields'    => array('howpublished', 'address'),
            'html_meta_row'     => '{howpublished}{address}{date}{isbn}{note}'
        ));

        // Proceedings
        self::register(array(
            'type_slug'         => 'proceedings',
            'bibtex_key_ext'    => 'proceedings',
            'i18n_singular'     => __('Proceedings', 'publications-manager'),
            'i18n_plural'       => __('Proceedings', 'publications-manager'),
            'default_fields'    => array('organization', 'publisher', 'address'),
            'html_meta_row'     => '{howpublished}{organization}{publisher}{address}{volume}{number}{year}{isbn}{note}'
        ));

        // Techreport
        self::register(array(
            'type_slug'         => 'techreport',
            'bibtex_key_ext'    => 'techreport',
            'i18n_singular'     => __('Technical Report', 'publications-manager'),
            'i18n_plural'       => __('Technical Reports', 'publications-manager'),
            'default_fields'    => array('institution', 'address', 'techtype', 'number'),
            'html_meta_row'     => '{institution}{address}{techtype}{number}{year}{isbn}{note}'
        ));

        // Unpublished
        self::register(array(
            'type_slug'         => 'unpublished',
            'bibtex_key_ext'    => 'unpublished',
            'i18n_singular'     => __('Unpublished', 'publications-manager'),
            'i18n_plural'       => __('Unpublished', 'publications-manager'),
            'default_fields'    => array('howpublished'),
            'html_meta_row'     => '{howpublished}{year}{isbn}{note}'
        ));

        // Working paper
        self::register(array(
            'type_slug'         => 'workingpaper',
            'bibtex_key_ext'    => 'misc',
            'i18n_singular'     => __('Working paper', 'publications-manager'),
            'i18n_plural'       => __('Working papers', 'publications-manager'),
            'default_fields'    => array('howpublished'),
            'html_meta_row'     => '{howpublished}{year}{isbn}{note}'
        ));

        // Workshop
        self::register(array(
            'type_slug'         => 'workshop',
            'bibtex_key_ext'    => 'workshop',
            'i18n_singular'     => __('Workshop', 'publications-manager'),
            'i18n_plural'       => __('Workshops', 'publications-manager'),
            'default_fields'    => array('booktitle', 'organization', 'address'),
            'html_meta_row'     => '{booktitle}{volume}{number}{series}{organization}{publisher}{address}{year}{isbn}{note}'
        ));
    }

    /**
     * Register a publication type
     */
    public static function register($args)
    {
        $defaults = array(
            'type_slug'         => '',
            'bibtex_key_ext'    => '',
            'i18n_singular'     => '',
            'i18n_plural'       => '',
            'default_fields'    => array(),
            'html_meta_row'     => ''
        );

        $args = wp_parse_args($args, $defaults);

        if (! empty($args['type_slug'])) {
            self::$pub_types[$args['type_slug']] = $args;
        }
    }

    /**
     * Get all registered publication types
     */
    public static function get_all()
    {
        return self::$pub_types;
    }

    /**
     * Get a specific publication type
     */
    public static function get($type_slug)
    {
        return isset(self::$pub_types[$type_slug]) ? self::$pub_types[$type_slug] : null;
    }

    /**
     * Get publication type options for select dropdown
     */
    public static function get_options($selected = '')
    {
        $types = self::get_all();
        uasort($types, function ($a, $b) {
            return strcmp($a['i18n_singular'], $b['i18n_singular']);
        });

        $options = '';
        foreach ($types as $type) {
            $sel = selected($selected, $type['type_slug'], false);
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($type['type_slug']),
                $sel,
                esc_html($type['i18n_singular'])
            );
        }

        return $options;
    }
}

// Initialize publication types
PM_Publication_Types::register_all();
