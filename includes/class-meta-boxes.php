<?php

/**
 * Meta Boxes for Publication Custom Post Type
 * Implements all teachPress fields
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class PM_Meta_Boxes
{

    /**
     * Initialize
     */
    public static function init()
    {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_publication', array(__CLASS__, 'save_meta_boxes'), 10, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Add meta boxes
     */
    public static function add_meta_boxes()
    {
        // Publication Type & Basic Info
        add_meta_box(
            'pm_publication_type',
            __('Publication Type & Basic Information', 'publications-manager'),
            array(__CLASS__, 'render_type_metabox'),
            'publication',
            'normal',
            'high'
        );

        // Authors & Editors
        add_meta_box(
            'pm_authors',
            __('Authors & Editors', 'publications-manager'),
            array(__CLASS__, 'render_authors_metabox'),
            'publication',
            'normal',
            'high'
        );

        // Publication Details (dynamic based on type)
        add_meta_box(
            'pm_details',
            __('Publication Details', 'publications-manager'),
            array(__CLASS__, 'render_details_metabox'),
            'publication',
            'normal',
            'default'
        );

        // Additional Information
        add_meta_box(
            'pm_additional',
            __('Additional Information', 'publications-manager'),
            array(__CLASS__, 'render_additional_metabox'),
            'publication',
            'normal',
            'default'
        );

        // Abstract & Notes
        add_meta_box(
            'pm_abstract_notes',
            __('Abstract & Notes', 'publications-manager'),
            array(__CLASS__, 'render_abstract_metabox'),
            'publication',
            'normal',
            'default'
        );

        // URLs & DOI
        add_meta_box(
            'pm_urls',
            __('URLs & Digital Object Identifier', 'publications-manager'),
            array(__CLASS__, 'render_urls_metabox'),
            'publication',
            'side',
            'default'
        );

        // Status & Metadata
        add_meta_box(
            'pm_status',
            __('Publication Status', 'publications-manager'),
            array(__CLASS__, 'render_status_metabox'),
            'publication',
            'side',
            'default'
        );
    }

    /**
     * Enqueue scripts for dynamic field visibility
     */
    public static function enqueue_scripts($hook)
    {
        global $post;

        if (('post.php' === $hook || 'post-new.php' === $hook) &&
            isset($post->post_type) && 'publication' === $post->post_type
        ) {

            wp_add_inline_script('pm-admin', '
                jQuery(document).ready(function($) {
                    function updateFieldVisibility() {
                        var selectedType = $("#pm_type").val();
                        var typeData = ' . json_encode(PM_Publication_Types::get_all()) . ';
                        
                        // Hide all optional fields first
                        $(".pm-field-wrapper").removeClass("pm-recommended");
                        
                        if (typeData[selectedType] && typeData[selectedType].default_fields) {
                            var defaultFields = typeData[selectedType].default_fields;
                            
                            $.each(defaultFields, function(index, field) {
                                $("#pm_" + field).closest(".pm-field-wrapper").addClass("pm-recommended");
                            });
                        }
                    }
                    
                    $("#pm_type").on("change", updateFieldVisibility);
                    updateFieldVisibility();
                });
            ');
        }
    }

    /**
     * Render Type metabox
     */
    public static function render_type_metabox($post)
    {
        wp_nonce_field('pm_save_meta', 'pm_meta_nonce');

        $type = get_post_meta($post->ID, '_pm_type', true);
        $bibtex = get_post_meta($post->ID, '_pm_bibtex', true);
        $award = get_post_meta($post->ID, '_pm_award', true);
        $date = get_post_meta($post->ID, '_pm_date', true);

?>
        <table class="form-table">
            <tr>
                <th><label for="pm_type"><?php _e('Publication Type', 'publications-manager'); ?> *</label></th>
                <td>
                    <select name="pm_type" id="pm_type" class="regular-text" required>
                        <option value=""><?php _e('Select Type', 'publications-manager'); ?></option>
                        <?php echo PM_Publication_Types::get_options($type); ?>
                    </select>
                    <p class="description"><?php _e('Select the type of publication. Fields will be adjusted based on your selection.', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_bibtex"><?php _e('BibTeX Key', 'publications-manager'); ?> *</label></th>
                <td>
                    <input type="text" name="pm_bibtex" id="pm_bibtex" value="<?php echo esc_attr($bibtex); ?>" class="regular-text" required />
                    <p class="description"><?php _e('Unique BibTeX citation key (e.g., Smith2024)', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_date"><?php _e('Publication Date', 'publications-manager'); ?> *</label></th>
                <td>
                    <input type="date" name="pm_date" id="pm_date" value="<?php echo esc_attr($date); ?>" class="regular-text" required />
                    <p class="description"><?php _e('Date of publication', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_award"><?php _e('Award', 'publications-manager'); ?></label></th>
                <td>
                    <input type="text" name="pm_award" id="pm_award" value="<?php echo esc_attr($award); ?>" class="regular-text" />
                    <p class="description"><?php _e('Any award or distinction received', 'publications-manager'); ?></p>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render Authors metabox
     */
    public static function render_authors_metabox($post)
    {
        $author = get_post_meta($post->ID, '_pm_author', true);
        $editor = get_post_meta($post->ID, '_pm_editor', true);

    ?>
        <table class="form-table">
            <tr>
                <th><label for="pm_author"><?php _e('Authors', 'publications-manager'); ?> *</label></th>
                <td>
                    <textarea name="pm_author" id="pm_author" rows="3" class="large-text" required><?php echo esc_textarea($author); ?></textarea>
                    <p class="description"><?php _e('List of authors (e.g., Smith, John and Doe, Jane)', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_editor"><?php _e('Editors', 'publications-manager'); ?></label></th>
                <td>
                    <textarea name="pm_editor" id="pm_editor" rows="3" class="large-text"><?php echo esc_textarea($editor); ?></textarea>
                    <p class="description"><?php _e('List of editors (if applicable)', 'publications-manager'); ?></p>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render Details metabox with all teachPress fields
     */
    public static function render_details_metabox($post)
    {
        // Get all field values
        $fields = array(
            'journal'       => get_post_meta($post->ID, '_pm_journal', true),
            'booktitle'     => get_post_meta($post->ID, '_pm_booktitle', true),
            'issuetitle'    => get_post_meta($post->ID, '_pm_issuetitle', true),
            'volume'        => get_post_meta($post->ID, '_pm_volume', true),
            'number'        => get_post_meta($post->ID, '_pm_number', true),
            'issue'         => get_post_meta($post->ID, '_pm_issue', true),
            'pages'         => get_post_meta($post->ID, '_pm_pages', true),
            'chapter'       => get_post_meta($post->ID, '_pm_chapter', true),
            'publisher'     => get_post_meta($post->ID, '_pm_publisher', true),
            'address'       => get_post_meta($post->ID, '_pm_address', true),
            'edition'       => get_post_meta($post->ID, '_pm_edition', true),
            'series'        => get_post_meta($post->ID, '_pm_series', true),
            'institution'   => get_post_meta($post->ID, '_pm_institution', true),
            'organization'  => get_post_meta($post->ID, '_pm_organization', true),
            'school'        => get_post_meta($post->ID, '_pm_school', true),
            'howpublished'  => get_post_meta($post->ID, '_pm_howpublished', true),
            'techtype'      => get_post_meta($post->ID, '_pm_techtype', true),
            'isbn'          => get_post_meta($post->ID, '_pm_isbn', true),
            'crossref'      => get_post_meta($post->ID, '_pm_crossref', true),
            'key'           => get_post_meta($post->ID, '_pm_key', true),
        );

    ?>
        <div class="pm-details-grid">
            <table class="form-table">
                <!-- Journal/Serial Publication Fields -->
                <tr class="pm-field-wrapper">
                    <th><label for="pm_journal"><?php _e('Journal', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_journal" id="pm_journal" value="<?php echo esc_attr($fields['journal']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Name of the journal', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_booktitle"><?php _e('Book Title', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_booktitle" id="pm_booktitle" value="<?php echo esc_attr($fields['booktitle']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Title of the book (for chapters, sections)', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_issuetitle"><?php _e('Issue Title', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_issuetitle" id="pm_issuetitle" value="<?php echo esc_attr($fields['issuetitle']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Title of the specific issue', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <!-- Volume, Number, Issue, Pages -->
                <tr class="pm-field-wrapper">
                    <th><label for="pm_volume"><?php _e('Volume', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_volume" id="pm_volume" value="<?php echo esc_attr($fields['volume']); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_number"><?php _e('Number', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_number" id="pm_number" value="<?php echo esc_attr($fields['number']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Issue number', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_issue"><?php _e('Issue', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_issue" id="pm_issue" value="<?php echo esc_attr($fields['issue']); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_pages"><?php _e('Pages', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_pages" id="pm_pages" value="<?php echo esc_attr($fields['pages']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Page range (e.g., 123-145)', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_chapter"><?php _e('Chapter', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_chapter" id="pm_chapter" value="<?php echo esc_attr($fields['chapter']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Chapter number or title', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <!-- Publisher Information -->
                <tr class="pm-field-wrapper">
                    <th><label for="pm_publisher"><?php _e('Publisher', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_publisher" id="pm_publisher" value="<?php echo esc_attr($fields['publisher']); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_address"><?php _e('Address', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_address" id="pm_address" value="<?php echo esc_attr($fields['address']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Publisher address or location', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_edition"><?php _e('Edition', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_edition" id="pm_edition" value="<?php echo esc_attr($fields['edition']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Edition number (e.g., 2nd, Third)', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_series"><?php _e('Series', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_series" id="pm_series" value="<?php echo esc_attr($fields['series']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Book or journal series', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <!-- Institutional Information -->
                <tr class="pm-field-wrapper">
                    <th><label for="pm_institution"><?php _e('Institution', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_institution" id="pm_institution" value="<?php echo esc_attr($fields['institution']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Sponsoring institution (for tech reports)', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_organization"><?php _e('Organization', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_organization" id="pm_organization" value="<?php echo esc_attr($fields['organization']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Sponsoring organization', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_school"><?php _e('School/University', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_school" id="pm_school" value="<?php echo esc_attr($fields['school']); ?>" class="regular-text" />
                        <p class="description"><?php _e('School where thesis was written', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <!-- Other Fields -->
                <tr class="pm-field-wrapper">
                    <th><label for="pm_howpublished"><?php _e('How Published', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_howpublished" id="pm_howpublished" value="<?php echo esc_attr($fields['howpublished']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Method of publication (for unusual publications)', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_techtype"><?php _e('Type', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_techtype" id="pm_techtype" value="<?php echo esc_attr($fields['techtype']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Type of technical report or thesis', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_isbn"><?php _e('ISBN/ISSN', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_isbn" id="pm_isbn" value="<?php echo esc_attr($fields['isbn']); ?>" class="regular-text" />
                        <p class="description"><?php _e('ISBN or ISSN number', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_crossref"><?php _e('Cross Reference', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_crossref" id="pm_crossref" value="<?php echo esc_attr($fields['crossref']); ?>" class="regular-text" />
                        <p class="description"><?php _e('BibTeX cross-reference key', 'publications-manager'); ?></p>
                    </td>
                </tr>

                <tr class="pm-field-wrapper">
                    <th><label for="pm_key"><?php _e('Key', 'publications-manager'); ?></label></th>
                    <td>
                        <input type="text" name="pm_key" id="pm_key" value="<?php echo esc_attr($fields['key']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Hidden field for alphabetizing', 'publications-manager'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Render Additional Information metabox
     */
    public static function render_additional_metabox($post)
    {
        $urldate = get_post_meta($post->ID, '_pm_urldate', true);
        $image_url = get_post_meta($post->ID, '_pm_image_url', true);
        $image_ext = get_post_meta($post->ID, '_pm_image_ext', true);
        $rel_page = get_post_meta($post->ID, '_pm_rel_page', true);
        $import_id = get_post_meta($post->ID, '_pm_import_id', true);

    ?>
        <table class="form-table">
            <tr>
                <th><label for="pm_urldate"><?php _e('URL Access Date', 'publications-manager'); ?></label></th>
                <td>
                    <input type="date" name="pm_urldate" id="pm_urldate" value="<?php echo esc_attr($urldate); ?>" class="regular-text" />
                    <p class="description"><?php _e('Date when the URL was last accessed', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_image_url"><?php _e('Cover Image URL', 'publications-manager'); ?></label></th>
                <td>
                    <input type="url" name="pm_image_url" id="pm_image_url" value="<?php echo esc_attr($image_url); ?>" class="large-text" />
                    <p class="description"><?php _e('URL to publication cover image', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_image_ext"><?php _e('External Image Link', 'publications-manager'); ?></label></th>
                <td>
                    <input type="url" name="pm_image_ext" id="pm_image_ext" value="<?php echo esc_attr($image_ext); ?>" class="large-text" />
                    <p class="description"><?php _e('External link for the image', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_rel_page"><?php _e('Related Page', 'publications-manager'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        'name'              => 'pm_rel_page',
                        'id'                => 'pm_rel_page',
                        'selected'          => $rel_page,
                        'show_option_none'  => __('None', 'publications-manager'),
                        'option_none_value' => '0'
                    ));
                    ?>
                    <p class="description"><?php _e('Link to a related WordPress page', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_import_id"><?php _e('Import ID', 'publications-manager'); ?></label></th>
                <td>
                    <input type="text" name="pm_import_id" id="pm_import_id" value="<?php echo esc_attr($import_id); ?>" class="regular-text" readonly />
                    <p class="description"><?php _e('Unique identifier for imported publications (read-only)', 'publications-manager'); ?></p>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render Abstract metabox
     */
    public static function render_abstract_metabox($post)
    {
        $abstract = get_post_meta($post->ID, '_pm_abstract', true);
        $note = get_post_meta($post->ID, '_pm_note', true);
        $comment = get_post_meta($post->ID, '_pm_comment', true);

    ?>
        <table class="form-table">
            <tr>
                <th><label for="pm_abstract"><?php _e('Abstract', 'publications-manager'); ?></label></th>
                <td>
                    <textarea name="pm_abstract" id="pm_abstract" rows="6" class="large-text"><?php echo esc_textarea($abstract); ?></textarea>
                    <p class="description"><?php _e('Publication abstract or summary', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_note"><?php _e('Note', 'publications-manager'); ?></label></th>
                <td>
                    <textarea name="pm_note" id="pm_note" rows="3" class="large-text"><?php echo esc_textarea($note); ?></textarea>
                    <p class="description"><?php _e('Additional notes or remarks', 'publications-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="pm_comment"><?php _e('Internal Comment', 'publications-manager'); ?></label></th>
                <td>
                    <textarea name="pm_comment" id="pm_comment" rows="3" class="large-text"><?php echo esc_textarea($comment); ?></textarea>
                    <p class="description"><?php _e('Internal comments (not displayed publicly)', 'publications-manager'); ?></p>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * Render URLs metabox
     */
    public static function render_urls_metabox($post)
    {
        $url = get_post_meta($post->ID, '_pm_url', true);
        $doi = get_post_meta($post->ID, '_pm_doi', true);

    ?>
        <p>
            <label for="pm_url"><strong><?php _e('URL', 'publications-manager'); ?></strong></label><br>
            <input type="url" name="pm_url" id="pm_url" value="<?php echo esc_attr($url); ?>" class="widefat" />
            <span class="description"><?php _e('Publication URL', 'publications-manager'); ?></span>
        </p>
        <p>
            <label for="pm_doi"><strong><?php _e('DOI', 'publications-manager'); ?></strong></label><br>
            <input type="text" name="pm_doi" id="pm_doi" value="<?php echo esc_attr($doi); ?>" class="widefat" />
            <span class="description"><?php _e('Digital Object Identifier', 'publications-manager'); ?></span>
        </p>
    <?php
    }

    /**
     * Render Status metabox
     */
    public static function render_status_metabox($post)
    {
        $status = get_post_meta($post->ID, '_pm_status', true);
        if (empty($status)) {
            $status = 'published';
        }

    ?>
        <p>
            <label for="pm_status"><strong><?php _e('Publication Status', 'publications-manager'); ?></strong></label><br>
            <select name="pm_status" id="pm_status" class="widefat">
                <option value="published" <?php selected($status, 'published'); ?>><?php _e('Published', 'publications-manager'); ?></option>
                <option value="forthcoming" <?php selected($status, 'forthcoming'); ?>><?php _e('Forthcoming', 'publications-manager'); ?></option>
                <option value="in_press" <?php selected($status, 'in_press'); ?>><?php _e('In Press', 'publications-manager'); ?></option>
                <option value="submitted" <?php selected($status, 'submitted'); ?>><?php _e('Submitted', 'publications-manager'); ?></option>
                <option value="in_review" <?php selected($status, 'in_review'); ?>><?php _e('In Review', 'publications-manager'); ?></option>
            </select>
        </p>
<?php
    }

    /**
     * Save meta box data
     */
    public static function save_meta_boxes($post_id, $post)
    {
        // Verify nonce
        if (! isset($_POST['pm_meta_nonce']) || ! wp_verify_nonce($_POST['pm_meta_nonce'], 'pm_save_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Define all meta fields
        $meta_fields = array(
            'pm_type',
            'pm_bibtex',
            'pm_award',
            'pm_date',
            'pm_author',
            'pm_editor',
            'pm_journal',
            'pm_booktitle',
            'pm_issuetitle',
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
            'pm_doi',
            'pm_urldate',
            'pm_image_url',
            'pm_image_ext',
            'pm_rel_page',
            'pm_abstract',
            'pm_note',
            'pm_comment',
            'pm_status'
        );

        // Save each field
        foreach ($meta_fields as $field) {
            $meta_key = '_' . $field;

            if (isset($_POST[$field])) {
                $value = $_POST[$field];

                // Sanitize based on field type
                if (in_array($field, array('pm_abstract', 'pm_note', 'pm_comment', 'pm_author', 'pm_editor'))) {
                    $value = sanitize_textarea_field($value);
                } elseif (in_array($field, array('pm_url', 'pm_image_url', 'pm_image_ext'))) {
                    $value = esc_url_raw($value);
                } else {
                    $value = sanitize_text_field($value);
                }

                update_post_meta($post_id, $meta_key, $value);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
}
