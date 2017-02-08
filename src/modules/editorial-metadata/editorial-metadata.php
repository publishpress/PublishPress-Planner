<?php
/**
 * @package PublishPress
 * @author PressShack
 *
 * Copyright (c) 2017 PressShack
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists('PP_Editorial_Metadata')) {
    /**
     * class PP_Editorial_Metadata
     * This class gives publishers arbitrary structured content details to go along with every post
     *
     * @author sbressler, danielbachhuber
     *
     * Ways to test and play with this class:
     * 1) Create a new term by selecting Editorial Metadata from the PublishPress settings
     * 2) Edit an existing term (slug, description, etc.)
     * 3) Create a post and assign metadata to it
     * 4) Look at the list of terms again - the count should go up!
     * 5) Play with adding more metadata to a post
     * 6) Clear the metadata for a single term in a post and watch the count go down!
     * 6) Delete a term and note the metadata disappears from posts
     * 7) Re-add the term (same slug) and the metadata returns!
     *
     * Improvements to make:
     * @todo Abstract the permissions check for management to class level
     */
    class PP_Editorial_Metadata extends PP_Module
    {

        /**
         * The name of the taxonomy we're going to register for editorial metadata.
         */
        const metadata_taxonomy         = 'pp_editorial_meta';
        const metadata_postmeta_key = "_pp_editorial_meta";

        public $module_name = 'editorial_metadata';

        private $editorial_metadata_terms_cache = array();

        /**
         * Construct the PP_Editorial_Metadata class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);
            // Register the module with PublishPress
            $args = array(
                'title'                => __('Editorial Metadata', 'publishpress'),
                'short_description'    => __('Click here to customize the extra data that’s tracked for your content.', 'publishpress'),
                'extended_description' => __('Log details on every assignment using configurable editorial metadata. It’s completely customizable; create fields for everything from due date to location to contact information to role assignments.', 'publishpress'),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'editorial-metadata',
                'default_options'      => array(
                    'enabled'    => 'on',
                    'post_types' => array(
                        'post' => 'on',
                        'page' => 'off',
                    ),
                ),
                'messages' => array(
                    'term-added'              => __("Metadata term added.", 'publishpress'),
                    'term-updated'            => __("Metadata term updated.", 'publishpress'),
                    'term-missing'            => __("Metadata term doesn't exist.", 'publishpress'),
                    'term-deleted'            => __("Metadata term deleted.", 'publishpress'),
                    'term-position-updated'   => __("Term order updated.", 'publishpress'),
                    'term-visibility-changed' => __("Term visibility changed.", 'publishpress'),
                ),
                'configure_page_cb' => 'print_configure_view',
                'settings_help_tab' => array(
                    'id'      => 'pp-editorial-metadata-overview',
                    'title'   => __('Overview', 'publishpress'),
                    'content' => __('<p>Keep track of important details about your content with editorial metadata. This feature allows you to create as many date, text, number, etc. fields as you like, and then use them to store information like contact details, required word count, or the location of an interview.</p><p>Once you’ve set your fields up, editorial metadata integrates with both the calendar and the story budget. Make an editorial metadata item visible to have it appear to the rest of your team. Keep it hidden to restrict the information between the writer and their editor.</p>', 'publishpress'),
                ),
                'settings_help_sidebar' => __('<p><strong>For more information:</strong></p><p><a href="https://pressshack.com/features/editorial-metadata/">Editorial Metadata Documentation</a></p><p><a href="http://wordpress.org/tags/publishpress?forum_id=10">PublishPress Forum</a></p><p><a href="https://github.com/danielbachhuber/Edit-Flow">PublishPress on Github</a></p>', 'publishpress'),
                'add_menu'              => true,
            );
            PublishPress()->register_module($this->module_name, $args);
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {

            // Register the taxonomy we use for Editorial Metadata with WordPress core
            $this->register_taxonomy();

            // Anything that needs to happen in the admin
            add_action('admin_init', array($this, 'action_admin_init'));

            // Register our settings
            add_action('admin_init', array($this, 'register_settings'));

            // Actions relevant to the configuration view (adding, editing, or sorting existing Editorial Metadata)
            add_action('admin_init', array($this, 'handle_add_editorial_metadata'));
            add_action('admin_init', array($this, 'handle_edit_editorial_metadata'));
            add_action('admin_init', array($this, 'handle_change_editorial_metadata_visibility'));
            add_action('admin_init', array($this, 'handle_delete_editorial_metadata'));
            add_action('wp_ajax_inline_save_term', array($this, 'handle_ajax_inline_save_term'));
            add_action('wp_ajax_update_term_positions', array($this, 'handle_ajax_update_term_positions'));

            add_action('add_meta_boxes', array($this, 'handle_post_metaboxes'));
            add_action('save_post', array($this, 'save_meta_box'), 10, 2);

            // Add Editorial Metadata columns to the Manage Posts view
            $supported_post_types = $this->get_post_types_for_module($this->module);
            foreach ($supported_post_types as $post_type) {
                add_filter("manage_{$post_type}_posts_columns", array($this, 'filter_manage_posts_columns'));
                add_action("manage_{$post_type}_posts_custom_column", array($this, 'action_manage_posts_custom_column'), 10, 2);
            }

            // Add Editorial Metadata to the calendar if the calendar is activated
            if ($this->module_enabled('calendar')) {
                add_filter('pp_calendar_item_information_fields', array($this, 'filter_calendar_item_fields'), 10, 2);
            }

            // Add Editorial Metadata columns to the Story Budget if it exists
            if ($this->module_enabled('story_budget')) {
                add_filter('pp_story_budget_term_columns', array($this, 'filter_story_budget_term_columns'));
                // Register an action to handle this data later
                add_filter('pp_story_budget_term_column_value', array($this, 'filter_story_budget_term_column_values'), 10, 3);
            }

            // Load necessary scripts and stylesheets
            add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
        }

        /**
         * Load default editorial metadata the first time the module is loaded
         *
         * @since 0.7
         */
        public function install()
        {
            // Our default metadata fields
            $default_metadata = array(
                array(
                    'name'        => __('First Draft Date', 'publishpress'),
                    'slug'        => 'first-draft-date',
                    'type'        => 'date',
                    'description' => __('When the first draft needs to be ready.', 'publishpress'),
                ),
                array(
                    'name'        => __('Assignment', 'publishpress'),
                    'slug'        => 'assignment',
                    'type'        => 'paragraph',
                    'description' => __('What the post needs to cover.', 'publishpress'),
                ),
                array(
                    'name'        => __('Needs Photo', 'publishpress'),
                    'slug'        => 'needs-photo',
                    'type'        => 'checkbox',
                    'description' => __('Checked if this post needs a photo.', 'publishpress'),
                ),
                array(
                    'name'        => __('Word Count', 'publishpress'),
                    'slug'        => 'word-count',
                    'type'        => 'number',
                    'description' => __('Required post length in words.', 'publishpress'),
                ),
            );
            // Load the metadata fields if the slugs don't conflict
            foreach ($default_metadata as $args) {
                if (!term_exists($args['slug'], self::metadata_taxonomy)) {
                    $this->insert_editorial_metadata_term($args);
                }
            }
        }

        /**
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previous_version)
        {
            global $publishpress;

            // Upgrade path to v0.7
            if (version_compare($previous_version, '0.7', '<')) {
                // Technically we've run this code before so we don't want to auto-install new data
                $publishpress->update_module_option($this->module->name, 'loaded_once', true);
            }
            // Upgrade path to v0.7.4
            if (version_compare($previous_version, '0.7.4', '<')) {
                // Editorial metadata descriptions become base64_encoded, instead of maybe json_encoded.
                $this->upgrade_074_term_descriptions(self::metadata_taxonomy);
            }
        }

        /**
         * Anything that needs to happen on the 'admin_init' hook
         *
         * @since 0.7.4
         */
        public function action_admin_init()
        {

            // Parse the query when we're ordering by an editorial metadata term
            add_action('parse_query', array($this, 'action_parse_query'));
        }

        /**
         * Generate <select> HTML for all of the metadata types
         */
        public function get_select_html($description)
        {
            $current_metadata_type = $description->type;
            $metadata_types        = $this->get_supported_metadata_types();
            ?>
            <select id="<?php echo self::metadata_taxonomy;
            ?>'_type" name="<?php echo self::metadata_taxonomy;
            ?>'_type">
            <?php foreach ($metadata_types as $metadata_type => $metadata_type_name) : ?>
                <option value="<?php echo $metadata_type;
            ?>" <?php selected($metadata_type, $current_metadata_type);
            ?>><?php echo $metadata_type_name;
            ?></option>
            <?php endforeach;
            ?>
            </select>
        <?php

        }

        /**
         * Prepare an array of supported editorial metadata types
         *
         * @return array $supported_metadata_types All of the supported metadata
         */
        public function get_supported_metadata_types()
        {
            $supported_metadata_types = array(
                'checkbox'  => __('Checkbox', 'publishpress'),
                'date'      => __('Date', 'publishpress'),
                'location'  => __('Location', 'publishpress'),
                'number'    => __('Number', 'publishpress'),
                'paragraph' => __('Paragraph', 'publishpress'),
                'text'      => __('Text', 'publishpress'),
                'user'      => __('User', 'publishpress'),
            );
            return $supported_metadata_types;
        }

        /**
         * Enqueue relevant admin Javascript
         */
        public function add_admin_scripts()
        {
            global $current_screen, $pagenow;

            // Add the metabox date picker JS and CSS
            $current_post_type    = $this->get_current_post_type();
            $supported_post_types = $this->get_post_types_for_module($this->module);
            if (in_array($current_post_type, $supported_post_types)) {
                $this->enqueue_datepicker_resources();

                // Now add the rest of the metabox CSS
                wp_enqueue_style('publishpress-editorial_metadata-styles', $this->module_url . 'lib/editorial-metadata.css', false, PUBLISHPRESS_VERSION, 'all');
            }
            // A bit of custom CSS for the Manage Posts view if we have viewable metadata
            if ($current_screen->base == 'edit' && in_array($current_post_type, $supported_post_types)) {
                $terms          = $this->get_editorial_metadata_terms();
                $viewable_terms = array();
                foreach ($terms as $term) {
                    if ($term->viewable) {
                        $viewable_terms[] = $term;
                    }
                }
                if (!empty($viewable_terms)) {
                    $css_rules = array(
                        '.wp-list-table.fixed .column-author' => array(
                            'min-width: 7em;',
                            'width: auto;',
                        ),
                        '.wp-list-table.fixed .column-tags' => array(
                            'min-width: 7em;',
                            'width: auto;',
                        ),
                        '.wp-list-table.fixed .column-categories' => array(
                            'min-width: 7em;',
                            'width: auto;',
                        ),
                    );
                    foreach ($viewable_terms as $viewable_term) {
                        switch ($viewable_term->type) {
                            case 'checkbox':
                            case 'number':
                            case 'date':
                                $css_rules['.wp-list-table.fixed .column-' . $this->module->slug . '-' . $viewable_term->slug] = array(
                                    'min-width: 6em;',
                                );
                                break;
                            case 'location':
                            case 'text':
                            case 'user':
                                $css_rules['.wp-list-table.fixed .column-' . $this->module->slug . '-' . $viewable_term->slug] = array(
                                    'min-width: 7em;',
                                );
                                break;
                            case 'paragraph':
                                $css_rules['.wp-list-table.fixed .column-' . $this->module->slug . '-' . $viewable_term->slug] = array(
                                    'min-width: 8em;',
                                );
                                break;
                        }
                    }
                    // Allow users to filter out rules if there's something wonky
                    $css_rules = apply_filters('pp_editorial_metadata_manage_posts_css_rules', $css_rules);
                    echo "<style type=\"text/css\">\n";
                    foreach ((array)$css_rules as $css_property => $rules) {
                        echo $css_property . " {" . implode(' ', $rules) . "}\n";
                    }
                    echo '</style>';
                }
            }

            // Load Javascript specific to the editorial metadata configuration view
            if ($this->is_whitelisted_settings_view($this->module->name)) {
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_script('publishpress-editorial-metadata-configure', PUBLISHPRESS_URL . 'modules/editorial-metadata/lib/editorial-metadata-configure.js', array('jquery', 'jquery-ui-sortable', 'publishpress-settings-js'), PUBLISHPRESS_VERSION, true);
            }
        }

        /**
         * Register the post metadata taxonomy
         */
        public function register_taxonomy()
        {

            // We need to make sure taxonomy is registered for all of the post types that support it
            $supported_post_types = $this->get_post_types_for_module($this->module);

            register_taxonomy(self::metadata_taxonomy, $supported_post_types,
                array(
                    'public' => false,
                    'labels' => array(
                        'name' => _x('Editorial Metadata', 'taxonomy general name', 'publishpress'),
                        'singular_name' => _x('Editorial Metadata', 'taxonomy singular name', 'publishpress'),
                        'search_items' => __('Search Editorial Metadata', 'publishpress'),
                        'popular_items' => __('Popular Editorial Metadata', 'publishpress'),
                        'all_items' => __('All Editorial Metadata', 'publishpress'),
                        'edit_item' => __('Edit Editorial Metadata', 'publishpress'),
                        'update_item' => __('Update Editorial Metadata', 'publishpress'),
                        'add_new_item' => __('Add New Editorial Metadata', 'publishpress'),
                        'new_item_name' => __('New Editorial Metadata', 'publishpress'),
                   ),
                    'rewrite' => false,
               )
           );
        }

        /*****************************************************
         * Post meta box generation and processing
         ****************************************************/

        /**
         * Load the post metaboxes for all of the post types that are supported
         */
        public function handle_post_metaboxes()
        {
            $title = __('Editorial Metadata', 'publishpress');
            if (current_user_can('manage_options')) {
                // Make the metabox title include a link to edit the Editorial Metadata terms. Logic similar to how Core dashboard widgets work.
                $url = add_query_arg('page', 'pp-editorial-metadata-settings', get_admin_url(null, 'admin.php'));
                $title .= ' <span class="postbox-title-action"><a href="' . esc_url($url) . '" class="edit-box open-box">' . __('Configure') . '</a></span>';
            }

            $supported_post_types = $this->get_post_types_for_module($this->module);
            foreach ($supported_post_types as $post_type) {
                add_meta_box(self::metadata_taxonomy, $title, array($this, 'display_meta_box'), $post_type, 'side');
            }
        }

        /**
         * Displays HTML output for Editorial Metadata post meta box
         *
         * @param object $post Current post
         */
        public function display_meta_box($post)
        {
            echo "<div id='" . self::metadata_taxonomy . "_meta_box'>";
            // Add nonce for verification upon save
            echo "<input type='hidden' name='" . self::metadata_taxonomy . "_nonce' value='" . wp_create_nonce(__FILE__) . "' />";

            $terms = $this->get_editorial_metadata_terms();
            if (!count($terms)) {
                $message = __('No editorial metadata available.');
                if (current_user_can('manage_options')) {
                    $message .= sprintf(__(' <a href="%s">Add fields to get started</a>.'), $this->get_link());
                } else {
                    $message .= __(' Encourage your site administrator to configure your editorial workflow by adding editorial metadata.');
                }
                echo '<p>' . $message . '</p>';
            } else {
                foreach ($terms as $term) {
                    $postmeta_key     = $this->get_postmeta_key($term);
                    $current_metadata = esc_attr($this->get_postmeta_value($term, $post->ID));
                    $type             = $term->type;
                    $description      = $term->description;
                    if ($description) {
                        $description_span = "<span class='description'>$description</span>";
                    } else {
                        $description_span = '';
                    }
                    echo "<div class='" . self::metadata_taxonomy . " " . self::metadata_taxonomy . "_$type'>";
                    switch ($type) {
                        case "date":
                            // TODO: Move this to a function
                            if (!empty($current_metadata)) {
                                // Turn timestamp into a human-readable date
                                $current_metadata = $this->show_date_or_datetime(intval($current_metadata));
                            }
                            echo "<label for='$postmeta_key'>{$term->name}</label>";
                            if ($description_span) {
                                echo "<label for='$postmeta_key'>$description_span</label>";
                            }
                            echo "<input id='$postmeta_key' name='$postmeta_key' type='text' class='date-time-pick' value='$current_metadata' />";
                            break;
                        case "location":
                            echo "<label for='$postmeta_key'>{$term->name}</label>";
                            if ($description_span) {
                                echo "<label for='$postmeta_key'>$description_span</label>";
                            }
                            echo "<input id='$postmeta_key' name='$postmeta_key' type='text' value='$current_metadata' />";
                            if (!empty($current_metadata)) {
                                echo "<div><a href='http://maps.google.com/?q={$current_metadata}&t=m' target='_blank'>" . sprintf(__('View &#8220;%s&#8221; on Google Maps', 'publishpress'), $current_metadata) . "</a></div>";
                            }
                            break;
                        case "text":
                            echo "<label for='$postmeta_key'>{$term->name}$description_span</label>";
                            echo "<input id='$postmeta_key' name='$postmeta_key' type='text' value='$current_metadata' />";
                            break;
                        case "paragraph":
                            echo "<label for='$postmeta_key'>{$term->name}$description_span</label>";
                            echo "<textarea id='$postmeta_key' name='$postmeta_key'>$current_metadata</textarea>";
                            break;
                        case "checkbox":
                            echo "<label for='$postmeta_key'>{$term->name}$description_span</label>";
                            echo "<input id='$postmeta_key' name='$postmeta_key' type='checkbox' value='1' " . checked($current_metadata, 1, false) . " />";
                            break;
                        case "user":
                            echo "<label for='$postmeta_key'>{$term->name}$description_span</label>";
                            $user_dropdown_args = array(
                                    'show_option_all' => __('-- Select a user --', 'publishpress'),
                                    'name'     => $postmeta_key,
                                    'selected' => $current_metadata
                                );
                            $user_dropdown_args = apply_filters('pp_editorial_metadata_user_dropdown_args', $user_dropdown_args);
                            wp_dropdown_users($user_dropdown_args);
                            break;
                        case "number":
                            echo "<label for='$postmeta_key'>{$term->name}$description_span</label>";
                            echo "<input id='$postmeta_key' name='$postmeta_key' type='text' value='$current_metadata' />";
                            break;
                        default:
                            echo "<p>" . __('This editorial metadata type is not yet supported.', 'publishpress') . "</p>";
                    }
                    echo "</div>";
                    echo "<div class='clear'></div>";
                } // Done iterating through metadata terms
            }
            echo "</div>";
        }

        /**
         * Show date or datetime
         * @param  int $current_date
         * @return string
         * @since 0.8
         */
        private function show_date_or_datetime($current_date)
        {
            if (date('Hi', $current_date) == '0000') {
                return date('M d Y', $current_date);
            } else {
                return date('M d Y H:i', $current_date);
            }
        }

        /**
         * Save any values in the editorial metadata post meta box
         *
         * @param int $id Unique ID for the post being saved
         * @param object $post Post object
         */
        public function save_meta_box($id, $post)
        {

            // Authentication checks: make sure data came from our meta box and that the current user is allowed to edit the post
            // TODO: switch to using check_admin_referrer? See core (e.g. edit.php) for usage
            if (! isset($_POST[self::metadata_taxonomy . "_nonce"])
                || ! wp_verify_nonce($_POST[self::metadata_taxonomy . "_nonce"], __FILE__)) {
                return $id;
            }

            if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                || ! in_array($post->post_type, $this->get_post_types_for_module($this->module))
                || $post->post_type == 'post' && !current_user_can('edit_post', $id)
                || $post->post_type == 'page' && !current_user_can('edit_page', $id)) {
                return $id;
            }

            // Authentication passed, let's save the data
            $terms      = $this->get_editorial_metadata_terms();
            $term_slugs = array();

            foreach ($terms as $term) {
                // Setup the key for this editorial metadata term (same as what's in $_POST)
                $key = $this->get_postmeta_key($term);

                // Get the current editorial metadata
                // TODO: do we care about the current_metadata at all?
                //$current_metadata = get_post_meta($id, $key, true);

                $new_metadata = isset($_POST[$key]) ? $_POST[$key] : '';

                $type = $term->type;
                if (empty($new_metadata)) {
                    delete_post_meta($id, $key);
                } else {

                    // TODO: Move this to a function
                    if ($type == 'date') {
                        $new_metadata = strtotime($new_metadata);
                    }
                    if ($type == 'number') {
                        $new_metadata = (int)$new_metadata;
                    }

                    $new_metadata = strip_tags($new_metadata);
                    update_post_meta($id, $key, $new_metadata);

                    // Add the slugs of the terms with non-empty new metadata to an array
                    $term_slugs[] = $term->slug;
                }
                do_action('pp_editorial_metadata_field_updated', $key, $new_metadata, $id, $type);
            }

            // Relate the post to the terms used and taxonomy type (wp_term_relationships table).
            // This will allow us to update and display the count of metadata in posts in use per term.
            // TODO: Core only correlates posts with terms if the post_status is publish. Do we care what it is?
            if ($post->post_status === 'publish') {
                wp_set_object_terms($id, $term_slugs, self::metadata_taxonomy);
            }
        }

        /**
         * Generate a unique key based on the term
         *
         * @param object $term Term object
         * @return string $postmeta_key Unique key
         */
        public function get_postmeta_key($term)
        {
            $key          = self::metadata_postmeta_key;
            $type         = $term->type;
            $prefix       = "{$key}_{$type}";
            $postmeta_key = "{$prefix}_" . (is_object($term) ? $term->slug : $term);
            return $postmeta_key;
        }

        /**
         * Returns the value for the given metadata
         *
         * @param object|string|int term The term object, slug or ID for the metadata field term
         * @param int post_id The ID of the post
         */
        public function get_postmeta_value($term, $post_id)
        {
            if (! is_object($term)) {
                if (is_int($term)) {
                    $term = $this->get_editorial_metadata_term_by('id', $term);
                } else {
                    $term = $this->get_editorial_metadata_term_by('slug', $term);
                }
            }
            $postmeta_key = $this->get_postmeta_key($term);
            return get_metadata('post', $post_id, $postmeta_key, true);
        }

        /**
         * Get all of the editorial metadata terms as objects and sort by position
         * @todo Figure out what we should do with the filter...
         *
         * @param array $filter_args Filter to specific arguments
         * @return array $ordered_terms The terms as they should be ordered
         */
        public function get_editorial_metadata_terms($filter_args = array())
        {

            // Try to fetch from internal object cache
            $arg_hash = md5(serialize($filter_args));
            if (isset($this->editorial_metadata_terms_cache[$arg_hash])) {
                return $this->editorial_metadata_terms_cache[$arg_hash];
            }

            $args = array(
                    'orderby'    => apply_filters('pp_editorial_metadata_term_order', 'name'),
                    'hide_empty' => false
                );

            $terms         = get_terms(self::metadata_taxonomy, $args);
            $ordered_terms = array();
            $hold_to_end   = array();
            // Order the terms
            foreach ($terms as $key => $term) {

                // Unencode and set all of our psuedo term meta because we need the position and viewable if they exists
                // First do an array_merge() on the term object to make sure the keys exist, then array_merge()
                // any values that may already exist
                $unencoded_description = $this->get_unencoded_description($term->description);
                $defaults              = array(
                    'description' => '',
                    'viewable' => false,
                    'position' => false,
                );
                $term = array_merge($defaults, (array)$term);
                if (is_array($unencoded_description)) {
                    $term = array_merge($term, $unencoded_description);
                }
                $term = (object)$term;
                // We used to store the description field in a funny way
                if (isset($term->desc)) {
                    $term->description = $term->desc;
                    unset($term->desc);
                }
                // Only add the term to the ordered array if it has a set position and doesn't conflict with another key
                // Otherwise, hold it for later
                if ($term->position && !array_key_exists($term->position, $ordered_terms)) {
                    $ordered_terms[(int)$term->position] = $term;
                } else {
                    $hold_to_end[] = $term;
                }
            }
            // Sort the items numerically by key
            ksort($ordered_terms, SORT_NUMERIC);
            // Append all of the terms that didn't have an existing position
            foreach ($hold_to_end as $unpositioned_term) {
                $ordered_terms[] = $unpositioned_term;
            }

            // If filter arguments were passed, do our filtering
            $ordered_terms = wp_filter_object_list($ordered_terms, $filter_args);

            // Set the internal object cache
            $this->editorial_metadata_terms_cache[$arg_hash] = $ordered_terms;

            return $ordered_terms;
        }

        /**
         * Returns a term for single metadata field
         *
         * @param int|string $field The slug or ID for the metadata field term to return
         * @return object $term Term's object representation
         */
        public function get_editorial_metadata_term_by($field, $value)
        {
            if (! in_array($field, array('id', 'slug', 'name'))) {
                return false;
            }

            if ('id' == $field) {
                $field = 'term_id';
            }

            $terms = $this->get_editorial_metadata_terms();
            $term  = wp_filter_object_list($terms, array($field => $value));

            if (! empty($term)) {
                return array_shift($term);
            } else {
                return false;
            }
        }

        /**
         * Register editorial metadata fields as columns in the manage posts view
         * Only adds columns for the currently active post types - logic controlled in $this->init()
         *
         * @since 0.7
         * @uses apply_filters('manage_posts_columns') in wp-admin/includes/class-wp-posts-list-table.php
         *
         * @param array $posts_columns Existing post columns prepared by WP_List_Table
         * @param array $posts_columns Previous post columns with the new values
         */
        public function filter_manage_posts_columns($posts_columns)
        {
            $screen = get_current_screen();
            if ($screen) {
                add_filter("manage_{$screen->id}_sortable_columns", array($this, 'filter_manage_posts_sortable_columns'));
                $terms = $this->get_editorial_metadata_terms(array('viewable' => true));
                foreach ($terms as $term) {
                    // Prefixing slug with module slug because it isn't stored prefixed and we want to avoid collisions
                    $key                 = $this->module->slug . '-' . $term->slug;
                    $posts_columns[$key] = $term->name;
                }
            }
            return $posts_columns;
        }

        /**
         * Register any viewable date editorial metadata as a sortable column
         *
         * @since 0.7.4
         *
         * @param array $sortable_columns Any existing sortable columns (e.g. Title)
         * @return array $sortable_columms Sortable columns with editorial metadata date fields added
         */
        public function filter_manage_posts_sortable_columns($sortable_columns)
        {
            $terms = $this->get_editorial_metadata_terms(array('viewable' => true, 'type' => 'date'));
            foreach ($terms as $term) {
                // Prefixing slug with module slug because it isn't stored prefixed and we want to avoid collisions
                $key                    = $this->module->slug . '-' . $term->slug;
                $sortable_columns[$key] = $key;
            }
            return $sortable_columns;
        }

        /**
         * If we're ordering by a sortable column, let's modify the query
         *
         * @since 0.7.4
         */
        public function action_parse_query($query)
        {
            if (is_admin() && false !== stripos(get_query_var('orderby'), $this->module->slug)) {
                $term_slug = sanitize_key(str_replace($this->module->slug . '-', '', get_query_var('orderby')));
                $term      = $this->get_editorial_metadata_term_by('slug', $term_slug);
                $meta_key  = $this->get_postmeta_key($term);
                set_query_var('meta_key', $meta_key);
                set_query_var('orderby', 'meta_value_num');
            }
        }

        /**
         * Handle the output of an editorial metadata custom column
         * Logic for the post types this is called on is controlled in $this->init()
         *
         * @since 0.7
         * @uses do_action('manage_posts_custom_column') in wp-admin/includes/class-wp-posts-list-table.php
         *
         * @param string $column_name Unique string for the column
         * @param int $post_id ID for the post of the row
         */
        public function action_manage_posts_custom_column($column_name, $post_id)
        {
            $terms = $this->get_editorial_metadata_terms();
            // We're looking for the proper term to display its saved value
            foreach ($terms as $term) {
                $key = $this->module->slug . '-' . $term->slug;
                if ($column_name != $key) {
                    continue;
                }

                $current_metadata = $this->get_postmeta_value($term, $post_id);
                echo $this->generate_editorial_metadata_term_output($term, $current_metadata);
            }
        }

        /**
         * If the PublishPress Calendar is enabled, add viewable Editorial Metadata terms
         *
         * @since 0.7
         * @uses apply_filters('pp_calendar_item_information_fields')
         *
         * @param array $calendar_fields Additional data fields to include on the calendar
         * @param int $post_id Unique ID for the post data we're building
         * @return array $calendar_fields Calendar fields with our viewable Editorial Metadata added
         */
        public function filter_calendar_item_fields($calendar_fields, $post_id)
        {


            // Make sure we respect which post type we're on
            if (!in_array(get_post_type($post_id), $this->get_post_types_for_module($this->module))) {
                return $calendar_fields;
            }

            $terms = $this->get_editorial_metadata_terms(array('viewable' => true));

            foreach ($terms as $term) {
                $key = $this->module->slug . '-' . $term->slug;

                // Default values
                $current_metadata = $this->get_postmeta_value($term, $post_id);
                $term_data        = array(
                    'label' => $term->name,
                    'value' => $this->generate_editorial_metadata_term_output($term, $current_metadata),
                );
                $term_data['editable'] = true;
                $term_data['type']     = $term->type;
                $calendar_fields[$key] = $term_data;
            }
            return $calendar_fields;
        }

        /**
         * If the PublishPress Story Budget is enabled, register our viewable terms as columns
         *
         * @since 0.7
         * @uses apply_filters('pp_story_budget_term_columns')
         *
         * @param array $term_columns The existing columns on the story budget
         * @return array $term_columns Term columns with viewable Editorial Metadata terms
         */
        public function filter_story_budget_term_columns($term_columns)
        {
            $terms = $this->get_editorial_metadata_terms(array('viewable' => true));
            foreach ($terms as $term) {
                // Prefixing slug with module slug because it isn't stored prefixed and we want to avoid collisions
                $key = $this->module->slug . '-' . $term->slug;
                // Switch to underscores
                $key                = str_replace('-', '_', $key);
                $term_columns[$key] = $term->name;
            }
            return $term_columns;
        }

        /**
         * If the PublishPress Story Budget is enabled,
         *
         * @since 0.7
         * @uses apply_filters('pp_story_budget_term_column_value')
         *
         * @param object $post The post we're displaying
         * @param string $column_name Name of the column, as registered with PP_Story_Budget::register_term_columns
         * @param object $parent_term The parent term for the term column
         */
        public function filter_story_budget_term_column_values($column_name, $post, $parent_term)
        {
            $local_column_name = str_replace('_', '-', $column_name);
            // Don't accidentally handle values not our own
            if (false === strpos($local_column_name, $this->module->slug)) {
                return $column_name;
            }

            $term_slug = str_replace($this->module->slug . '-', '', $local_column_name);
            $term      = $this->get_editorial_metadata_term_by('slug', $term_slug);

            // Don't allow non-viewable term data to be displayed
            if (!$term->viewable) {
                return $column_name;
            }

            $current_metadata = $this->get_postmeta_value($term, $post->ID);
            $output           = $this->generate_editorial_metadata_term_output($term, $current_metadata);

            return $output;
        }

        /**
         * Generate the presentational output for an editorial metadata term
         *
         * @since 0.8
         *
         * @param object      $term    The editorial metadata term
         * @return string     $html    How the term should be rendered
         */
        private function generate_editorial_metadata_term_output($term, $pm_value)
        {
            $output = '';
            switch ($term->type) {
                case "date":
                    if (empty($pm_value)) {
                        break;
                    }

                    // All day vs. day and time
                    $date = date(get_option('date_format'), $pm_value);
                    $time = date(get_option('time_format'), $pm_value);
                    if (date('Hi', $pm_value) == '0000') {
                        $pm_value = $date;
                    } else {
                        $pm_value = sprintf(__('%1$s at %2$s', 'publishpress'), $date, $time);
                    }
                    $output = esc_html($pm_value);
                    break;
                case "location":
                case "text":
                case "number":
                case "paragraph":
                    if ($pm_value) {
                        $output = esc_html($pm_value);
                    }
                    break;
                case "checkbox":
                    if ($pm_value) {
                        $output = __('Yes', 'publishpress');
                    } else {
                        $output = __('No', 'publishpress');
                    }
                    break;
                case "user":
                    if (empty($pm_value)) {
                        break;
                    }
                    $userdata = get_user_by('id', $pm_value);
                    if (is_object($userdata)) {
                        $output = esc_html($userdata->display_name);
                    }
                    break;
                default:
                    break;
            }
            return $output;
        }

        /**
         * Update an existing editorial metadata term if the term_id exists
         *
         * @since 0.7
         *
         * @param int $term_id The term's unique ID
         * @param array $args Any values that need to be updated for the term
         * @return object|WP_Error $updated_term The updated term or a WP_Error object if something disastrous happened
         */
        public function update_editorial_metadata_term($term_id, $args)
        {
            $new_args = array();
            $old_term = $this->get_editorial_metadata_term_by('id', $term_id);
            if ($old_term) {
                $old_args = array(
                    'position' => $old_term->position,
                    'name' => $old_term->name,
                    'slug' => $old_term->slug,
                    'description' => $old_term->description,
                    'type' => $old_term->type,
                    'viewable' => $old_term->viewable,
                );
            }
            $new_args = array_merge($old_args, $args);

            // We're encoding metadata that isn't supported by default in the term's description field
            $args_to_encode = array(
                'description' => $new_args['description'],
                'position' => $new_args['position'],
                'type' => $new_args['type'],
                'viewable' => $new_args['viewable'],
            );
            $encoded_description     = $this->get_encoded_description($args_to_encode);
            $new_args['description'] = $encoded_description;

            $updated_term = wp_update_term($term_id, self::metadata_taxonomy, $new_args);

            // Reset the internal object cache
            $this->editorial_metadata_terms_cache = array();

            $updated_term = $this->get_editorial_metadata_term_by('id', $term_id);
            return $updated_term;
        }

        /**
         * Insert a new editorial metadata term
         * @todo Handle conflicts with existing terms at that position (if relevant)
         *
         * @since 0.7
         */
        public function insert_editorial_metadata_term($args)
        {


            // Term is always added to the end of the list
            $default_position = count($this->get_editorial_metadata_terms()) + 2;
            $defaults         = array(
                'position'    => $default_position,
                'name'        => '',
                'slug'        => '',
                'description' => '',
                'type'        => '',
                'viewable'    => false,
            );
            $args      = array_merge($defaults, $args);
            $term_name = $args['name'];
            unset($args['name']);

            // We're encoding metadata that isn't supported by default in the term's description field
            $args_to_encode = array(
                'description' => $args['description'],
                'position' => $args['position'],
                'type' => $args['type'],
                'viewable' => $args['viewable'],
            );
            $encoded_description = $this->get_encoded_description($args_to_encode);
            $args['description'] = $encoded_description;

            $inserted_term = wp_insert_term($term_name, self::metadata_taxonomy, $args);

            // Reset the internal object cache
            $this->editorial_metadata_terms_cache = array();

            return $inserted_term;
        }

        /**
         * Settings and other management code
         */

        /**
         * Delete an existing editorial metadata term
         *
         * @since 0.7
         *
         * @param int $term_id The term we want deleted
         * @return bool $result Whether or not the term was deleted
         */
        public function delete_editorial_metadata_term($term_id)
        {
            $result = wp_delete_term($term_id, self::metadata_taxonomy);

            // Reset the internal object cache
            $this->editorial_metadata_terms_cache = array();

            return $result;
        }

        /**
         * Generate a link to one of the editorial metadata actions
         *
         * @since 0.7
         *
         * @param array $args (optional) Action and any query args to add to the URL
         * @return string $link Direct link to complete the action
         */
        public function get_link($args = array())
        {
            if (!isset($args['action'])) {
                $args['action'] = '';
            }
            if (!isset($args['page'])) {
                $args['page'] = $this->module->settings_slug;
            }
            // Add other things we may need depending on the action
            switch ($args['action']) {
                case 'make-viewable':
                case 'make-hidden':
                case 'delete-term':
                    $args['nonce'] = wp_create_nonce($args['action']);
                    break;
                default:
                    break;
            }
            return add_query_arg($args, get_admin_url(null, 'admin.php'));
        }

        /**
         * Handles a request to add a new piece of editorial metadata
         */
        public function handle_add_editorial_metadata()
        {
            if (!isset($_POST['submit'], $_POST['form-action'], $_GET['page'])
                || $_GET['page'] != $this->module->settings_slug || $_POST['form-action'] != 'add-term') {
                return;
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'editorial-metadata-add-nonce')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            // Sanitize all of the user-entered values
            $term_name        = sanitize_text_field(trim($_POST['metadata_name']));
            $term_slug        = (!empty($_POST['metadata_slug'])) ? sanitize_title($_POST['metadata_slug']) : sanitize_title($term_name);
            $term_description = stripslashes(wp_filter_post_kses(trim($_POST['metadata_description'])));
            $term_type        = sanitize_key($_POST['metadata_type']);

            $_REQUEST['form-errors'] = array();

            /**
             * Form validation for adding new editorial metadata term
             *
             * Details
             * - "name", "slug", and "type" are required fields
             * - "description" can accept a limited amount of HTML, and is optional
             */
            // Field is required
            if (empty($term_name)) {
                $_REQUEST['form-errors']['name'] = __('Please enter a name for the editorial metadata.', 'publishpress');
            }
            // Field is required
            if (empty($term_slug)) {
                $_REQUEST['form-errors']['slug'] = __('Please enter a slug for the editorial metadata.', 'publishpress');
            }
            if (term_exists($term_slug)) {
                $_REQUEST['form-errors']['name'] = __('Name conflicts with existing term. Please choose another.', 'publishpress');
            }
            // Check to ensure a term with the same name doesn't exist
            if ($this->get_editorial_metadata_term_by('name', $term_name, self::metadata_taxonomy)) {
                $_REQUEST['form-errors']['name'] = __('Name already in use. Please choose another.', 'publishpress');
            }
            // Check to ensure a term with the same slug doesn't exist
            if ($this->get_editorial_metadata_term_by('slug', $term_slug)) {
                $_REQUEST['form-errors']['slug'] = __('Slug already in use. Please choose another.', 'publishpress');
            }
            // Check to make sure the status doesn't already exist as another term because otherwise we'd get a weird slug
            // Check that the term name doesn't exceed 200 chars
            if (strlen($term_name) > 200) {
                $_REQUEST['form-errors']['name'] = __('Name cannot exceed 200 characters. Please try a shorter name.', 'publishpress');
            }
            // Metadata type needs to pass our whitelist check
            $metadata_types = $this->get_supported_metadata_types();
            if (empty($_POST['metadata_type']) || !isset($metadata_types[$_POST['metadata_type']])) {
                $_REQUEST['form-errors']['type'] = __('Please select a valid metadata type.', 'publishpress');
            }
            // Metadata viewable needs to be a valid Yes or No
            $term_viewable = false;
            if ($_POST['metadata_viewable'] == 'yes') {
                $term_viewable = true;
            }

            // Kick out if there are any errors
            if (count($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';
                return;
            }

            // Try to add the status
            $args = array(
                'name'        => $term_name,
                'description' => $term_description,
                'slug'        => $term_slug,
                'type'        => $term_type,
                'viewable'    => $term_viewable,
            );
            $return = $this->insert_editorial_metadata_term($args);
            if (is_wp_error($return)) {
                wp_die(__('Error adding term.', 'publishpress'));
            }

            $redirect_url = add_query_arg(array('page' => $this->module->settings_slug, 'message' => 'term-added'), get_admin_url(null, 'admin.php'));
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handles a request to edit an editorial metadata
         */
        public function handle_edit_editorial_metadata()
        {
            if (!isset($_POST['submit'], $_GET['page'], $_GET['action'], $_GET['term-id'])
                || $_GET['page'] != $this->module->settings_slug || $_GET['action'] != 'edit-term') {
                return;
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'editorial-metadata-edit-nonce')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            if (!$existing_term = $this->get_editorial_metadata_term_by('id', (int)$_GET['term-id'])) {
                wp_die($this->module->messages['term-missing']);
            }

            $new_name        = sanitize_text_field(trim($_POST['name']));
            $new_description = stripslashes(wp_filter_post_kses(strip_tags(trim($_POST['description']))));

            /**
             * Form validation for editing editorial metadata term
             *
             * Details
             * - "name", "slug", and "type" are required fields
             * - "description" can accept a limited amount of HTML, and is optional
             */
            $_REQUEST['form-errors'] = array();
            // Check if name field was filled in
            if (empty($new_name)) {
                $_REQUEST['form-errors']['name'] = __('Please enter a name for the editorial metadata', 'publishpress');
            }

            // Check that the name isn't numeric
            if (is_numeric($new_name)) {
                $_REQUEST['form-errors']['name'] = __('Please enter a valid, non-numeric name for the editorial metadata.', 'publishpress');
            }

            $term_exists = term_exists(sanitize_title($new_name));
            if ($term_exists && $term_exists != $existing_term->term_id) {
                $_REQUEST['form-errors']['name'] = __('Metadata name conflicts with existing term. Please choose another.', 'publishpress');
            }

            // Check to ensure a term with the same name doesn't exist,
            $search_term = $this->get_editorial_metadata_term_by('name', $new_name);
            if (is_object($search_term) && $search_term->term_id != $existing_term->term_id) {
                $_REQUEST['form-errors']['name'] = __('Name already in use. Please choose another.', 'publishpress');
            }
            // or that the term name doesn't map to an existing term's slug
            $search_term = $this->get_editorial_metadata_term_by('slug', sanitize_title($new_name));
            if (is_object($search_term) && $search_term->term_id != $existing_term->term_id) {
                $_REQUEST['form-errors']['name'] = __('Name conflicts with slug for another term. Please choose something else.', 'publishpress');
            }

            // Check that the term name doesn't exceed 200 chars
            if (strlen($new_name) > 200) {
                $_REQUEST['form-errors']['name'] = __('Name cannot exceed 200 characters. Please try a shorter name.', 'publishpress');
            }
            // Make sure the viewable state is valid
            $new_viewable = false;
            if ($_POST['viewable'] == 'yes') {
                $new_viewable = true;
            }

            // Kick out if there are any errors
            if (count($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';
                return;
            }

            // Try to add the metadata term
            $args = array(
                'name' => $new_name,
                'description' => $new_description,
                'viewable' => $new_viewable,
            );
            $return = $this->update_editorial_metadata_term($existing_term->term_id, $args);
            if (is_wp_error($return)) {
                wp_die(__('Error updating term.', 'publishpress'));
            }

            $redirect_url = add_query_arg(array('page' => $this->module->settings_slug, 'message' => 'term-updated'), get_admin_url(null, 'admin.php'));
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handle a $_GET request to change the visibility of an Editorial Metadata term
         *
         * @since 0.7
         */
        public function handle_change_editorial_metadata_visibility()
        {

            // Check that the current GET request is our GET request
            if (!isset($_GET['page'], $_GET['action'], $_GET['term-id'], $_GET['nonce'])
                || $_GET['page'] != $this->module->settings_slug || !in_array($_GET['action'], array('make-viewable', 'make-hidden'))) {
                return;
            }

            // Check for proper nonce
            if (!wp_verify_nonce($_GET['nonce'], 'make-viewable') && !wp_verify_nonce($_GET['nonce'], 'make-hidden')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            // Only allow users with the proper caps
            if (!current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            $term_id = (int)$_GET['term-id'];
            $args    = array();
            if ($_GET['action'] == 'make-viewable') {
                $args['viewable'] = true;
            } elseif ($_GET['action'] == 'make-hidden') {
                $args['viewable'] = false;
            }

            $return = $this->update_editorial_metadata_term($term_id, $args);
            if (is_wp_error($return)) {
                wp_die(__('Error updating term.', 'publishpress'));
            }

            $redirect_url = $this->get_link(array('message' => 'term-visibility-changed'));
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handle the request to update a given Editorial Metadata term via inline edit
         *
         * @since 0.7
         */
        public function handle_ajax_inline_save_term()
        {
            if (!wp_verify_nonce($_POST['inline_edit'], 'editorial-metadata-inline-edit-nonce')) {
                die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can('manage_options')) {
                die($this->module->messages['invalid-permissions']);
            }

            $term_id = (int) $_POST['term_id'];
            if (!$existing_term = $this->get_editorial_metadata_term_by('id', $term_id)) {
                die($this->module->messages['term-missing']);
            }

            $metadata_name        = sanitize_text_field(trim($_POST['name']));
            $metadata_description = stripslashes(wp_filter_post_kses(trim($_POST['description'])));

            /**
             * Form validation for editing editorial metadata term
             */
            // Check if name field was filled in
            if (empty($metadata_name)) {
                $change_error = new WP_Error('invalid', __('Please enter a name for the editorial metadata', 'publishpress'));
                die($change_error->get_error_message());
            }

            // Check that the name isn't numeric
            if (is_numeric($metadata_name)) {
                $change_error = new WP_Error('invalid', __('Please enter a valid, non-numeric name for the editorial metadata.', 'publishpress'));
                die($change_error->get_error_message());
            }

            // Check that the term name doesn't exceed 200 chars
            if (strlen($metadata_name) > 200) {
                $change_error = new WP_Error('invalid', __('Name cannot exceed 200 characters. Please try a shorter name.'));
                die($change_error->get_error_message());
            }

            // Check to make sure the status doesn't already exist as another term because otherwise we'd get a fatal error
            $term_exists = term_exists(sanitize_title($metadata_name));
            if ($term_exists && $term_exists != $term_id) {
                $change_error = new WP_Error('invalid', __('Metadata name conflicts with existing term. Please choose another.', 'publishpress'));
                die($change_error->get_error_message());
            }

            // Check to ensure a term with the same name doesn't exist,
            $search_term = $this->get_editorial_metadata_term_by('name', $metadata_name);
            if (is_object($search_term) && $search_term->term_id != $existing_term->term_id) {
                $change_error = new WP_Error('invalid', __('Name already in use. Please choose another.', 'publishpress'));
                die($change_error->get_error_message());
            }

            // or that the term name doesn't map to an existing term's slug
            $search_term = $this->get_editorial_metadata_term_by('slug', sanitize_title($metadata_name));
            if (is_object($search_term) && $search_term->term_id != $existing_term->term_id) {
                $change_error = new WP_Error('invalid', __('Name conflicts with slug for another term. Please choose again.', 'publishpress'));
                die($change_error->get_error_message());
            }

            // Prepare the term name and description for saving
            $args = array(
                'name' => $metadata_name,
                'description' => $metadata_description,
            );
            $return = $this->update_editorial_metadata_term($existing_term->term_id, $args);
            if (!is_wp_error($return)) {
                set_current_screen('edit-editorial-metadata');
                $wp_list_table = new PP_Editorial_Metadata_List_Table();
                $wp_list_table->prepare_items();
                echo $wp_list_table->single_row($return);
                die();
            } else {
                $change_error = new WP_Error('invalid', sprintf(__('Could not update the term: <strong>%s</strong>', 'publishpress'), $status_name));
                die($change_error->get_error_message());
            }
        }

        /**
         * Handle the ajax request to update all of the term positions
         *
         * @since 0.7
         */
        public function handle_ajax_update_term_positions()
        {
            if (!wp_verify_nonce($_POST['editorial_metadata_sortable_nonce'], 'editorial-metadata-sortable')) {
                $this->print_ajax_response('error', $this->module->messages['nonce-failed']);
            }

            if (!current_user_can('manage_options')) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            if (!isset($_POST['term_positions']) || !is_array($_POST['term_positions'])) {
                $this->print_ajax_response('error', __('Terms not set.', 'publishpress'));
            }

            foreach ($_POST['term_positions'] as $position => $term_id) {

                // Have to add 1 to the position because the index started with zero
                $args = array(
                    'position' => (int)$position + 1,
                );
                $return = $this->update_editorial_metadata_term((int)$term_id, $args);
                // @todo check that this was a valid return
            }
            $this->print_ajax_response('success', $this->module->messages['term-position-updated']);
        }

        /**
         * Handles a request to delete an editorial metadata term
         */
        public function handle_delete_editorial_metadata()
        {
            if (!isset($_GET['page'], $_GET['action'], $_GET['term-id'])
                || $_GET['page'] != $this->module->settings_slug || $_GET['action'] != 'delete-term') {
                return;
            }

            if (!wp_verify_nonce($_GET['nonce'], 'delete-term')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            if (!$existing_term = $this->get_editorial_metadata_term_by('id', (int)$_GET['term-id'])) {
                wp_die($this->module->messages['term-missing']);
            }

            $result = $this->delete_editorial_metadata_term($existing_term->term_id);
            if (!$result || is_wp_error($result)) {
                wp_die(__('Error deleting term.', 'publishpress'));
            }

            $redirect_url = add_query_arg(array('page' => $this->module->settings_slug, 'message' => 'term-deleted'), get_admin_url(null, 'admin.php'));
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         *
         * @since 0.7
         * @uses add_settings_section(), add_settings_field()
         */
        public function register_settings()
        {
            add_settings_section($this->module->options_group_name . '_general', false, '__return_false', $this->module->options_group_name);
            add_settings_field('post_types', __('Add to these post types:', 'publishpress'), array($this, 'settings_post_types_option'), $this->module->options_group_name, $this->module->options_group_name . '_general');
        }

        /**
         * Choose the post types for editorial metadata
         *
         * @since 0.7
         */
        public function settings_post_types_option()
        {
            global $publishpress;
            $publishpress->settings->helper_option_custom_post_type($this->module);
        }

        /**
         * Validate data entered by the user
         *
         * @since 0.7
         *
         * @param array $new_options New values that have been entered by the user
         * @return array $new_options Form values after they've been sanitized
         */
        public function settings_validate($new_options)
        {

            // Whitelist validation for the post type options
            if (!isset($new_options['post_types'])) {
                $new_options['post_types'] = array();
            }
            $new_options['post_types'] = $this->clean_post_type_options($new_options['post_types'], $this->module->post_type_support);

            return $new_options;
        }

        /**
         * Prepare and display the configuration view for editorial metadata.
         * There are four primary components:
         * - Form to add a new Editorial Metadata term
         * - Form generated by the settings API for managing Editorial Metadata options
         * - Table of existing Editorial Metadata terms with ability to take actions on each
         * - Full page width view for editing a single Editorial Metadata term
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            global $publishpress;
            $wp_list_table = new PP_Editorial_Metadata_List_Table();
            $wp_list_table->prepare_items();
            ?>
            <script type="text/javascript">
                var pp_confirm_delete_term_string = "<?php echo esc_js(__('Are you sure you want to delete this term? Any metadata for this term will remain but will not be visible unless this term is re-added.', 'publishpress'));
            ?>";
            </script>
            <?php if (!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] != 'edit-term')): ?>
            <div id="col-right">
            <div class="col-wrap">
            <form id="posts-filter" action="" method="post">
                <?php $wp_list_table->display();
            ?>
                <?php wp_nonce_field('editorial-metadata-sortable', 'editorial-metadata-sortable');
            ?>
            </form>
            </div>
            </div><!-- /col-right -->
            <?php $wp_list_table->inline_edit();
            ?>
            <?php endif;
            ?>

            <?php if (isset($_GET['action'], $_GET['term-id']) && $_GET['action'] == 'edit-term'): ?>
            <?php /** Full page width view for editing a given editorial metadata term **/ ?>
            <?php
                // Check whether the term exists
                $term_id = (int)$_GET['term-id'];
            $term        = $this->get_editorial_metadata_term_by('id', $term_id);
            if (!$term) {
                echo '<div class="error"><p>' . $this->module->messages['term-missing'] . '</p></div>';
                return;
            }
            $metadata_types = $this->get_supported_metadata_types();
            $type           = $term->type;
            $edit_term_link = $this->get_link(array('action' => 'edit-term', 'term-id' => $term->term_id));

            $name        = (isset($_POST['name'])) ? stripslashes($_POST['name']) : $term->name;
            $description = (isset($_POST['description'])) ? stripslashes($_POST['description']) : $term->description;
            if ($term->viewable) {
                $viewable = 'yes';
            } else {
                $viewable = 'no';
            }
            $viewable = (isset($_POST['viewable'])) ? stripslashes($_POST['viewable']) : $viewable;
            ?>

                <form method="post" action="<?php echo esc_url($edit_term_link);
                ?>" >
                <input type="hidden" name="action" value="editedtag" />
                <input type="hidden" name="tag_id" value="<?php echo esc_attr($term->term_id);
                ?>" />
                <input type="hidden" name="taxonomy" value="<?php echo esc_attr(self::metadata_taxonomy) ?>" />
                <?php
                    wp_original_referer_field();
                wp_nonce_field('editorial-metadata-edit-nonce');
                ?>
                <table class="form-table">
                    <tr class="form-field form-required">
                        <th scope="row" valign="top"><label for="name"><?php _e('Name');
                ?></label></th>
                        <td><input name="name" id="name" type="text" value="<?php echo esc_attr($name);
                ?>" size="40" aria-required="true" />
                        <?php $publishpress->settings->helper_print_error_or_description('name', __('The name is for labeling the metadata field.', 'publishpress'));
                ?>
                    </tr>
                    <tr class="form-field">
                        <th scope="row" valign="top"><?php _e('Slug', 'publishpress');
                ?></th>
                        <td>
                            <input type="text" disabled="disabled" value="<?php echo esc_attr($term->slug);
                ?>" />
                            <p class="description"><?php _e('The slug cannot be changed once the term has been created.', 'publishpress');
                ?></p>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row" valign="top"><label for="description"><?php _e('Description', 'publishpress');
                ?></label></th>
                        <td>
                            <textarea name="description" id="description" rows="5" cols="50" style="width: 97%;"><?php echo esc_html($description);
                ?></textarea>
                        <?php $publishpress->settings->helper_print_error_or_description('description', __('The description can be used to communicate with your team about what the metadata is for.', 'publishpress'));
                ?>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row" valign="top"><?php _e('Type', 'publishpress');
                ?></th>
                        <td>
                            <input type="text" disabled="disabled" value="<?php echo esc_attr($metadata_types[$type]);
                ?>" />
                            <p class="description"><?php _e('The metadata type cannot be changed once created.', 'publishpress');
                ?></p>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row" valign="top"><?php _e('Viewable', 'publishpress');
                ?></th>
                        <td>
                            <?php
                                $metadata_viewable_options = array(
                                    'no' => __('No', 'publishpress'),
                                    'yes' => __('Yes', 'publishpress'),
                                );
                ?>
                            <select id="viewable" name="viewable">
                            <?php foreach ($metadata_viewable_options as $metadata_viewable_key => $metadata_viewable_value) : ?>
                                <option value="<?php echo esc_attr($metadata_viewable_key);
                ?>" <?php selected($viewable, $metadata_viewable_key);
                ?>><?php echo esc_attr($metadata_viewable_value);
                ?></option>
                            <?php endforeach;
                ?>
                            </select>
                            <?php $publishpress->settings->helper_print_error_or_description('viewable', __('When viewable, metadata can be seen on views other than the edit post view (e.g. calendar, manage posts, story budget, etc.)', 'publishpress'));
                ?>
                        </td>
                    </tr>
                <input type="hidden" name="<?php echo self::metadata_taxonomy ?>'_type" value="<?php echo $type;
                ?>" />
                </table>
                <p class="submit">
                <?php submit_button(__('Update Metadata Term', 'publishpress'), 'primary', 'submit', false);
                ?>
                <a class="cancel-settings-link" href="<?php echo esc_url(add_query_arg('page', $this->module->settings_slug, get_admin_url(null, 'admin.php')));
                ?>"><?php _e('Cancel', 'publishpress');
                ?></a>
                </p>
                </form>

                <?php else: ?>
                <?php /** If not in full-screen edit term mode, we can create new terms or change options **/ ?>
                <div id="col-left">
                    <div class="col-wrap">
                    <div class="form-wrap">
                    <h3 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url(add_query_arg(array('page' => $this->module->settings_slug), get_admin_url(null, 'admin.php')));
                ?>" class="nav-tab<?php if (!isset($_GET['action']) || $_GET['action'] != 'change-options') {
            echo ' nav-tab-active';
        }
                ?>"><?php _e('Add New', 'publishpress');
                ?></a>
                        <a href="<?php echo esc_url(add_query_arg(array('page' => $this->module->settings_slug, 'action' => 'change-options'), get_admin_url(null, 'admin.php')));
                ?>" class="nav-tab<?php if (isset($_GET['action']) && $_GET['action'] == 'change-options') {
            echo ' nav-tab-active';
        }
                ?>"><?php _e('Options', 'publishpress');
                ?></a>
                    </h3>

                <?php if (isset($_GET['action']) && $_GET['action'] == 'change-options'): ?>
                <?php /** Basic form built on WP Settings API for outputting Editorial Metadata options **/ ?>
                <form class="basic-settings" action="<?php echo esc_url(add_query_arg(array('page' => $this->module->settings_slug, 'action' => 'change-options'), get_admin_url(null, 'admin.php')));
                ?>" method="post">
                    <?php settings_fields($this->module->options_group_name);
                ?>
                    <?php do_settings_sections($this->module->options_group_name);
                ?>
                    <?php echo '<input id="publishpress_module_name" name="publishpress_module_name" type="hidden" value="' . esc_attr($this->module->name) . '" />';
                ?>
                    <?php submit_button();
                ?>
                </form>
                <?php else: ?>
                <?php /** Custom form for adding a new Editorial Metadata term **/ ?>
                    <form class="add:the-list:" action="<?php echo esc_url(add_query_arg(array('page' => $this->module->settings_slug), get_admin_url(null, 'admin.php')));
                ?>" method="post" id="addmetadata" name="addmetadata">
                    <div class="form-field form-required">
                        <label for="metadata_name"><?php _e('Name', 'publishpress');
                ?></label>
                        <input type="text" aria-required="true" size="20" maxlength="200" id="metadata_name" name="metadata_name" value="<?php if (!empty($_POST['metadata_name'])) {
            echo esc_attr(stripslashes($_POST['metadata_name']));
        }
                ?>" />
                        <?php $publishpress->settings->helper_print_error_or_description('name', __('The name is for labeling the metadata field.', 'publishpress'));
                ?>
                    </div>
                    <div class="form-field form-required">
                        <label for="metadata_slug"><?php _e('Slug', 'publishpress');
                ?></label>
                        <input type="text" aria-required="true" size="20" maxlength="200" id="metadata_slug" name="metadata_slug" value="<?php if (!empty($_POST['metadata_slug'])) {
            echo esc_attr($_POST['metadata_slug']);
        }
                ?>" />
                        <?php $publishpress->settings->helper_print_error_or_description('slug', __('The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'publishpress'));
                ?>
                    </div>
                    <div class="form-field">
                        <label for="metadata_description"><?php _e('Description', 'publishpress');
                ?></label>
                        <textarea cols="40" rows="5" id="metadata_description" name="metadata_description"><?php if (!empty($_POST['metadata_description'])) {
            echo esc_html(stripslashes($_POST['metadata_description']));
        }
                ?></textarea>
                        <?php $publishpress->settings->helper_print_error_or_description('description', __('The description can be used to communicate with your team about what the metadata is for.', 'publishpress'));
                ?>
                    </div>
                    <div class="form-field form-required">
                        <label for="metadata_type"><?php _e('Type', 'publishpress');
                ?></label>
                        <?php
                            $metadata_types = $this->get_supported_metadata_types();
                            // Select the previously selected metadata type if a valid one exists
                            $current_metadata_type = (isset($_POST['metadata_type']) && in_array($_POST['metadata_type'], array_keys($metadata_types))) ? $_POST['metadata_type'] : false;
                ?>
                        <select id="metadata_type" name="metadata_type">
                        <?php foreach ($metadata_types as $metadata_type => $metadata_type_name) : ?>
                            <option value="<?php echo esc_attr($metadata_type);
                ?>" <?php selected($metadata_type, $current_metadata_type);
                ?>><?php echo esc_attr($metadata_type_name);
                ?></option>
                        <?php endforeach;
                ?>
                        </select>
                        <?php $publishpress->settings->helper_print_error_or_description('type', __('Indicate the type of editorial metadata.', 'publishpress'));
                ?>
                    </div>
                    <div class="form-field form-required">
                        <label for="metadata_viewable"><?php _e('Viewable', 'publishpress');
                ?></label>
                        <?php
                            $metadata_viewable_options = array(
                                'no' => __('No', 'publishpress'),
                                'yes' => __('Yes', 'publishpress'),
                            );
                $current_metadata_viewable = (isset($_POST['metadata_viewable']) && in_array($_POST['metadata_viewable'], array_keys($metadata_viewable_options))) ? $_POST['metadata_viewable'] : 'no';
                ?>
                        <select id="metadata_viewable" name="metadata_viewable">
                        <?php foreach ($metadata_viewable_options as $metadata_viewable_key => $metadata_viewable_value) : ?>
                            <option value="<?php echo esc_attr($metadata_viewable_key);
                ?>" <?php selected($current_metadata_viewable, $metadata_viewable_key);
                ?>><?php echo esc_attr($metadata_viewable_value);
                ?></option>
                        <?php endforeach;
                ?>
                        </select>
                        <?php $publishpress->settings->helper_print_error_or_description('viewable', __('When viewable, metadata can be seen on views other than the edit post view (e.g. calendar, manage posts, story budget, etc.)', 'publishpress'));
                ?>
                    </div>
                    <?php wp_nonce_field('editorial-metadata-add-nonce');
                ?>
                    <input type="hidden" id="form-action" name="form-action" value="add-term" />
                    <p class="submit"><?php submit_button(__('Add New Metadata Term', 'publishpress'), 'primary', 'submit', false);
                ?><a class="cancel-settings-link" href="<?php echo PUBLISHPRESS_SETTINGS_PAGE;
                ?>"><?php _e('Back to PublishPress', 'publishpress');
                ?></a></p>
                    </form>
                <?php endif;
                ?>
                    </div>
                    </div>
                </div>

                <?php
            endif;
        }
    }
}

/**
 * Management interface for Editorial Metadata. Extends WP_List_Table class
 */
class PP_Editorial_Metadata_List_Table extends WP_List_Table
{

    public $callback_args;
    public $taxonomy;
    public $tax;

    /**
     * Construct the class
     */
    public function __construct()
    {
        global $publishpress;

        $this->taxonomy = PP_Editorial_Metadata::metadata_taxonomy;

        $this->tax = get_taxonomy($this->taxonomy);

        $columns = $this->get_columns();
        $hidden  = array(
            'position',
        );
        $sortable = array();

        $this->_column_headers = array($columns, $hidden, $sortable);

        parent::__construct(array(
            'plural' => 'editorial metadata',
            'singular' => 'editorial metadata',
        ));
    }

    /**
     * Prepare the items to be displayed on the list table
     *
     * @since 0.7
     */
    public function prepare_items()
    {
        global $publishpress;
        $this->items = $publishpress->editorial_metadata->get_editorial_metadata_terms();

        $this->set_pagination_args(array(
            'total_items' => count($this->items),
            'per_page' => count($this->items),
        ));
    }

    /**
     * Message to be displayed when there is no editorial metadata
     *
     * @since 0.7
     */
    public function no_items()
    {
        _e('No editorial metadata found.', 'publishpress');
    }

    /**
     * Register the columns to appear in the table
     *
     * @since 0.7
     */
    public function get_columns()
    {
        $columns = array(
            'position'    => __('Position', 'publishpress'),
            'name'        => __('Name', 'publishpress'),
            'type'        => __('Metadata Type', 'publishpress'),
            'description' => __('Description', 'publishpress'),
            'viewable'    => __('Viewable', 'publishpress'),
        );

        return $columns;
    }

    /**
     * Prepare a single row of Editorial Metadata
     *
     * @since 0.7
     *
     * @param object $term The current term we're displaying
     * @param int $level Level is always zero because it isn't a parent-child tax
     */
    public function single_row($term, $level = 0)
    {
        static $alternate_class = '';
        $alternate_class        = ($alternate_class == '' ? ' alternate' : '');
        $row_class              = ' class="term-static' . $alternate_class . '"';

        echo '<tr id="term-' . $term->term_id . '"' . $row_class . '>';
        echo $this->single_row_columns($term);
        echo '</tr>';
    }

    /**
     * Handle the column output when there's no method for it
     *
     * @since 0.7
     *
     * @param object $item Editorial Metadata term as an object
     * @param string $column_name How the column was registered at birth
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'position':
            case 'type':
            case 'description':
                return esc_html($item->$column_name);
                break;
            case 'viewable':
                if ($item->viewable) {
                    return __('Yes', 'publishpress');
                } else {
                    return __('No', 'publishpress');
                }
                break;
            default:
                break;
        }
    }

    /**
     * Column for displaying the term's name and associated actions
     *
     * @since 0.7
     *
     * @param object $item Editorial Metadata term as an object
     */
    public function column_name($item)
    {
        global $publishpress;
        $item_edit_link   = esc_url($publishpress->editorial_metadata->get_link(array('action' => 'edit-term', 'term-id' => $item->term_id)));
        $item_delete_link = esc_url($publishpress->editorial_metadata->get_link(array('action' => 'delete-term', 'term-id' => $item->term_id)));

        $out = '<strong><a class="row-title" href="' . $item_edit_link . '">' . esc_html($item->name) . '</a></strong>';

        $actions                         = array();
        $actions['edit']                 = "<a href='$item_edit_link'>" . __('Edit', 'publishpress') . "</a>";
        $actions['inline hide-if-no-js'] = '<a href="#" class="editinline">' . __('Quick&nbsp;Edit') . '</a>';
        if ($item->viewable) {
            $actions['change-visibility make-hidden'] = '<a title="' . esc_attr(__('Hidden metadata can only be viewed on the edit post view.', 'publishpress')) . '" href="' . esc_url($publishpress->editorial_metadata->get_link(array('action' => 'make-hidden', 'term-id' => $item->term_id))) . '">' . __('Make Hidden', 'publishpress') . '</a>';
        } else {
            $actions['change-visibility make-viewable'] = '<a title="' . esc_attr(__('When viewable, metadata can be seen on views other than the edit post view (e.g. calendar, manage posts, story budget, etc.)', 'publishpress')) . '" href="' . esc_url($publishpress->editorial_metadata->get_link(array('action' => 'make-viewable', 'term-id' => $item->term_id))) . '">' . __('Make Viewable', 'publishpress') . '</a>';
        }
        $actions['delete delete-status'] = "<a href='$item_delete_link'>" . __('Delete', 'publishpress') . "</a>";

        $out .= $this->row_actions($actions, false);
        $out .= '<div class="hidden" id="inline_' . $item->term_id . '">';
        $out .= '<div class="name">' . $item->name . '</div>';
        $out .= '<div class="description">' . $item->description . '</div>';
        $out .= '</div>';

        return $out;
    }

    /**
     * Admins can use the inline edit capability to quickly make changes to the title or description
     *
     * @since 0.7
     */
    public function inline_edit()
    {
        ?>
        <form method="get" action=""><table style="display: none"><tbody id="inlineedit">
            <tr id="inline-edit" class="inline-edit-row" style="display: none"><td colspan="<?php echo $this->get_column_count();
            ?>" class="colspanchange">
                <fieldset><div class="inline-edit-col">
                    <h4><?php _e('Quick Edit');
            ?></h4>
                    <label>
                        <span class="title"><?php _e('Name', 'publishpress');
            ?></span>
                        <span class="input-text-wrap"><input type="text" name="name" class="ptitle" value="" maxlength="200" /></span>
                    </label>
                    <label>
                        <span class="title"><?php _e('Description', 'publishpress');
            ?></span>
                        <span class="input-text-wrap"><input type="text" name="description" class="pdescription" value="" /></span>
                    </label>
                </div></fieldset>
            <p class="inline-edit-save submit">
                <a accesskey="c" href="#inline-edit" title="<?php _e('Cancel');
            ?>" class="cancel button-secondary alignleft"><?php _e('Cancel');
            ?></a>
                <?php $update_text = __('Update Metadata Term', 'publishpress');
            ?>
                <a accesskey="s" href="#inline-edit" title="<?php echo esc_attr($update_text);
            ?>" class="save button-primary alignright"><?php echo $update_text;
            ?></a>
                <img class="waiting" style="display:none;" src="<?php echo esc_url(admin_url('images/wpspin_light.gif'));
            ?>" alt="" />
                <span class="error" style="display:none;"></span>
                <?php wp_nonce_field('editorial-metadata-inline-edit-nonce', 'inline_edit', false);
            ?>
                <br class="clear" />
            </p>
            </td></tr>
            </tbody></table></form>
    <?php

    }
}
