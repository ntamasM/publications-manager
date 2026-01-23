<?php

/**
 * Publications Manager - Debug Connections
 * 
 * This utility helps you verify that relationships are working correctly
 * 
 * HOW TO USE:
 * Navigate to: your-site.com/wp-content/plugins/publications-manager/debug-connections.php
 * 
 * @package Publications Manager
 * @version 1.0.5
 */

// Find and load WordPress
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
if (isset($parse_uri[0])) {
    require_once($parse_uri[0] . 'wp-load.php');
} else {
    // Fallback
    require_once('../../../../wp-load.php');
}

// Security check
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

$team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

?>
<!DOCTYPE html>
<html>

<head>
    <title>Publications Manager - Debug Connections</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f1f1f1;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }

        h2 {
            color: #2271b1;
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f7f7f7;
            font-weight: 600;
        }

        .success {
            color: #00a32a;
            font-weight: bold;
        }

        .error {
            color: #d63638;
            font-weight: bold;
        }

        .info {
            background: #e5f5fa;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 20px 0;
        }

        .code {
            background: #f7f7f7;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 13px;
        }

        pre {
            margin: 0;
        }

        .button {
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 5px;
        }

        .button:hover {
            background: #135e96;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 Publications Manager - Connection Debug</h1>

        <div class="info">
            <p><strong>Team CPT Slug:</strong> <?php echo esc_html($team_cpt_slug); ?></p>
            <p><strong>Team CPT Exists:</strong> <?php echo post_type_exists($team_cpt_slug) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>'; ?></p>
        </div>

        <?php
        // Get sample publication
        $publications = get_posts(array(
            'post_type' => 'publication',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ));

        if (!empty($publications)) {
            echo '<h2>📚 Sample Publications & Their Relationships</h2>';

            foreach ($publications as $pub) {
                $authors = get_post_meta($pub->ID, 'pm_authors', false);
                $authors = !empty($authors) ? implode(', ', $authors) : '';
                $team_members = get_post_meta($pub->ID, 'pm_team_members', true);
                $author_links = get_post_meta($pub->ID, 'pm_author_links', true);

                echo '<div class="code">';
                echo '<strong>Publication ID ' . $pub->ID . ':</strong> ' . esc_html($pub->post_title) . '<br>';
                echo '<strong>Authors String:</strong> ' . esc_html($authors) . '<br>';
                echo '<strong>Linked Team Members (Array):</strong> ';
                if (is_array($team_members) && !empty($team_members)) {
                    echo '<span class="success">✓ ' . count($team_members) . ' linked</span><br>';
                    echo '<pre>' . print_r($team_members, true) . '</pre>';
                } else {
                    echo '<span class="error">✗ None</span><br>';
                }

                echo '<strong>Author Links (for display):</strong><br>';
                if (!empty($author_links)) {
                    echo '<pre>' . print_r($author_links, true) . '</pre>';
                }

                echo '<strong>Formatted Authors with Links:</strong><br>';
                echo pm_get_authors_with_links($pub->ID);
                echo '</div><br>';
            }
        }

        // Get sample team members
        if (post_type_exists($team_cpt_slug)) {
            $team_members = get_posts(array(
                'post_type' => $team_cpt_slug,
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ));

            if (!empty($team_members)) {
                echo '<h2>👥 Sample Team Members & Their Publications</h2>';

                foreach ($team_members as $member) {
                    $publication_ids = get_post_meta($member->ID, 'pm_publication_id', false);

                    echo '<div class="code">';
                    echo '<strong>Team Member ID ' . $member->ID . ':</strong> ' . esc_html($member->post_title) . '<br>';
                    echo '<strong>Linked Publication IDs (pm_publication_id):</strong> ';

                    if (!empty($publication_ids)) {
                        echo '<span class="success">✓ ' . count($publication_ids) . ' linked</span><br>';
                        echo '<pre>' . print_r($publication_ids, true) . '</pre>';

                        // Show individual publication meta entries
                        global $wpdb;
                        $pub_metas = $wpdb->get_results($wpdb->prepare(
                            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} 
                            WHERE post_id = %d 
                            AND meta_key LIKE 'pm_publication_%%' 
                            AND meta_key != 'pm_publication_id'",
                            $member->ID
                        ));

                        if (!empty($pub_metas)) {
                            echo '<br><strong>Individual Publication Meta Entries:</strong><br>';
                            foreach ($pub_metas as $meta) {
                                echo '<strong>' . $meta->meta_key . ':</strong><br>';
                                $data = maybe_unserialize($meta->meta_value);
                                echo '<pre>' . print_r($data, true) . '</pre>';
                            }
                        }
                    } else {
                        echo '<span class="error">✗ None</span><br>';
                    }

                    echo '</div><br>';
                }
            } else {
                echo '<div class="info">No team members found.</div>';
            }
        }
        ?>

        <h2>🧪 Test Queries</h2>

        <div class="code">
            <strong>Test: Get publications for a team member using meta query</strong><br>
            <?php
            if (!empty($team_members) && isset($team_members[0])) {
                $test_member_id = $team_members[0]->ID;

                $test_query = new WP_Query(array(
                    'post_type' => 'publication',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'pm_team_members',
                            'value' => $test_member_id,
                            'compare' => 'LIKE'
                        )
                    )
                ));

                echo 'Query for team member ID ' . $test_member_id . ': ';
                echo $test_query->found_posts > 0 ? '<span class="success">✓ Found ' . $test_query->found_posts . ' publications</span>' : '<span class="error">✗ No publications found</span>';
                wp_reset_postdata();
            }
            ?>
        </div>

        <br>

        <div class="code">
            <strong>Test: Get publications using pm_publication_id meta</strong><br>
            <?php
            if (!empty($team_members) && isset($team_members[0])) {
                $test_member_id = $team_members[0]->ID;
                $pub_ids = get_post_meta($test_member_id, 'pm_publication_id', false);

                if (!empty($pub_ids)) {
                    $test_query2 = new WP_Query(array(
                        'post_type' => 'publication',
                        'post__in' => $pub_ids,
                        'posts_per_page' => -1
                    ));

                    echo 'Query with post__in for team member ID ' . $test_member_id . ': ';
                    echo $test_query2->found_posts > 0 ? '<span class="success">✓ Found ' . $test_query2->found_posts . ' publications</span>' : '<span class="error">✗ No publications found</span>';
                    wp_reset_postdata();
                } else {
                    echo '<span class="error">✗ No publication IDs found</span>';
                }
            }
            ?>
        </div>

        <h2>🔧 Actions</h2>
        <a href="<?php echo admin_url('edit.php?post_type=publication'); ?>" class="button">Go to Publications</a>
        <a href="<?php echo admin_url('edit.php?post_type=publication&page=pm-settings'); ?>" class="button">Settings</a>
        <a href="bulk-process.php" class="button">Bulk Process</a>

        <div class="info" style="margin-top: 30px;">
            <h3>💡 How to Use in Bricks Builder</h3>
            <p><strong>On Team Member Page - Query Publications:</strong></p>
            <ol>
                <li>Add Query Loop element</li>
                <li>Query Type: Posts</li>
                <li>Post Type: Publication</li>
                <li>The plugin will automatically filter to show only this member's publications</li>
            </ol>

            <p><strong>Alternative - Manual Meta Query:</strong></p>
            <ol>
                <li>Meta Query → Key: <code>pm_publication_id</code></li>
                <li>Value: <code>{post_id}</code></li>
                <li>Compare: = (equals)</li>
            </ol>

            <p><strong>Display Authors with Links:</strong></p>
            <p>Use dynamic data: <code>{post_meta_pm_authors}</code></p>
        </div>
    </div>
</body>

</html>