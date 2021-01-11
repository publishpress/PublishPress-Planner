<?php
/**
 * @package PublishPress
 * @author PublishPress
 *
 * Copyright (c) 2018 PublishPress
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
        const metadata_taxonomy     = 'pp_editorial_meta';

        const metadata_postmeta_key = "_pp_editorial_meta";

        const SETTINGS_SLUG         = 'pp-editorial-metadata-settings';

        public $module_name = 'editorial_metadata';

        private $editorial_metadata_terms_cache = [];

        const CAP_VIEW_METADATA = 'pp_view_editorial_metadata';

        const CAP_EDIT_METADATA = 'pp_edit_editorial_metadata';

        /**
         * Stores a chain of input-type handlers.
         *
         * @var Editorial_Metadata_Input_Handler $editorial_metadata_input_handler
         */
        private $editorial_metadata_input_handler = null;

        /**
         * Construct the PP_Editorial_Metadata class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);
            // Register the module with PublishPress
            $args = [
                'title'                => __('Metadata', 'publishpress'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'editorial-metadata',
                'default_options'      => [
                    'enabled'    => 'on',
                    'post_types' => [
                        'post' => 'on',
                        'page' => 'off',
                    ],
                ],
                'messages' => [
                    'term-added'              => __("Metadata term added.", 'publishpress'),
                    'term-updated'            => __("Metadata term updated.", 'publishpress'),
                    'term-missing'            => __("Metadata term doesn't exist.", 'publishpress'),
                    'term-deleted'            => __("Metadata term deleted.", 'publishpress'),
                    'term-position-updated'   => __("Term order updated.", 'publishpress'),
                    'term-visibility-changed' => __("Term visibility changed.", 'publishpress'),
                ],
                'configure_page_cb' => 'print_configure_view',
                'settings_help_tab' => [
                    'id'      => 'pp-editorial-metadata-overview',
                    'title'   => __('Overview', 'publishpress'),
                    'content' => __('<p>Keep track of important details about your content with editorial metadata. This feature allows you to create as many date, text, number, etc. fields as you like, and then use them to store information like contact details or the location of an interview.</p><p>Once you’ve set your fields up, editorial metadata integrates with both the calendar and the content overview. Make an editorial metadata item visible to have it appear to the rest of your team. Keep it hidden to restrict the information between the writer and their editor.</p>', 'publishpress'),
                ],
                'settings_help_sidebar' => __('<p><strong>For more information:</strong></p><p><a href="https://publishpress.com/features/editorial-metadata/">Editorial Metadata Documentation</a></p><p><a href="https://github.com/ostraining/PublishPress">PublishPress on Github</a></p>', 'publishpress'),
                'options_page'       => true,
            ];
            PublishPress()->register_module($this->module_name, $args);
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            // Register the taxonomy we use for Editorial Metadata with WordPress core
            $this->register_taxonomy();

            // Load the chain of input-type handlers.
            $this->load_input_handlers();

            // Anything that needs to happen in the admin
            add_action('admin_init', [$this, 'action_admin_init']);

            // Register our settings
            add_action('admin_init', [$this, 'register_settings']);

            if ($this->checkEditCapability()) {
                // Actions relevant to the configuration view (adding, editing, or sorting existing Editorial Metadata)
                add_action('admin_init', [$this, 'handle_add_editorial_metadata']);
                add_action('admin_init', [$this, 'handle_edit_editorial_metadata']);
                add_action('admin_init', [$this, 'handle_change_editorial_metadata_visibility']);
                add_action('admin_init', [$this, 'handle_delete_editorial_metadata']);
                add_action('wp_ajax_update_term_positions', [$this, 'handle_ajax_update_term_positions']);

                add_action('save_post', [$this, 'save_meta_box'], 10, 2);
            }

            if ($this->checkEditCapability() || $this->checkViewCapability()) {
                add_action('add_meta_boxes', [$this, 'handle_post_metaboxes']);

                // Add Editorial Metadata columns to the Manage Posts view
                $supported_post_types = $this->get_post_types_for_module($this->module);
                foreach ($supported_post_types as $post_type) {
                    add_filter("manage_{$post_type}_posts_columns", [$this, 'filter_manage_posts_columns']);
                    add_action("manage_{$post_type}_posts_custom_column", [$this, 'action_manage_posts_custom_column'], 10, 2);
                }

                // Add Editorial Metadata to the calendar if the calendar is activated
                if ($this->module_enabled('calendar')) {
                    add_filter('pp_calendar_item_information_fields', [$this, 'filter_calendar_item_fields'], 10, 2);
                }

                // Add Editorial Metadata columns to the Content Overview if it exists
                if ($this->module_enabled('story_budget')) {
                    add_filter('pp_story_budget_term_columns', [$this, 'filter_story_budget_term_columns']);
                    // Register an action to handle this data later
                    add_filter('pp_story_budget_term_column_value', [$this, 'filter_story_budget_term_column_values'], 10, 3);
                }
            }

            // Load necessary scripts and stylesheets
            add_action('admin_enqueue_scripts', [$this, 'add_admin_scripts']);
        }

        /**
         * Load default editorial metadata the first time the module is loaded
         *
         * @since 0.7
         */
        public function install()
        {
            // Our default metadata fields
            $default_metadata = [
                [
                    'name'        => __('First Draft Date', 'publishpress'),
                    'slug'        => 'first-draft-date',
                    'type'        => 'date',
                    'description' => __('When the first draft needs to be ready.', 'publishpress'),
                ],
                [
                    'name'        => __('Assignment', 'publishpress'),
                    'slug'        => 'assignment',
                    'type'        => 'paragraph',
                    'description' => __('What the post needs to cover.', 'publishpress'),
                ],
            ];
            // Load the metadata fields if the slugs don't conflict
            foreach ($default_metadata as $args) {
                if (!term_exists($args['slug'], self::metadata_taxonomy)) {
                    $this->insert_editorial_metadata_term($args);
                }
            }

            $this->setDefaultCapabilities();
        }

        /**
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previousVersion)
        {
            global $publishpress;

            // Upgrade path to v0.7
            if (version_compare($previousVersion, '0.7', '<')) {
                // Technically we've run this code before so we don't want to auto-install new data
                $publishpress->update_module_option($this->module->name, 'loaded_once', true);
            }
            // Upgrade path to v0.7.4
            if (version_compare($previousVersion, '0.7.4', '<')) {
                // Editorial metadata descriptions become base64_encoded, instead of maybe json_encoded.
                $this->upgrade_074_term_descriptions(self::metadata_taxonomy);
            }

            if (version_compare($previousVersion, '3.0.1', '<=')) {
                $this->setDefaultCapabilities();
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
            add_action('parse_query', [$this, 'action_parse_query']);
        }

        /**
         * Generate <select> HTML for all of the metadata types
         */
        public function get_select_html($description)
        {
            $current_metadata_type = $description->type;
            $metadata_types        = $this->get_supported_metadata_types(); ?>
            <select id="<?php echo esc_attr(self::metadata_taxonomy); ?>'_type" name="<?php echo esc_attr(self::metadata_taxonomy); ?>'_type">
            <?php foreach ($metadata_types as $metadata_type => $metadata_type_name) : ?>
                <option value="<?php echo esc_attr($metadata_type); ?>" <?php selected($metadata_type, $current_metadata_type); ?>><?php echo esc_html($metadata_type_name); ?></option>
            <?php endforeach; ?>
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
            $supported_metadata_types = [
                'checkbox'  => __('Checkbox', 'publishpress'),
                'date'      => __('Date', 'publishpress'),
                'location'  => __('Location', 'publishpress'),
                'number'    => __('Number', 'publishpress'),
                'paragraph' => __('Paragraph', 'publishpress'),
                'text'      => __('Text', 'publishpress'),
                'user'      => __('User', 'publishpress'),
            ];
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
                $viewable_terms = [];
                foreach ($terms as $term) {
                    if ($term->viewable) {
                        $viewable_terms[] = $term;
                    }
                }
                if (!empty($viewable_terms)) {
                    $css_rules = [
                        '.wp-list-table.fixed .column-author' => [
                            'min-width: 7em;',
                            'width: auto;',
                        ],
                        '.wp-list-table.fixed .column-tags' => [
                            'min-width: 7em;',
                            'width: auto;',
                        ],
                        '.wp-list-table.fixed .column-categories' => [
                            'min-width: 7em;',
                            'width: auto;',
                        ],
                    ];
                    foreach ($viewable_terms as $viewable_term) {
                        switch ($viewable_term->type) {
                            case 'checkbox':
                            case 'number':
                            case 'date':
                                $css_rules['.wp-list-table.fixed .column-' . $this->module->slug . '-' . $viewable_term->slug] = [
                                    'min-width: 6em;',
                                ];
                                break;
                            case 'location':
                            case 'text':
                            case 'user':
                                $css_rules['.wp-list-table.fixed .column-' . $this->module->slug . '-' . $viewable_term->slug] = [
                                    'min-width: 7em;',
                                ];
                                break;
                            case 'paragraph':
                                $css_rules['.wp-list-table.fixed .column-' . $this->module->slug . '-' . $viewable_term->slug] = [
                                    'min-width: 8em;',
                                ];
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
                wp_enqueue_script('publishpress-editorial-metadata-configure', PUBLISHPRESS_URL . 'modules/editorial-metadata/lib/editorial-metadata-configure.js', ['jquery', 'jquery-ui-sortable'], PUBLISHPRESS_VERSION, true);

                wp_localize_script(
                    'publishpress-editorial-metadata-configure',
                    'objectL10nMetadata',
                    [
                        'pp_confirm_delete_term_string' => __('Are you sure you want to delete this term? Any metadata for this term will remain but will not be visible unless this term is re-added.', 'publishpress'),
                    ]
                );
            }
        }

        /**
         * Register the post metadata taxonomy
         */
        public function register_taxonomy()
        {

            // We need to make sure taxonomy is registered for all of the post types that support it
            $supported_post_types = $this->get_post_types_for_module($this->module);

            register_taxonomy(
                self::metadata_taxonomy,
                $supported_post_types,
                [
                    'public' => false,
                    'labels' => [
                        'name' => _x('Metadata', 'taxonomy general name', 'publishpress'),
                        'singular_name' => _x('Metadata', 'taxonomy singular name', 'publishpress'),
                        'search_items' => __('Search Editorial Metadata', 'publishpress'),
                        'popular_items' => __('Popular Editorial Metadata', 'publishpress'),
                        'all_items' => __('All Editorial Metadata', 'publishpress'),
                        'edit_item' => __('Edit Editorial Metadata', 'publishpress'),
                        'update_item' => __('Update Editorial Metadata', 'publishpress'),
                        'add_new_item' => __('Add New Editorial Metadata', 'publishpress'),
                        'new_item_name' => __('New Editorial Metadata', 'publishpress'),
                   ],
                    'rewrite' => false,
               ]
           );
        }

        public function getViewCapability()
        {
            return apply_filters('publishpress_view_editorial_metadata_cap', self::CAP_VIEW_METADATA);
        }

        public function getEditCapability()
        {
            return apply_filters('publishpress_edit_editorial_metadata_cap', self::CAP_EDIT_METADATA);
        }

        public function setDefaultCapabilities()
        {
            $roles = ['administrator', 'editor', 'author'];

            $viewCap = $this->getViewCapability();
            $editCap = $this->getEditCapability();

            foreach ($roles as $role) {
                $role = get_role($role);

                if (is_object($role) && !is_wp_error($role)) {
                    $role->add_cap($viewCap);
                    $role->add_cap($editCap);
                }
            }
        }

        /**
         * Load the post metaboxes for all of the post types that are supported
         */
        public function handle_post_metaboxes()
        {
            $title = __('Metadata', 'publishpress');

            $supported_post_types = $this->get_post_types_for_module($this->module);
            foreach ($supported_post_types as $post_type) {
                add_meta_box(self::metadata_taxonomy, $title, [$this, 'display_meta_box'], $post_type, 'side');
            }
        }

        public function checkViewCapability()
        {
            return current_user_can($this->getViewCapability());
        }

        public function checkEditCapability()
        {
            /**
             * The capability "pp_editorial_metadata_user_can_edit" is deprecated in favor of "pp_edit_editorial_metadata".
             */
            return current_user_can($this->getEditCapability()) || current_user_can('pp_editorial_metadata_user_can_edit');
        }

        protected function echo_not_set_span()
        {
            echo '<span class="pp_editorial_metadata_not_set">';
            esc_html_e('Not set', 'default');
            echo '</span>';
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
                    $message .= sprintf(__(' <a href="%s">Add fields to get started</a>.'), esc_url($this->get_link()));
                } else {
                    $message .= __(' Encourage your site administrator to configure your editorial workflow by adding editorial metadata.');
                }
                echo '<p>' . $message . '</p>';
            } else {
                foreach ($terms as $term) {
                    $postmeta_key     = esc_attr($this->get_postmeta_key($term));
                    $current_metadata = esc_attr($this->get_postmeta_value($term, $post->ID));

                    echo "<div class='" . esc_attr(self::metadata_taxonomy) . " " . esc_attr(self::metadata_taxonomy) . "_$term->type'>";

                    // Check if the user can edit the metadata
                    $can_edit = $this->checkEditCapability();

                    if ($can_edit) {
                        $this->editorial_metadata_input_handler->handleHtmlRendering(
                            $term->type,
                            [
                                'name'  => $postmeta_key,
                                'label' => $term->name,
                                'description' => $term->description,
                            ],
                            $current_metadata
                        );
                    } else {
                        $this->editorial_metadata_input_handler->handlePreviewRendering(
                            $term->type,
                            [
                                'name'  => $postmeta_key,
                                'label' => $term->name,
                                'description' => $term->description,
                            ],
                            $current_metadata
                        );
                    }

                    echo "</div>";
                    echo "<div class='clear'></div>";
                } // Done iterating through metadata terms
            }

            if (current_user_can('manage_options')) {
                // Make the metabox title include a link to edit the Editorial Metadata terms. Logic similar to how Core dashboard widgets work.
                echo '<span class="postbox-title-action"><a href="' . esc_url($this->get_link()) . '">' . __('Configure') . '</a></span>';
            }

            echo "</div>";
        }

        /**
         * Show date or datetime
         *
         * @param  int $current_date
         *
         * @return string
         * @since 0.8
         */
        private function show_date_or_datetime($current_date)
        {
            if (date('Hi', $current_date) == '0000') {
                return date(__('M d Y', 'publishpress'), $current_date);
            } else {
                return date(__('M d Y H:i', 'publishpress'), $current_date);
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
            $term_slugs = [];

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
                        $new_metadata = isset($_POST[$key . '_hidden']) ? $_POST[$key . '_hidden'] : '';
                        $date = DateTime::createFromFormat('Y-m-d H:i', $new_metadata);
                        if (false !== $date) {
                            $new_metadata = $date->getTimestamp();
                        }
                    } elseif ($type == 'number') {
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
         *
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
         *
         * @param array $filter_args Filter to specific arguments
         *
         * @return array $ordered_terms The terms as they should be ordered
         *@todo Figure out what we should do with the filter...
         *
         */
        public function get_editorial_metadata_terms($filter_args = [])
        {

            // Try to fetch from internal object cache
            $arg_hash = md5(serialize($filter_args));
            if (isset($this->editorial_metadata_terms_cache[$arg_hash])) {
                return $this->editorial_metadata_terms_cache[$arg_hash];
            }

            $args = [
                    'orderby'    => apply_filters('pp_editorial_metadata_term_order', 'name'),
                    'hide_empty' => false,
                ];

            $terms         = get_terms(self::metadata_taxonomy, $args);
            $ordered_terms = [];
            $hold_to_end   = [];
            // Order the terms
            foreach ($terms as $key => $term) {

                // Unencode and set all of our pseudo term meta because we need the position and viewable if they exists
                // First do an array_merge() on the term object to make sure the keys exist, then array_merge()
                // any values that may already exist
                $unencoded_description = $this->get_unencoded_description($term->description);
                $defaults              = [
                    'description' => '',
                    'viewable' => false,
                    'position' => false,
                ];
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
         *
         * @return object $term Term's object representation
         */
        public function get_editorial_metadata_term_by($field, $value)
        {
            if (! in_array($field, ['id', 'slug', 'name'])) {
                return false;
            }

            if ('id' == $field) {
                $field = 'term_id';
            }

            $terms = $this->get_editorial_metadata_terms();
            $term  = wp_filter_object_list($terms, [$field => $value]);

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
         * @param array $posts_columns Existing post columns prepared by WP_List_Table
         * @param array $posts_columns Previous post columns with the new values
         *
         *@since 0.7
         * @uses apply_filters('manage_posts_columns') in wp-admin/includes/class-wp-posts-list-table.php
         *
         */
        public function filter_manage_posts_columns($posts_columns)
        {
            $screen = get_current_screen();
            if ($screen) {
                add_filter("manage_{$screen->id}_sortable_columns", [$this, 'filter_manage_posts_sortable_columns']);
                $terms = $this->get_editorial_metadata_terms(['viewable' => true]);
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
         * @param array $sortable_columns Any existing sortable columns (e.g. Title)
         *
         * @return array $sortable_columms Sortable columns with editorial metadata date fields added
         *@since 0.7.4
         *
         */
        public function filter_manage_posts_sortable_columns($sortable_columns)
        {
            $terms = $this->get_editorial_metadata_terms(['viewable' => true, 'type' => 'date']);
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
         * @param string $column_name Unique string for the column
         * @param int $post_id ID for the post of the row
         *
         *@since 0.7
         * @uses do_action('manage_posts_custom_column') in wp-admin/includes/class-wp-posts-list-table.php
         *
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
         * @param array $calendar_fields Additional data fields to include on the calendar
         * @param int $post_id Unique ID for the post data we're building
         *
         * @return array $calendar_fields Calendar fields with our viewable Editorial Metadata added
         *@uses apply_filters('pp_calendar_item_information_fields')
         *
         * @since 0.7
         */
        public function filter_calendar_item_fields($calendar_fields, $post_id)
        {
            // Make sure we respect which post type we're on
            if (!in_array(get_post_type($post_id), $this->get_post_types_for_module($this->module))) {
                return $calendar_fields;
            }

            $terms = $this->get_editorial_metadata_terms(['viewable' => true]);

            foreach ($terms as $term) {
                $key = $this->module->slug . '-' . $term->slug;

                // Default values
                $current_metadata = $this->get_postmeta_value($term, $post_id);
                $term_data        = [
                    'label' => $term->name,
                    'value' => $this->generate_editorial_metadata_term_output($term, $current_metadata),
                ];
                $term_data['editable'] = true;
                $term_data['type']     = $term->type;
                $calendar_fields[$key] = $term_data;
            }
            return $calendar_fields;
        }

        /**
         * If the PublishPress Content Overview is enabled, register our viewable terms as columns
         *
         * @param array $term_columns The existing columns on the content overview
         *
         * @return array $term_columns Term columns with viewable Editorial Metadata terms
         *@since 0.7
         * @uses apply_filters('pp_story_budget_term_columns')
         *
         */
        public function filter_story_budget_term_columns($term_columns)
        {
            $terms = $this->get_editorial_metadata_terms(['viewable' => true]);
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
         * If the PublishPress Content Overview is enabled,
         *
         * @param object $post The post we're displaying
         * @param string $column_name Name of the column, as registered with PP_Story_Budget::register_term_columns
         * @param object $parent_term The parent term for the term column
         *
         *@uses apply_filters('pp_story_budget_term_column_value')
         *
         * @since 0.7
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
         * @param object      $term    The editorial metadata term
         *
         * @return string     $html    How the term should be rendered
         *@since 0.8
         *
         */
        private function generate_editorial_metadata_term_output($term, $pm_value)
        {
            return $this->editorial_metadata_input_handler->handleMetaValueHtmling(
                $term->type,
                $pm_value
            );
        }

        /**
         * Update an existing editorial metadata term if the term_id exists
         *
         * @param int $term_id The term's unique ID
         * @param array $args Any values that need to be updated for the term
         *
         * @return object|WP_Error $updated_term The updated term or a WP_Error object if something disastrous happened
         *@since 0.7
         *
         */
        public function update_editorial_metadata_term($term_id, $args)
        {
            $new_args = [];
            $old_term = $this->get_editorial_metadata_term_by('id', $term_id);
            if ($old_term) {
                $old_args = [
                    'position' => $old_term->position,
                    'name' => $old_term->name,
                    'slug' => $old_term->slug,
                    'description' => $old_term->description,
                    'type' => $old_term->type,
                    'viewable' => $old_term->viewable,
                ];
            }
            $new_args = array_merge($old_args, $args);

            // We're encoding metadata that isn't supported by default in the term's description field
            $args_to_encode = [
                'description' => $new_args['description'],
                'position' => $new_args['position'],
                'type' => $new_args['type'],
                'viewable' => $new_args['viewable'],
            ];
            $encoded_description     = $this->get_encoded_description($args_to_encode);
            $new_args['description'] = $encoded_description;

            $updated_term = wp_update_term($term_id, self::metadata_taxonomy, $new_args);

            // Reset the internal object cache
            $this->editorial_metadata_terms_cache = [];

            $updated_term = $this->get_editorial_metadata_term_by('id', $term_id);
            return $updated_term;
        }

        /**
         * Insert a new editorial metadata term
         *
         * @todo Handle conflicts with existing terms at that position (if relevant)
         *
         * @since 0.7
         */
        public function insert_editorial_metadata_term($args)
        {


            // Term is always added to the end of the list
            $default_position = count($this->get_editorial_metadata_terms()) + 2;
            $defaults         = [
                'position'    => $default_position,
                'name'        => '',
                'slug'        => '',
                'description' => '',
                'type'        => '',
                'viewable'    => false,
            ];
            $args      = array_merge($defaults, $args);
            $term_name = $args['name'];
            unset($args['name']);

            // We're encoding metadata that isn't supported by default in the term's description field
            $args_to_encode = [
                'description' => $args['description'],
                'position' => $args['position'],
                'type' => $args['type'],
                'viewable' => $args['viewable'],
            ];
            $encoded_description = $this->get_encoded_description($args_to_encode);
            $args['description'] = $encoded_description;

            $inserted_term = wp_insert_term($term_name, self::metadata_taxonomy, $args);

            // Reset the internal object cache
            $this->editorial_metadata_terms_cache = [];

            return $inserted_term;
        }

        /**
         * Settings and other management code
         */

        /**
         * Delete an existing editorial metadata term
         *
         * @param int $term_id The term we want deleted
         *
         * @return bool $result Whether or not the term was deleted
         *@since 0.7
         *
         */
        public function delete_editorial_metadata_term($term_id)
        {
            $result = wp_delete_term($term_id, self::metadata_taxonomy);

            // Reset the internal object cache
            $this->editorial_metadata_terms_cache = [];

            return $result;
        }

        /**
         * Generate a link to one of the editorial metadata actions
         *
         * @param array $args (optional) Action and any query args to add to the URL
         *
         * @return string $link Direct link to complete the action
         *@since 0.7
         *
         */
        public function get_link($args = [])
        {
            if (!isset($args['action'])) {
                $args['action'] = '';
            }
            if (!isset($args['page'])) {
                $args['page'] = PP_Modules_Settings::SETTINGS_SLUG;
            }
            if (!isset($args['module'])) {
                $args['module'] = self::SETTINGS_SLUG;
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
            if (!isset($_POST['submit'], $_POST['form-action'], $_GET['page'], $_GET['module'])
                || ($_GET['page'] != PP_Modules_Settings::SETTINGS_SLUG && $_GET['module'] != self::SETTINGS_SLUG) || $_POST['form-action'] != 'add-term') {
                return;
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'edit-publishpress-settings')) {
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

            $_REQUEST['form-errors'] = [];

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
            if (function_exists('mb_strlen')) {
                if (mb_strlen($term_name) > 200) {
                    $_REQUEST['form-errors']['name'] = __('Name cannot exceed 200 characters. Please try a shorter name.', 'publishpress');
                }
            } else {
                if (strlen($term_name) > 200) {
                    $_REQUEST['form-errors']['name'] = __('Name cannot exceed 200 characters. Please try a shorter name.', 'publishpress');
                }
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
            if (!empty($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';
                return;
            }

            // Try to add the status
            $args = [
                'name'        => $term_name,
                'description' => $term_description,
                'slug'        => $term_slug,
                'type'        => $term_type,
                'viewable'    => $term_viewable,
            ];
            $return = $this->insert_editorial_metadata_term($args);

            if (is_wp_error($return)) {
                wp_die(__('Error adding term.', 'publishpress'));
            }

            $redirect_url = $this->get_link(['message' => 'term-added', 'action' => 'add-new']);
            wp_redirect($redirect_url);

            exit;
        }

        /**
         * Handles a request to edit an editorial metadata
         */
        public function handle_edit_editorial_metadata()
        {
            if (!isset($_POST['submit'], $_GET['page'], $_GET['module'], $_GET['action'], $_GET['term-id'])
                || !($_GET['page'] === PP_Modules_Settings::SETTINGS_SLUG && $_GET['module'] === self::SETTINGS_SLUG)
                || $_GET['action'] != 'edit-term') {
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
            $_REQUEST['form-errors'] = [];

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
            if (!empty($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';
                return;
            }

            // Try to add the metadata term
            $args = [
                'name' => $new_name,
                'description' => $new_description,
                'viewable' => $new_viewable,
            ];
            $return = $this->update_editorial_metadata_term($existing_term->term_id, $args);

            if (is_wp_error($return)) {
                wp_die(__('Error updating term.', 'publishpress'));
            }

            $redirect_url = $this->get_link(['message' => 'term-updated', 'action' => 'add-new']);
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
            if (!isset($_GET['page'], $_GET['module'], $_GET['action'], $_GET['term-id'], $_GET['nonce'])
                || ($_GET['page'] != PP_Modules_Settings::SETTINGS_SLUG && $_GET['module'] != self::SETTINGS_SLUG) || !in_array($_GET['action'], ['make-viewable', 'make-hidden'])) {
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
            $args    = [];
            if ($_GET['action'] == 'make-viewable') {
                $args['viewable'] = true;
            } elseif ($_GET['action'] == 'make-hidden') {
                $args['viewable'] = false;
            }

            $return = $this->update_editorial_metadata_term($term_id, $args);

            if (is_wp_error($return)) {
                wp_die(__('Error updating term.', 'publishpress'));
            }

            $redirect_url = $this->get_link(['message' => 'term-visibility-changed', 'action' => 'add-new']);

            wp_redirect($redirect_url);
            exit;
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
                $args = [
                    'position' => (int)$position + 1,
                ];
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
            if (!isset($_GET['page'], $_GET['module'], $_GET['action'], $_GET['term-id'])
                || ($_GET['page'] != PP_Modules_Settings::SETTINGS_SLUG && $_GET['module'] != self::SETTINGS_SLUG) || $_GET['action'] != 'delete-term') {
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

            $redirect_url = $this->get_link(['message' => 'term-deleted', 'action' => 'add-new']);
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
            add_settings_field('post_types', __('Add to these post types:', 'publishpress'), [$this, 'settings_post_types_option'], $this->module->options_group_name, $this->module->options_group_name . '_general');
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
         * @param array $new_options New values that have been entered by the user
         *
         * @return array $new_options Form values after they've been sanitized
         *@since 0.7
         *
         */
        public function settings_validate($new_options)
        {
            // Whitelist validation for the post type options
            if (!isset($new_options['post_types'])) {
                $new_options['post_types'] = [];
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
            $wp_list_table->prepare_items(); ?>
            </script>
            <?php if (!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] != 'edit-term')): ?>
            <div id='col-right'>
            <div class='col-wrap'>
            <form id='posts-filter'; action=''; method='post'>
                <?php $wp_list_table->display(); ?>
                <?php wp_nonce_field('editorial-metadata-sortable', 'editorial-metadata-sortable'); ?>
            </form>
            </div>
            </div><!-- /col-right -->
            <?php endif; ?>

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
            $edit_term_link = $this->get_link(['action' => 'edit-term', 'term-id' => $term->term_id]);

            $name        = (isset($_POST['name'])) ? stripslashes($_POST['name']) : $term->name;
            $description = (isset($_POST['description'])) ? stripslashes($_POST['description']) : $term->description;
            if ($term->viewable) {
                $viewable = 'yes';
            } else {
                $viewable = 'no';
            }
            $viewable = (isset($_POST['viewable'])) ? stripslashes($_POST['viewable']) : $viewable; ?>

                <form method='post'; action="<?php echo esc_url($edit_term_link); ?>" >
                <input type='hidden'; name='action'; value='editedtag' />
                <input type='hidden'; name='tag_id'; value="<?php echo esc_attr($term->term_id); ?>" />
                <input type='hidden'; name='taxonomy'; value="<?php echo esc_attr(self::metadata_taxonomy) ?>" />
                <?php
                wp_original_referer_field();
                wp_nonce_field('editorial-metadata-edit-nonce'); ?>
                <table class='form-table'>
                    <tr class='form-field form-required'>
                        <th scope='row'; valign='top'><label for='name'><?php _e('Name'); ?></label></th>
                        <td><input name='name'; id='name'; type='text'; value="<?php echo esc_attr($name); ?>"; size='40'; aria-required='true' />
                        <?php $publishpress->settings->helper_print_error_or_description('name', __('The name is for labeling the metadata field.', 'publishpress')); ?>
                    </tr>
                    <tr class='form-field'>
                        <th scope='row'; valign='top'><?php _e('Slug', 'publishpress'); ?></th>
                        <td>
                            <input type='text'; disabled='disabled'; value="<?php echo esc_attr($term->slug); ?>" />
                            <p class='description'><?php _e('The slug cannot be changed once the term has been created.', 'publishpress'); ?></p>
                        </td>
                    </tr>
                    <tr class='form-field'>
                        <th scope='row'; valign='top'><label for='description'><?php _e('Description', 'publishpress'); ?></label></th>
                        <td>
                            <textarea name='description'; id='description'; rows='5'; cols='50'; style='width: 97%;'><?php echo esc_html($description); ?></textarea>
                        <?php $publishpress->settings->helper_print_error_or_description('description', __('The description can be used to communicate with your team about what the metadata is for.', 'publishpress')); ?>
                        </td>
                    </tr>
                    <tr class='form-field'>
                        <th scope='row'; valign='top'><?php _e('Type', 'publishpress'); ?></th>
                        <td>
                            <input type='text'; disabled='disabled'; value="<?php echo esc_attr($metadata_types[$type]); ?>" />
                            <p class='description'><?php _e('The metadata type cannot be changed once created.', 'publishpress'); ?></p>
                        </td>
                    </tr>
                    <tr class='form-field'>
                        <th scope='row'; valign='top'><?php _e('Viewable', 'publishpress'); ?></th>
                        <td>
                            <?php
                                $metadata_viewable_options = [
                                    'no' => __('No', 'publishpress'),
                                    'yes' => __('Yes', 'publishpress'),
                                ]; ?>
                            <select id='viewable'; name='viewable'>
                            <?php foreach ($metadata_viewable_options as $metadata_viewable_key => $metadata_viewable_value) : ?>
                                <option value="<?php echo esc_attr($metadata_viewable_key); ?>" <?php selected($viewable, $metadata_viewable_key); ?>><?php echo esc_attr($metadata_viewable_value); ?></option>
                            <?php endforeach; ?>
                            </select>
                            <?php $publishpress->settings->helper_print_error_or_description('viewable', __('When viewable, metadata can be seen on views other than the edit post view (e.g. calendar, manage posts, content overview, etc.)', 'publishpress')); ?>
                        </td>
                    </tr>
                <input type='hidden'; name="<?php echo esc_attr(self::metadata_taxonomy); ?>'_type"; value="<?php echo esc_attr($type); ?>" />
                </table>
                <p class='submit'>
                <?php submit_button(__('Update Metadata Term', 'publishpress'), 'primary', 'submit', false); ?>
                <a class='cancel-settings-link'; href="<?php echo esc_url($this->get_link()); ?>"><?php _e('Cancel', 'publishpress'); ?></a>
                </p>
                </form>

                <?php else: ?>
                <?php /** If not in full-screen edit term mode, we can create new terms or change options **/ ?>
                <?php
                $showOptionsTab = (!isset($_GET['action']) || $_GET['action'] != 'add-new') && (!isset($_REQUEST['form-errors']) || empty($_REQUEST['form-errors']));
                ?>
                <div id='col-left'>
                    <div class='col-wrap'>
                    <div class='form-wrap'>
                    <h3 class='nav-tab-wrapper'>
                        <a href="<?php echo esc_url($this->get_link()); ?>" class="nav-tab<?php echo $showOptionsTab ? ' nav-tab-active' : ''; ?>">
                            <?php _e('Options', 'publishpress'); ?>
                        </a>
                        <a href="<?php echo esc_url($this->get_link(['action' => 'add-new'])); ?>" class="nav-tab<?php echo !$showOptionsTab ? ' nav-tab-active' : ''; ?>">
                            <?php _e('Add New', 'publishpress'); ?>
                        </a>
                    </h3>

                    <?php if (!$showOptionsTab): ?>
                        <?php /** Custom form for adding a new Editorial Metadata term **/ ?>
                        <form class='add:the-list:'; action="<?php echo esc_url($this->get_link()); ?>"; method='post'; id='addmetadata'; name='addmetadata'>
                        <div class='form-field form-required'>
                            <label for='metadata_name'><?php _e('Name', 'publishpress'); ?></label>
                            <input type="text"; aria-required='true'; size='20'; maxlength='200'; id='metadata_name'; name='metadata_name'; value="<?php if (!empty($_POST['metadata_name'])) {
                                        echo esc_attr(stripslashes($_POST['metadata_name']));
                                    } ?>" />
                            <?php $publishpress->settings->helper_print_error_or_description('name', __('The name is for labeling the metadata field.', 'publishpress')); ?>
                        </div>
                        <div class='form-field form-required'>
                            <label for='metadata_slug'><?php _e('Slug', 'publishpress'); ?></label>
                            <input type="text"; aria-required='true'; size='20'; maxlength='200'; id='metadata_slug'; name='metadata_slug'; value="<?php if (!empty($_POST['metadata_slug'])) {
                                        echo esc_attr($_POST['metadata_slug']);
                                    } ?>" />
                            <?php $publishpress->settings->helper_print_error_or_description('slug', __('The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'publishpress')); ?>
                        </div>
                        <div class='form-field'>
                            <label for='metadata_description'><?php _e('Description', 'publishpress'); ?></label>
                            <textarea cols="40"; rows='5'; id='metadata_description'; name='metadata_description'><?php if (!empty($_POST['metadata_description'])) {
                                        echo esc_html(stripslashes($_POST['metadata_description']));
                                    } ?></textarea>
                            <?php $publishpress->settings->helper_print_error_or_description('description', __('The description can be used to communicate with your team about what the metadata is for.', 'publishpress')); ?>
                        </div>
                        <div class='form-field form-required'>
                            <label for='metadata_type'><?php _e('Type', 'publishpress'); ?></label>
                            <?php
                                $metadata_types = $this->get_supported_metadata_types();
                // Select the previously selected metadata type if a valid one exists
                $current_metadata_type = (isset($_POST['metadata_type']) && in_array($_POST['metadata_type'], array_keys($metadata_types))) ? $_POST['metadata_type'] : false; ?>
                            <select id="metadata_type"; name='metadata_type'>
                            <?php foreach ($metadata_types as $metadata_type => $metadata_type_name) : ?>
                                <option value="<?php echo esc_attr($metadata_type); ?>" <?php selected($metadata_type, $current_metadata_type); ?>><?php echo esc_attr($metadata_type_name); ?></option>
                            <?php endforeach; ?>
                            </select>
                            <?php $publishpress->settings->helper_print_error_or_description('type', __('Indicate the type of editorial metadata.', 'publishpress')); ?>
                        </div>
                        <div class='form-field form-required'>
                            <label for='metadata_viewable'><?php _e('Viewable', 'publishpress'); ?></label>
                            <?php
                                $metadata_viewable_options = [
                                    'no' => __('No', 'publishpress'),
                                    'yes' => __('Yes', 'publishpress'),
                                ];
                $current_metadata_viewable = (isset($_POST['metadata_viewable']) && in_array($_POST['metadata_viewable'], array_keys($metadata_viewable_options))) ? $_POST['metadata_viewable'] : 'no'; ?>
                            <select id="metadata_viewable"; name='metadata_viewable'>
                            <?php foreach ($metadata_viewable_options as $metadata_viewable_key => $metadata_viewable_value) : ?>
                                <option value="<?php echo esc_attr($metadata_viewable_key); ?>" <?php selected($current_metadata_viewable, $metadata_viewable_key); ?>><?php echo esc_attr($metadata_viewable_value); ?></option>
                            <?php endforeach; ?>
                            </select>
                            <?php $publishpress->settings->helper_print_error_or_description('viewable', __('When viewable, metadata can be seen on views other than the edit post view (e.g. calendar, manage posts, content overview, etc.)', 'publishpress')); ?>
                        </div>
                        <?php wp_nonce_field('edit-publishpress-settings'); ?>

                        <input type='hidden'; id='form-action'; name='form-action'; value='add-term' />
                        <p class='submit'><?php submit_button(__('Add New Metadata Term', 'publishpress'), 'primary', 'submit', false); ?>&nbsp;</p>
                        </form>
                    <?php else: ?>
                        <?php /** Basic form built on WP Settings API for outputting Editorial Metadata options **/ ?>
                        <form class='basic-settings'; action="<?php echo esc_url($this->get_link(['action' => 'change-options'])); ?>" method='post'>
                            <br />
                            <p><?php echo __('Please note that checking a box will apply all metadata to that post type.', 'publishpress'); ?></p>
                            <?php settings_fields($this->module->options_group_name); ?>
                            <?php do_settings_sections($this->module->options_group_name); ?>
                            <?php echo '<input id="publishpress_module_name" name="publishpress_module_name[]" type="hidden" value="' . esc_attr($this->module->name) . '" />'; ?>
                            <?php wp_nonce_field('edit-publishpress-settings'); ?>

                            <?php submit_button(); ?>
                        </form>
                <?php endif; ?>
                    </div>
                    </div>
                </div>

                <?php
            endif;
        }

        /**
         * Load the chain of input-type handlers.
         *
         * @since   1.20.0
         */
        public function load_input_handlers()
        {
            $handlers_base_path = dirname(__FILE__) . '/input-handlers';

            require_once "{$handlers_base_path}/editorial-metadata-input-text-handler.php";
            require_once "{$handlers_base_path}/editorial-metadata-input-paragraph-handler.php";
            require_once "{$handlers_base_path}/editorial-metadata-input-number-handler.php";
            require_once "{$handlers_base_path}/editorial-metadata-input-date-handler.php";
            require_once "{$handlers_base_path}/editorial-metadata-input-checkbox-handler.php";
            require_once "{$handlers_base_path}/editorial-metadata-input-user-handler.php";
            require_once "{$handlers_base_path}/editorial-metadata-input-location-handler.php";

            $handlers = [
                new Editorial_Metadata_Input_Paragraph_Handler(),
                new Editorial_Metadata_Input_Text_Handler(),
                new Editorial_Metadata_Input_Number_Handler(),
                new Editorial_Metadata_Input_Date_Handler(),
                new Editorial_Metadata_Input_Checkbox_Handler(),
                new Editorial_Metadata_Input_User_Handler(),
                new Editorial_Metadata_Input_Location_Handler(),
            ];

            foreach ($handlers as $handler) {
                if (is_null($this->editorial_metadata_input_handler)) {
                    $this->editorial_metadata_input_handler = $handler;
                    continue;
                }

                $this->editorial_metadata_input_handler->registerHandler($handler);

                $handler_type = $handler->getType();

                add_filter("pp_editorial_metadata_{$handler_type}_render_value_html", [get_class($handler), 'getMetaValueHtml']);
            }
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
        $hidden  = [
            'position',
        ];
        $sortable = [];

        $this->_column_headers = [$columns, $hidden, $sortable];

        parent::__construct([
            'plural' => 'metadata',
            'singular' => 'metadata',
        ]);
    }

    /**
     * Register the columns to appear in the table
     *
     * @since 0.7
     */
    public function get_columns()
    {
        $columns = [
            'position'    => __('Position', 'publishpress'),
            'name'        => __('Name', 'publishpress'),
            'type'        => __('Metadata Type', 'publishpress'),
            'description' => __('Description', 'publishpress'),
            'viewable'    => __('Viewable', 'publishpress'),
        ];

        return $columns;
    }

    /**
     * Prepare a single row of Editorial Metadata
     *
     * @param object $term The current term we're displaying
     * @param int $level Level is always zero because it isn't a parent-child tax
     *
     *@since 0.7
     *
     */
    public function single_row($term, $level = 0)
    {
        static $alternate_class = '';
        $alternate_class        = ($alternate_class == '' ? ' alternate' : '');
        $row_class              = ' class="term-static' . $alternate_class . '"';

        echo '<tr id="term-' . esc_attr($term->term_id) . '"' . $row_class . '>';
        echo $this->single_row_columns($term);
        echo '</tr>';
    }

    /**
     * Handle the column output when there's no method for it
     *
     * @param object $item Editorial Metadata term as an object
     * @param string $column_name How the column was registered at birth
     *
     *@since 0.7
     *
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
     * Prepare the items to be displayed on the list table
     *
     * @since 0.7
     */
    public function prepare_items()
    {
        global $publishpress;
        $this->items = $publishpress->editorial_metadata->get_editorial_metadata_terms();

        $this->set_pagination_args([
            'total_items' => count($this->items),
            'per_page' => count($this->items),
        ]);
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
     * Column for displaying the term's name and associated actions
     *
     * @param object $item Editorial Metadata term as an object
     *
     *@since 0.7
     *
     */
    public function column_name($item)
    {
        global $publishpress;
        $item_edit_link   = esc_url($publishpress->editorial_metadata->get_link(['action' => 'edit-term', 'term-id' => $item->term_id]));
        $item_delete_link = esc_url($publishpress->editorial_metadata->get_link(['action' => 'delete-term', 'term-id' => $item->term_id]));

        $out = '<strong><a class="row-title" href="' . $item_edit_link . '">' . esc_html($item->name) . '</a></strong>';

        $actions                         = [];
        $actions['edit']                 = "<a href='$item_edit_link'>" . __('Edit', 'publishpress') . "</a>";
        if ($item->viewable) {
            $actions['change-visibility make-hidden'] = '<a title="' . esc_attr(__('Hidden metadata can only be viewed on the edit post view.', 'publishpress')) . '" href="' . esc_url($publishpress->editorial_metadata->get_link(['action' => 'make-hidden', 'term-id' => $item->term_id])) . '">' . __('Make Hidden', 'publishpress') . '</a>';
        } else {
            $actions['change-visibility make-viewable'] = '<a title="' . esc_attr(__('When viewable, metadata can be seen on views other than the edit post view (e.g. calendar, manage posts, content overview, etc.)', 'publishpress')) . '" href="' . esc_url($publishpress->editorial_metadata->get_link(['action' => 'make-viewable', 'term-id' => $item->term_id])) . '">' . __('Make Viewable', 'publishpress') . '</a>';
        }
        $actions['delete delete-status'] = "<a href='$item_delete_link'>" . __('Delete', 'publishpress') . "</a>";

        $out .= $this->row_actions($actions, false);

        return $out;
    }
}
