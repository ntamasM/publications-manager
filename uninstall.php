<?php

/**
 * Uninstall Publications Manager Plugin
 * 
 * This file runs when the plugin is deleted from WordPress admin.
 * It will remove ALL plugin data from the database.
 * 
 * @package Publications_Manager
 */

// Exit if accessed directly or not in uninstall mode
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all publications (custom post type)
$publications = get_posts(array(
    'post_type' => 'publication',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
));

foreach ($publications as $pub_id) {
    // Delete all current meta data fields
    $meta_fields = array(
        'pm_type',
        'pm_doi',
        'pm_issn',
        'pm_year',
        'pm_month',
        'pm_journal',
        'pm_volume',
        'pm_number',
        'pm_issue',
        'pm_pages',
        'pm_chapter',
        'pm_publisher',
        'pm_address',
        'pm_edition',
        'pm_series',
        'pm_institution',
        'pm_organization',
        'pm_school',
        'pm_howpublished',
        'pm_techtype',
        'pm_isbn',
        'pm_crossref',
        'pm_key',
        'pm_url',
        'pm_urldate',
        'pm_image_url',
        'pm_image_ext',
        'pm_rel_page',
        'pm_abstract',
        'pm_note',
        'pm_comment',
        'pm_status',
        'pm_bibtex_key',
        'pm_team_members',
        'pm_author_links',
        'pm_import_id',
        'pm_last_updated'
    );

    foreach ($meta_fields as $field) {
        delete_post_meta($pub_id, $field);
    }

    // Force delete the publication post
    wp_delete_post($pub_id, true);
}

// Delete author taxonomy and all its terms
$author_terms = get_terms(array(
    'taxonomy' => 'pm_author',
    'hide_empty' => false,
    'fields' => 'ids'
));

if (!is_wp_error($author_terms) && !empty($author_terms)) {
    foreach ($author_terms as $term_id) {
        // Delete term meta
        delete_term_meta($term_id, 'pm_team_member_id');
        delete_term_meta($term_id, 'pm_author_team_url');
        // Delete the term
        wp_delete_term($term_id, 'pm_author');
    }
}

// Clean up team member relationships
$team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
if (post_type_exists($team_cpt_slug)) {
    global $wpdb;

    // Delete pm_publication_id meta entries (current relationship tracking)
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
        WHERE meta_key = 'pm_publication_id'"
    );

    // Delete pm_author_term_id meta entries (author-to-team-member links)
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
        WHERE meta_key = 'pm_author_term_id'"
    );
}

// Delete plugin options
delete_option('pm_team_cpt_slug');

// Delete transients
delete_transient('pm_flush_rewrite_rules');
delete_transient('pm_author_url_migrated_v2.2');

// Flush rewrite rules to clean up custom post type and taxonomy rules
flush_rewrite_rules();
