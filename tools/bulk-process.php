<?php

/**
 * Publications Manager - Bulk Process Relationships
 * 
 * This utility script processes all existing publications to create
 * team member relationships. Useful when installing the plugin on
 * a site with existing publications.
 * 
 * HOW TO USE:
 * 1. Copy this file to wp-content/plugins/publications-manager/
 * 2. Navigate to: your-site.com/wp-content/plugins/publications-manager/bulk-process.php
 * 3. The script will process all publications
 * 4. Delete this file after use for security
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

?>
<!DOCTYPE html>
<html>

<head>
    <title>Publications Manager - Bulk Process</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f1f1f1;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }

        .success {
            background: #d7f0d8;
            border-left: 4px solid #00a32a;
            padding: 15px;
            margin: 20px 0;
        }

        .info {
            background: #e5f5fa;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 20px 0;
        }

        .warning {
            background: #fcf3cf;
            border-left: 4px solid #f0b849;
            padding: 15px;
            margin: 20px 0;
        }

        .error {
            background: #fbe4e4;
            border-left: 4px solid #d63638;
            padding: 15px;
            margin: 20px 0;
        }

        .stat {
            font-size: 24px;
            font-weight: bold;
            color: #2271b1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f7f7f7;
            font-weight: 600;
        }

        .button {
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-top: 20px;
        }

        .button:hover {
            background: #135e96;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>📚 Publications Manager - Bulk Process Relationships</h1>

        <?php

        if (isset($_GET['process']) && $_GET['process'] === 'run') {
            // Run the bulk process

            $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

            echo '<div class="info">';
            echo '<p><strong>Team CPT Slug:</strong> ' . esc_html($team_cpt_slug) . '</p>';
            echo '</div>';

            // Get all publications
            $publications = get_posts(array(
                'post_type' => 'publication',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            $total = count($publications);
            $processed = 0;
            $linked = 0;
            $errors = 0;

            $results = array();

            foreach ($publications as $publication) {
                $before_links = get_post_meta($publication->ID, 'pm_team_members', true);
                $before_count = is_array($before_links) ? count($before_links) : 0;

                // Process relationships
                pm_process_author_relationships($publication->ID);

                $after_links = get_post_meta($publication->ID, 'pm_team_members', true);
                $after_count = is_array($after_links) ? count($after_links) : 0;

                $authors = get_post_meta($publication->ID, 'pm_authors', false);
                $authors_display = !empty($authors) ? implode(', ', $authors) : '';

                $results[] = array(
                    'id' => $publication->ID,
                    'title' => $publication->post_title,
                    'authors' => $authors_display,
                    'links_before' => $before_count,
                    'links_after' => $after_count,
                    'new_links' => $after_count - $before_count
                );

                $processed++;
                if ($after_count > 0) {
                    $linked++;
                }
            }

            echo '<div class="success">';
            echo '<h2>✅ Processing Complete!</h2>';
            echo '<p class="stat">' . $processed . ' / ' . $total . '</p>';
            echo '<p>Publications processed successfully.</p>';
            echo '<p><strong>Publications with team member links:</strong> ' . $linked . '</p>';
            echo '</div>';

            // Show results table
            if (!empty($results)) {
                echo '<h2>Results</h2>';
                echo '<table>';
                echo '<thead><tr>';
                echo '<th>ID</th>';
                echo '<th>Publication Title</th>';
                echo '<th>Authors</th>';
                echo '<th>Links Before</th>';
                echo '<th>Links After</th>';
                echo '<th>New Links</th>';
                echo '</tr></thead>';
                echo '<tbody>';

                foreach ($results as $result) {
                    $row_class = $result['new_links'] > 0 ? 'style="background:#d7f0d8;"' : '';
                    echo '<tr ' . $row_class . '>';
                    echo '<td>' . $result['id'] . '</td>';
                    echo '<td>' . esc_html($result['title']) . '</td>';
                    echo '<td>' . esc_html(substr($result['authors'], 0, 50)) . '...</td>';
                    echo '<td>' . $result['links_before'] . '</td>';
                    echo '<td>' . $result['links_after'] . '</td>';
                    echo '<td><strong>' . ($result['new_links'] > 0 ? '+' : '') . $result['new_links'] . '</strong></td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            }

            echo '<div class="info">';
            echo '<h3>Next Steps:</h3>';
            echo '<ol>';
            echo '<li>Review the results above to ensure relationships were created correctly.</li>';
            echo '<li>Check a few publication pages to verify author links are working.</li>';
            echo '<li>Test your Bricks Builder Query Loops on team member pages.</li>';
            echo '<li><strong>Important:</strong> Delete this file (bulk-process.php) for security.</li>';
            echo '</ol>';
            echo '</div>';

            echo '<a href="' . admin_url('edit.php?post_type=publication') . '" class="button">Go to Publications</a> ';
            echo '<a href="' . admin_url('edit.php?post_type=publication&page=pm-settings') . '" class="button">Go to Settings</a>';
        } else {
            // Show information and confirmation

            $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

            if (empty($team_cpt_slug)) {
                echo '<div class="warning">';
                echo '<h2>⚠️ Configuration Required</h2>';
                echo '<p>Please configure your Team CPT Slug first:</p>';
                echo '<ol>';
                echo '<li>Go to <strong>Publications → Settings</strong></li>';
                echo '<li>Enter your Team CPT Slug (e.g., team_member)</li>';
                echo '<li>Click Save Settings</li>';
                echo '<li>Return to this page</li>';
                echo '</ol>';
                echo '</div>';
                echo '<a href="' . admin_url('edit.php?post_type=publication&page=pm-settings') . '" class="button">Go to Settings</a>';
            } else {
                // Get stats
                $publication_count = wp_count_posts('publication');
                $total_pubs = $publication_count->publish;

                $team_count = 0;
                if (post_type_exists($team_cpt_slug)) {
                    $team_posts = wp_count_posts($team_cpt_slug);
                    $team_count = $team_posts->publish;
                }

                echo '<div class="info">';
                echo '<h2>ℹ️ About This Tool</h2>';
                echo '<p>This utility will process all existing publications and create relationships with team members based on author names.</p>';
                echo '<h3>What it does:</h3>';
                echo '<ul>';
                echo '<li>Parses author names from publications (comma-separated format)</li>';
                echo '<li>Searches for matching team member post titles</li>';
                echo '<li>Creates bidirectional relationships</li>';
                echo '<li>Stores link data for frontend display</li>';
                echo '</ul>';
                echo '</div>';

                echo '<div class="info">';
                echo '<h3>Current Configuration:</h3>';
                echo '<p><strong>Team CPT Slug:</strong> ' . esc_html($team_cpt_slug) . '</p>';
                echo '<p><strong>Total Publications:</strong> ' . $total_pubs . '</p>';
                echo '<p><strong>Total Team Members:</strong> ' . $team_count . '</p>';
                echo '</div>';

                if ($team_count === 0) {
                    echo '<div class="warning">';
                    echo '<h2>⚠️ No Team Members Found</h2>';
                    echo '<p>No published posts found for post type: <strong>' . esc_html($team_cpt_slug) . '</strong></p>';
                    echo '<p>Please check:</p>';
                    echo '<ul>';
                    echo '<li>The Team CPT Slug is correct in Settings</li>';
                    echo '<li>You have published team member posts</li>';
                    echo '</ul>';
                    echo '</div>';
                }

                echo '<div class="warning">';
                echo '<h3>⚠️ Before You Start:</h3>';
                echo '<ul>';
                echo '<li>Make sure your Team CPT Slug is configured correctly</li>';
                echo '<li>Ensure author names in publications match team member post titles exactly</li>';
                echo '<li>Author format should be: "John Doe, Jane Smith" (comma-separated)</li>';
                echo '<li>Backup your database (recommended for large sites)</li>';
                echo '</ul>';
                echo '</div>';

                echo '<a href="?process=run" class="button">Start Processing Publications</a>';
            }
        }

        ?>

    </div>
</body>

</html>