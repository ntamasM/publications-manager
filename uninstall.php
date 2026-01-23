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
    // Delete all meta data associated with this publication
    delete_post_meta($pub_id, 'pm_type');
    delete_post_meta($pub_id, 'pm_authors');
    delete_post_meta($pub_id, 'pm_editor');
    delete_post_meta($pub_id, 'pm_title');
    delete_post_meta($pub_id, 'pm_booktitle');
    delete_post_meta($pub_id, 'pm_journal');
    delete_post_meta($pub_id, 'pm_volume');
    delete_post_meta($pub_id, 'pm_number');
    delete_post_meta($pub_id, 'pm_pages');
    delete_post_meta($pub_id, 'pm_publisher');
    delete_post_meta($pub_id, 'pm_address');
    delete_post_meta($pub_id, 'pm_edition');
    delete_post_meta($pub_id, 'pm_chapter');
    delete_post_meta($pub_id, 'pm_institution');
    delete_post_meta($pub_id, 'pm_school');
    delete_post_meta($pub_id, 'pm_howpublished');
    delete_post_meta($pub_id, 'pm_organization');
    delete_post_meta($pub_id, 'pm_series');
    delete_post_meta($pub_id, 'pm_year');
    delete_post_meta($pub_id, 'pm_month');
    delete_post_meta($pub_id, 'pm_note');
    delete_post_meta($pub_id, 'pm_key');
    delete_post_meta($pub_id, 'pm_annote');
    delete_post_meta($pub_id, 'pm_crossref');
    delete_post_meta($pub_id, 'pm_doi');
    delete_post_meta($pub_id, 'pm_url');
    delete_post_meta($pub_id, 'pm_abstract');
    delete_post_meta($pub_id, 'pm_isbn');
    delete_post_meta($pub_id, 'pm_issn');
    delete_post_meta($pub_id, 'pm_keywords');
    delete_post_meta($pub_id, 'pm_bibtex');
    delete_post_meta($pub_id, 'pm_team_members');
    delete_post_meta($pub_id, 'pm_author_links');

    // Force delete the publication post
    wp_delete_post($pub_id, true);
}

// Clean up team member relationships
$team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
if (post_type_exists($team_cpt_slug)) {
    global $wpdb;

    // Delete all pm_publication_id meta entries from team members
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
        WHERE meta_key = 'pm_publication_id'"
    );

    // Delete all pm_publication_{id} meta entries from team members
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
        WHERE meta_key LIKE 'pm_publication_%'"
    );
}

// Delete plugin options
delete_option('pm_team_cpt_slug');

// Delete any transients
delete_transient('pm_flush_rewrite_rules');

// Clear any scheduled events (if any were created)
wp_clear_scheduled_hook('pm_cleanup_hook'); // Example, adjust if you have cron jobs

// Flush rewrite rules to clean up custom post type rules
flush_rewrite_rules();
