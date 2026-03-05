<?php

/**
 * Bricks Builder Integration
 * Handles all Bricks Builder related functionality including:
 * - Dynamic data filters for publication fields
 * - Custom "Team Member Publications" query loop type
 * - Auto-filtering standard publication queries on team member pages
 *
 * @package Publications_Manager
 * @since 2.0.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PM_Bricks_Integration
{
    /**
     * Custom query loop type identifier
     */
    const QUERY_LOOP_TYPE = 'pm_team_publications';

    /**
     * Initialize Bricks integration
     */
    public static function init()
    {
        // Only load if Bricks is active
        if (!defined('BRICKS_VERSION')) {
            return;
        }

        // Filter dynamic data rendering
        add_filter('bricks/dynamic_data/render_content', array(__CLASS__, 'filter_dynamic_data'), 20, 3);
        add_filter('bricks/dynamic_data/render_tag', array(__CLASS__, 'filter_dynamic_data'), 20, 3);
        add_filter('bricks/dynamic_data/post_meta', array(__CLASS__, 'filter_post_meta'), 20, 3);

        // Filter term meta for author taxonomy custom fields
        add_filter('bricks/dynamic_data/term_meta', array(__CLASS__, 'filter_term_meta'), 20, 3);

        // Register custom query loop type: "Team Member Publications"
        add_filter('bricks/setup/control_options', array(__CLASS__, 'register_query_loop_type'));
        add_filter('bricks/query/loop_object', array(__CLASS__, 'set_loop_object'), 10, 3);
        add_filter('bricks/query/loop_object_id', array(__CLASS__, 'set_loop_object_id'), 10, 3);

        // Custom query loop controls
        add_filter('bricks/elements/container/controls', array(__CLASS__, 'add_query_controls'));
        add_filter('bricks/elements/div/controls', array(__CLASS__, 'add_query_controls'));
        add_filter('bricks/elements/block/controls', array(__CLASS__, 'add_query_controls'));

        // Run our custom query
        add_filter('bricks/query/run', array(__CLASS__, 'run_team_publications_query'), 10, 2);

        // Auto-filter standard publication WP_Query on team member pages
        add_filter('bricks/query/run', array(__CLASS__, 'filter_team_publications_query'), 15, 2);
    }

    /**
     * Register "Team Member Publications" as a custom query loop type
     * Appears in Bricks query type dropdown alongside Posts, Terms, Users, etc.
     *
     * @param array $control_options Bricks control options
     * @return array Modified control options
     */
    public static function register_query_loop_type($control_options)
    {
        $control_options['queryTypes'][self::QUERY_LOOP_TYPE] = esc_html__('Team Member Publications', 'publications-manager');

        return $control_options;
    }

    /**
     * Add custom controls for the Team Member Publications query type
     * These appear in the Bricks query settings panel when the custom type is selected
     *
     * @param array $controls Element controls
     * @return array Modified controls
     */
    public static function add_query_controls($controls)
    {
        // Info text explaining the query type
        $controls['pm_team_pub_info'] = array(
            'group'    => 'query',
            'label'    => esc_html__('Team Member Publications', 'publications-manager'),
            'type'     => 'info',
            'content'  => esc_html__('Displays all publications linked to a team member via the Authors taxonomy. On a team member single page, publications are detected automatically.', 'publications-manager'),
            'required' => array(
                array('query.objectType', '=', self::QUERY_LOOP_TYPE),
            ),
        );

        // Optional: Specific team member ID (for use outside of team member single pages)
        $controls['pm_team_member_id'] = array(
            'group'       => 'query',
            'label'       => esc_html__('Team Member ID', 'publications-manager'),
            'type'        => 'number',
            'placeholder' => esc_html__('Auto-detect from current page', 'publications-manager'),
            'description' => esc_html__('Leave empty to auto-detect on team member single pages. Set a specific ID to show publications for a particular team member on any page.', 'publications-manager'),
            'required'    => array(
                array('query.objectType', '=', self::QUERY_LOOP_TYPE),
            ),
        );

        // Posts per page
        $controls['pm_team_pub_per_page'] = array(
            'group'       => 'query',
            'label'       => esc_html__('Publications Per Page', 'publications-manager'),
            'type'        => 'number',
            'placeholder' => '-1',
            'description' => esc_html__('Number of publications to show. -1 for all.', 'publications-manager'),
            'required'    => array(
                array('query.objectType', '=', self::QUERY_LOOP_TYPE),
            ),
        );

        // Order by
        $controls['pm_team_pub_orderby'] = array(
            'group'       => 'query',
            'label'       => esc_html__('Order By', 'publications-manager'),
            'type'        => 'select',
            'options'     => array(
                'pm_year'  => esc_html__('Publication Year', 'publications-manager'),
                'date'     => esc_html__('Publish Date', 'publications-manager'),
                'title'    => esc_html__('Title', 'publications-manager'),
                'modified' => esc_html__('Modified Date', 'publications-manager'),
            ),
            'placeholder' => esc_html__('Publication Year', 'publications-manager'),
            'required'    => array(
                array('query.objectType', '=', self::QUERY_LOOP_TYPE),
            ),
        );

        // Order direction
        $controls['pm_team_pub_order'] = array(
            'group'       => 'query',
            'label'       => esc_html__('Order', 'publications-manager'),
            'type'        => 'select',
            'options'     => array(
                'DESC' => esc_html__('Descending (newest first)', 'publications-manager'),
                'ASC'  => esc_html__('Ascending (oldest first)', 'publications-manager'),
            ),
            'placeholder' => esc_html__('Descending', 'publications-manager'),
            'required'    => array(
                array('query.objectType', '=', self::QUERY_LOOP_TYPE),
            ),
        );

        // Filter by publication type
        $controls['pm_team_pub_type'] = array(
            'group'       => 'query',
            'label'       => esc_html__('Filter by Publication Type', 'publications-manager'),
            'type'        => 'select',
            'options'     => self::get_publication_type_options(),
            'placeholder' => esc_html__('All types', 'publications-manager'),
            'required'    => array(
                array('query.objectType', '=', self::QUERY_LOOP_TYPE),
            ),
        );

        return $controls;
    }

    /**
     * Get publication type options for the select control
     *
     * @return array Type slug => label pairs
     */
    private static function get_publication_type_options()
    {
        $options = array('' => esc_html__('All types', 'publications-manager'));

        if (class_exists('PM_Publication_Types')) {
            $types = PM_Publication_Types::get_all();
            foreach ($types as $slug => $data) {
                $label = isset($data['i18n_singular']) ? $data['i18n_singular'] : $slug;
                $options[$slug] = $label;
            }
        }

        return $options;
    }

    /**
     * Run the custom Team Member Publications query
     * This executes a WP_Query filtered by pm_author taxonomy terms linked to the team member
     *
     * @param mixed $results Query results
     * @param object $query_obj Bricks query object
     * @return mixed Query results (array of WP_Post objects)
     */
    public static function run_team_publications_query($results, $query_obj)
    {
        // Only handle our custom query type
        if ($query_obj->object_type !== self::QUERY_LOOP_TYPE) {
            return $results;
        }

        $settings = $query_obj->settings;

        // Determine team member ID
        $team_member_id = self::get_team_member_id_from_settings($settings);

        if (!$team_member_id) {
            return array();
        }

        // Find author term IDs linked to this team member
        $author_term_ids = self::get_author_terms_for_team_member($team_member_id);

        if (empty($author_term_ids)) {
            return array();
        }

        // Build WP_Query args
        $per_page = isset($settings['pm_team_pub_per_page']) && $settings['pm_team_pub_per_page'] !== ''
            ? intval($settings['pm_team_pub_per_page'])
            : -1;

        $orderby = !empty($settings['pm_team_pub_orderby']) ? $settings['pm_team_pub_orderby'] : 'pm_year';
        $order   = !empty($settings['pm_team_pub_order']) ? $settings['pm_team_pub_order'] : 'DESC';

        $query_args = array(
            'post_type'      => 'publication',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'pm_author',
                    'field'    => 'term_id',
                    'terms'    => $author_term_ids,
                    'operator' => 'IN',
                ),
            ),
        );

        // Handle ordering
        if ($orderby === 'pm_year') {
            $query_args['meta_key'] = 'pm_year';
            $query_args['orderby']  = 'meta_value_num';
        } else {
            $query_args['orderby'] = $orderby;
        }
        $query_args['order'] = $order;

        // Filter by publication type if set
        if (!empty($settings['pm_team_pub_type'])) {
            $query_args['meta_query'] = array(
                array(
                    'key'   => 'pm_type',
                    'value' => sanitize_text_field($settings['pm_team_pub_type']),
                ),
            );
        }

        // Allow external filtering of the query args
        $query_args = apply_filters('pm/bricks/team_publications_query_args', $query_args, $team_member_id, $settings);

        $query = new WP_Query($query_args);

        // Store query for pagination support
        $query_obj->query_result = $query;

        return $query->posts;
    }

    /**
     * Set the loop object (WP_Post) for each iteration of the custom query loop
     *
     * @param mixed $loop_object Current loop object
     * @param string $loop_key Current loop key/index
     * @param object $query_obj Bricks query object
     * @return mixed The post object for this iteration
     */
    public static function set_loop_object($loop_object, $loop_key, $query_obj)
    {
        if ($query_obj->object_type !== self::QUERY_LOOP_TYPE) {
            return $loop_object;
        }

        $results = $query_obj->results;

        if (isset($results[$loop_key]) && $results[$loop_key] instanceof WP_Post) {
            // Set global post so dynamic data tags like {post_title}, {cf_pm_*} work correctly
            global $post;
            $post = $results[$loop_key];
            setup_postdata($post);
            return $post;
        }

        return $loop_object;
    }

    /**
     * Set the loop object ID for each iteration of the custom query loop
     *
     * @param int $loop_object_id Current loop object ID
     * @param string $loop_key Current loop key/index
     * @param object $query_obj Bricks query object
     * @return int The post ID for this iteration
     */
    public static function set_loop_object_id($loop_object_id, $loop_key, $query_obj)
    {
        if ($query_obj->object_type !== self::QUERY_LOOP_TYPE) {
            return $loop_object_id;
        }

        $results = $query_obj->results;

        if (isset($results[$loop_key]) && $results[$loop_key] instanceof WP_Post) {
            return $results[$loop_key]->ID;
        }

        return $loop_object_id;
    }

    /**
     * Filter Bricks dynamic data for pm_authors, pm_type, and pm_url fields
     */
    public static function filter_dynamic_data($content, $post = null, $context = array())
    {
        if (!$post) {
            $post = get_post();
        }

        if (!is_object($post) || $post->post_type !== 'publication') {
            return $content;
        }

        if (is_array($context) && isset($context['tag'])) {
            if (strpos($context['tag'], 'pm_type') !== false || strpos($context['tag'], 'post_meta:pm_type') !== false) {
                return pm_get_formatted_type($post->ID);
            }

            if (strpos($context['tag'], 'pm_authors') !== false || strpos($context['tag'], 'post_meta:pm_authors') !== false) {
                return PM_Author_Taxonomy::get_authors_html($post->ID);
            }

            if (strpos($context['tag'], 'pm_url') !== false || strpos($context['tag'], 'post_meta:pm_url') !== false) {
                return self::get_publication_url($post->ID);
            }
        }

        // Check if content matches raw type value
        $raw_type = get_post_meta($post->ID, 'pm_type', true);
        if (!empty($raw_type) && $content === $raw_type) {
            return pm_get_formatted_type($post->ID);
        }

        return $content;
    }

    /**
     * Filter post meta specifically
     * Handles: pm_authors (taxonomy-based), pm_type (formatted label), pm_url (smart fallback)
     *
     * Note: In our custom query loop (Team Member Publications), Bricks may pass
     * the team member's post ID instead of the publication's post ID for link URL fields.
     * We detect this by checking if the global $post is a publication (set by set_loop_object).
     */
    public static function filter_post_meta($meta_value, $post_id, $meta_key)
    {
        // Only intercept our pm_ keys
        if (!in_array($meta_key, array('pm_authors', 'pm_type', 'pm_url'), true)) {
            return $meta_value;
        }

        // If the given post_id is not a publication, check if we're inside our custom
        // query loop where the global $post is the correct publication object.
        // This happens when Bricks resolves {cf_pm_url} in link URL fields using
        // the page's post ID instead of the loop item's post ID.
        if (get_post_type($post_id) !== 'publication') {
            global $post;
            if ($post && is_object($post) && $post->post_type === 'publication') {
                $post_id = $post->ID;
            } else {
                return $meta_value;
            }
        }

        if ($meta_key === 'pm_authors') {
            return PM_Author_Taxonomy::get_authors_html($post_id);
        }

        if ($meta_key === 'pm_type') {
            return pm_get_formatted_type($post_id);
        }

        if ($meta_key === 'pm_url') {
            return self::get_publication_url($post_id);
        }

        return $meta_value;
    }

    /**
     * Get the best available URL for a publication
     * Priority: pm_url meta → DOI URL → publication permalink
     *
     * @param int $post_id Publication post ID
     * @return string URL
     */
    public static function get_publication_url($post_id)
    {
        // 1. Check pm_url meta field (external URL)
        $url = get_post_meta($post_id, 'pm_url', true);
        if (!empty($url)) {
            return esc_url($url);
        }

        // 2. Construct URL from DOI if available
        $doi = get_post_meta($post_id, 'pm_doi', true);
        if (!empty($doi)) {
            return esc_url('https://doi.org/' . $doi);
        }

        // 3. Fall back to the publication's WordPress permalink
        return get_permalink($post_id);
    }

    /**
     * Filter term meta for author taxonomy
     * Makes cf_pm_author_team_url accessible in Bricks Builder
     */
    public static function filter_term_meta($meta_value, $term_id, $meta_key)
    {
        // Check if this is an author term
        $term = get_term($term_id);
        if (!$term || is_wp_error($term) || $term->taxonomy !== 'pm_author') {
            return $meta_value;
        }

        // Return the stored URL for cf_pm_author_team_url
        if ($meta_key === 'pm_author_team_url') {
            $stored_url = get_term_meta($term_id, 'pm_author_team_url', true);
            return $stored_url ? $stored_url : $meta_value;
        }

        return $meta_value;
    }

    /**
     * Auto-filter standard publication WP_Query on team member single pages
     * When a Bricks query loop uses WP_Query with post_type=publication on a team member page,
     * this automatically filters it to only show that team member's publications.
     *
     * @param mixed $results Query results
     * @param object $query_obj Bricks query object
     * @return mixed Filtered results
     */
    public static function filter_team_publications_query($results, $query_obj)
    {
        // Skip our custom query type (handled by run_team_publications_query)
        if (isset($query_obj->object_type) && $query_obj->object_type === self::QUERY_LOOP_TYPE) {
            return $results;
        }

        // Only filter WP_Query for publications
        if (!isset($query_obj->settings['post_type']) || $query_obj->settings['post_type'] !== 'publication') {
            return $results;
        }

        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        if (!is_singular($team_cpt_slug)) {
            return $results;
        }

        $team_member_id = get_the_ID();

        if (!$team_member_id) {
            return $results;
        }

        // Find author term IDs linked to this team member
        $author_term_ids = self::get_author_terms_for_team_member($team_member_id);

        if (!empty($author_term_ids)) {
            // Add tax_query to filter by the team member's author terms
            if (!isset($query_obj->query_vars['tax_query'])) {
                $query_obj->query_vars['tax_query'] = array();
            }

            $query_obj->query_vars['tax_query'][] = array(
                'taxonomy' => 'pm_author',
                'field'    => 'term_id',
                'terms'    => $author_term_ids,
                'operator' => 'IN',
            );
        } else {
            // No author terms found - return no results
            $query_obj->query_vars['post__in'] = array(0);
        }

        return $results;
    }

    /**
     * Get the team member ID from query settings or auto-detect from current page
     *
     * @param array $settings Query settings from Bricks
     * @return int|false Team member post ID or false
     */
    private static function get_team_member_id_from_settings($settings)
    {
        // Check if a specific team member ID was provided in the control
        if (!empty($settings['pm_team_member_id'])) {
            $id = intval($settings['pm_team_member_id']);
            if ($id > 0 && get_post_status($id)) {
                return $id;
            }
        }

        // Auto-detect: check if we're on a team member single page
        $team_cpt_slug = get_option('pm_team_cpt_slug', 'team_member');

        if (is_singular($team_cpt_slug)) {
            return get_the_ID();
        }

        // Check the global post as fallback (useful in Bricks editor preview)
        $post = get_post();
        if ($post && $post->post_type === $team_cpt_slug) {
            return $post->ID;
        }

        return false;
    }

    /**
     * Get pm_author taxonomy term IDs linked to a team member
     * Looks up author terms that have pm_team_member_id pointing to this team member
     *
     * @param int $team_member_id Team member post ID
     * @return array Array of term IDs
     */
    private static function get_author_terms_for_team_member($team_member_id)
    {
        // Primary method: get pm_author_term_id values stored on the team member
        $term_ids = get_post_meta($team_member_id, 'pm_author_term_id', false);

        if (!empty($term_ids)) {
            // Validate that these terms still exist and are linked
            $valid_ids = array();
            foreach ($term_ids as $term_id) {
                $term = get_term(intval($term_id), 'pm_author');
                if ($term && !is_wp_error($term)) {
                    $valid_ids[] = intval($term_id);
                }
            }
            if (!empty($valid_ids)) {
                return $valid_ids;
            }
        }

        // Fallback: query terms that have this team member ID in their meta
        $terms = get_terms(array(
            'taxonomy'   => 'pm_author',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'   => 'pm_team_member_id',
                    'value' => $team_member_id,
                    'type'  => 'NUMERIC',
                ),
            ),
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            return wp_list_pluck($terms, 'term_id');
        }

        return array();
    }
}
