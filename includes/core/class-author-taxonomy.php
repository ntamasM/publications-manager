<?php

/**
 * Author Taxonomy Registration
 * Handles publication authors as a custom taxonomy
 *
 * @package Publications_Manager
 * @since 2.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PM_Author_Taxonomy
{
    /**
     * Initialize the taxonomy
     */
    public static function init()
    {
        // Register taxonomy immediately, not on another hook
        self::register_taxonomy();

        // Register term meta for REST API and custom field access
        self::register_term_meta();

        // Add term meta for team member linking
        add_action('pm_author_add_form_fields', array(__CLASS__, 'add_term_fields'));
        add_action('pm_author_edit_form_fields', array(__CLASS__, 'edit_term_fields'));
        add_action('created_pm_author', array(__CLASS__, 'save_term_fields'));
        add_action('edited_pm_author', array(__CLASS__, 'save_term_fields'));

        // Add custom columns to taxonomy list
        add_filter('manage_edit-pm_author_columns', array(__CLASS__, 'add_columns'));
        add_filter('manage_pm_author_custom_column', array(__CLASS__, 'column_content'), 10, 3);
    }

    /**
     * Register the author taxonomy
     */
    public static function register_taxonomy()
    {
        $labels = array(
            'name'              => _x('Authors', 'taxonomy general name', 'publications-manager'),
            'singular_name'     => _x('Author', 'taxonomy singular name', 'publications-manager'),
            'search_items'      => __('Search Authors', 'publications-manager'),
            'all_items'         => __('All Authors', 'publications-manager'),
            'parent_item'       => __('Parent Author', 'publications-manager'),
            'parent_item_colon' => __('Parent Author:', 'publications-manager'),
            'edit_item'         => __('Edit Author', 'publications-manager'),
            'update_item'       => __('Update Author', 'publications-manager'),
            'add_new_item'      => __('Add New Author', 'publications-manager'),
            'new_item_name'     => __('New Author Name', 'publications-manager'),
            'menu_name'         => __('Authors', 'publications-manager'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_in_menu'      => 'edit.php?post_type=publication',
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'publication-author'),
            'show_in_quick_edit' => false,
            'meta_box_cb'       => false, // We'll use a custom meta box
        );

        register_taxonomy('pm_author', array('publication'), $args);
    }

    /**
     * Add fields when creating new author term
     */
    public static function add_term_fields($taxonomy)
    {
?>
        <div class="form-field">
            <label for="pm_team_member_id"><?php _e('Linked Team Member', 'publications-manager'); ?></label>
            <?php
            $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
            if (post_type_exists($team_cpt_slug)) {
                $team_members = get_posts(array(
                    'post_type' => $team_cpt_slug,
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
            ?>
                <select name="pm_team_member_id" id="pm_team_member_id">
                    <option value=""><?php _e('None', 'publications-manager'); ?></option>
                    <?php foreach ($team_members as $member) : ?>
                        <option value="<?php echo esc_attr($member->ID); ?>">
                            <?php echo esc_html($member->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Link this author to a team member profile', 'publications-manager'); ?></p>
            <?php
            } else {
                echo '<p>' . __('Team member post type not found. Configure in Publications > Settings', 'publications-manager') . '</p>';
            }
            ?>
        </div>
    <?php
    }

    /**
     * Add fields when editing author term
     */
    public static function edit_term_fields($term)
    {
        $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);
    ?>
        <tr class="form-field">
            <th scope="row">
                <label for="pm_team_member_id"><?php _e('Linked Team Member', 'publications-manager'); ?></label>
            </th>
            <td>
                <?php
                $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');
                if (post_type_exists($team_cpt_slug)) {
                    $team_members = get_posts(array(
                        'post_type' => $team_cpt_slug,
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                ?>
                    <select name="pm_team_member_id" id="pm_team_member_id">
                        <option value=""><?php _e('None', 'publications-manager'); ?></option>
                        <?php foreach ($team_members as $member) : ?>
                            <option value="<?php echo esc_attr($member->ID); ?>" <?php selected($team_member_id, $member->ID); ?>>
                                <?php echo esc_html($member->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Link this author to a team member profile. Author name will link to team member page.', 'publications-manager'); ?></p>
                <?php
                } else {
                    echo '<p>' . __('Team member post type not found. Configure in Publications > Settings', 'publications-manager') . '</p>';
                }
                ?>
            </td>
        </tr>
<?php
    }

    /**
     * Save term fields
     */
    public static function save_term_fields($term_id)
    {
        if (isset($_POST['pm_team_member_id'])) {
            $team_member_id = absint($_POST['pm_team_member_id']);

            if ($team_member_id > 0) {
                update_term_meta($term_id, 'pm_team_member_id', $team_member_id);

                // Store the team member URL for Bricks Builder custom field access
                $team_member_url = get_permalink($team_member_id);
                update_term_meta($term_id, 'pm_author_team_url', $team_member_url);

                // Create bidirectional link on team member
                $author_name = get_term($term_id)->name;
                $existing_links = get_post_meta($team_member_id, 'pm_author_term_id', false);

                if (!in_array($term_id, $existing_links)) {
                    add_post_meta($team_member_id, 'pm_author_term_id', $term_id);
                }
            } else {
                // Remove link if deselected
                $old_member_id = get_term_meta($term_id, 'pm_team_member_id', true);
                if ($old_member_id) {
                    delete_post_meta($old_member_id, 'pm_author_term_id', $term_id);
                }
                delete_term_meta($term_id, 'pm_team_member_id');
                delete_term_meta($term_id, 'pm_author_team_url');
            }
        }
    }

    /**
     * Add custom columns
     */
    public static function add_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['name'] = $columns['name'];
        $new_columns['team_member'] = __('Team Member', 'publications-manager');
        $new_columns['posts'] = $columns['posts'];

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public static function column_content($content, $column_name, $term_id)
    {
        if ($column_name === 'team_member') {
            $team_member_id = get_term_meta($term_id, 'pm_team_member_id', true);

            if ($team_member_id) {
                $team_member = get_post($team_member_id);
                if ($team_member) {
                    $edit_link = get_edit_post_link($team_member_id);
                    return '<a href="' . esc_url($edit_link) . '">' . esc_html($team_member->post_title) . '</a>';
                }
            }

            return '—';
        }

        return $content;
    }

    /**
     * Find or create author term and link to team member
     * 
     * @param string $author_name Author name
     * @return int Term ID
     */
    public static function get_or_create_author_term($author_name)
    {
        $author_name = trim($author_name);

        if (empty($author_name)) {
            error_log('PM Author Taxonomy: Empty author name provided');
            return 0;
        }

        // Check if term exists
        $term = get_term_by('name', $author_name, 'pm_author');

        if ($term) {
            error_log('PM Author Taxonomy: Found existing term for: ' . $author_name . ' (ID: ' . $term->term_id . ')');
            return $term->term_id;
        }

        // Create new term
        $result = wp_insert_term($author_name, 'pm_author');

        if (is_wp_error($result)) {
            error_log('PM Author Taxonomy: Error creating term for ' . $author_name . ': ' . $result->get_error_message());
            return 0;
        }

        $term_id = $result['term_id'];
        error_log('PM Author Taxonomy: Created new term for: ' . $author_name . ' (ID: ' . $term_id . ')');

        // Note: Team member linking is now done manually via the Authors taxonomy admin UI
        // Auto-linking removed in v2.1.0 to give users full control over connections

        return $term_id;
    }

    /**
     * Get authors with team member links for display
     * 
     * @param int $post_id Publication ID
     * @return string HTML with author names and links
     */
    public static function get_authors_html($post_id)
    {
        $terms = get_the_terms($post_id, 'pm_author');

        if (!$terms || is_wp_error($terms)) {
            return '';
        }

        $authors_html = array();

        foreach ($terms as $term) {
            $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);

            if ($team_member_id) {
                $url = get_permalink($team_member_id);
                $authors_html[] = '<a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a>';
            } else {
                $authors_html[] = esc_html($term->name);
            }
        }

        return implode(', ', $authors_html);
    }

    /**
     * Get team member URL for an author term
     * Returns the permalink to the linked team member post
     * 
     * @param int $term_id Author term ID
     * @return string Team member URL or empty string
     */
    public static function get_author_team_url($term_id)
    {
        $team_member_id = get_term_meta($term_id, 'pm_team_member_id', true);

        if ($team_member_id && get_post_status($team_member_id) === 'publish') {
            return get_permalink($team_member_id);
        }

        return '';
    }

    /**
     * Register term meta for REST API and custom field access
     */
    public static function register_term_meta()
    {
        // Register the team member URL as accessible term meta for Bricks Builder
        register_term_meta('pm_author', 'pm_author_team_url', array(
            'type' => 'string',
            'description' => __('Team member URL for this author', 'publications-manager'),
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));

        // Migrate existing links to add cf_author_team_url
        self::maybe_migrate_author_urls();
    }

    /**
     * Migrate existing author-team member links to include cf_author_team_url
     * Only runs once using a transient flag
     */
    private static function maybe_migrate_author_urls()
    {
        // Check if migration has already been done
        if (get_transient('pm_author_url_migrated_v2.2')) {
            return;
        }

        // Get all author terms that have team member links but no URL
        $terms = get_terms(array(
            'taxonomy' => 'pm_author',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'pm_team_member_id',
                    'compare' => 'EXISTS'
                )
            )
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $team_member_id = get_term_meta($term->term_id, 'pm_team_member_id', true);
                $existing_url = get_term_meta($term->term_id, 'pm_author_team_url', true);

                // Only update if we don't have a URL yet
                if ($team_member_id && empty($existing_url)) {
                    $team_member_url = get_permalink($team_member_id);
                    if ($team_member_url && $team_member_url !== '') {
                        update_term_meta($term->term_id, 'pm_author_team_url', $team_member_url);
                    }
                }
            }
        }

        // Set transient to prevent re-running (expires in 1 day, but we check it exists not value)
        set_transient('pm_author_url_migrated_v2.2', true, DAY_IN_SECONDS);
    }
}
