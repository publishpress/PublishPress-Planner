<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
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

if (!class_exists('PP_Module')) {
    /**
     * PP_Module
     */
    class PP_Module
    {
        /**
         * @var \PublishPress\Core\ViewInterface
         */
        protected $view;

        protected $debug = false;

        public $options;

        public $published_statuses = [
            'publish',
            // 'future',
            'private',
        ];

        protected $viewsPath;

        public function __construct()
        {
            if (!empty($this->viewsPath)) {
                $this->view = new \PublishPress\Core\View();
            }

            foreach(get_post_stati(['public' => true, 'private' => true], 'names', 'OR') as $status) {
                if (!in_array($status, $this->published_statuses)) {
                    $this->published_statuses []= $status;
                }
            }
        }

        /**
         * Returns whether the module with the given name is enabled.
         *
         * @param string module Slug of the module to check
         *
         * @return <code>true</code> if the module is enabled, <code>false</code> otherwise
         * @since  0.7
         *
         */
        public function module_enabled($slug)
        {
            global $publishpress;

            if ('custom_status' == $slug) {
                return class_exists('PublishPress_Statuses');
            }

            return isset($publishpress->$slug) && $publishpress->$slug->module->options->enabled == 'on';
        }

        /**
         * Gets an array of allowed post types for a module
         *
         * @return array post-type-slug => post-type-label
         */
        public function get_all_post_types($module = null)
        {
            $allowed_post_types = [
                'post' => __('Post'),
                'page' => __('Page'),
            ];

            if (is_null($module)) {
                $module = $this;
            }

            $custom_post_types = $this->get_supported_post_types_for_module($module);

            foreach ($custom_post_types as $custom_post_type => $args) {
                $allowed_post_types[$custom_post_type] = $args->label;
            }

            return $allowed_post_types;
        }

        /**
         * Cleans up the 'on' and 'off' for post types on a given module (so we don't get warnings all over)
         * For every post type that doesn't explicitly have the 'on' value, turn it 'off'
         * If add_post_type_support() has been used anywhere (legacy support), inherit the state
         *
         * @param array $module_post_types Current state of post type options for the module
         * @param string $post_type_support What the feature is called for post_type_support (e.g. 'pp_calendar')
         *
         * @return array $normalized_post_type_options The setting for each post type, normalized based on rules
         *
         * @since 0.7
         */
        public function clean_post_type_options($module_post_types = [], $post_type_support = null)
        {
            $normalized_post_type_options = [];
            $all_post_types               = array_keys($this->get_all_post_types());
            foreach ($all_post_types as $post_type) {
                if ((isset($module_post_types[$post_type]) && $module_post_types[$post_type] == 'on') || post_type_supports(
                        $post_type,
                        $post_type_support
                    )) {
                    $normalized_post_type_options[$post_type] = 'on';
                } else {
                    $normalized_post_type_options[$post_type] = 'off';
                }
            }

            return $normalized_post_type_options;
        }

        /**
         * Get all of the possible post types that can be used with a given module
         *
         * @param object $module The full module
         *
         * @return array $post_types An array of post type objects
         *
         * @since 0.7.2
         */
        public function get_supported_post_types_for_module($module = null)
        {
            $pt_args = [
                '_builtin' => false,
                'show_ui'  => true,
            ];
            $pt_args = apply_filters('publishpress_supported_module_post_types_args', $pt_args, $module);

            $postTypes = get_post_types($pt_args, 'objects');

            $postTypes = apply_filters('publishpress_supported_module_post_types', $postTypes);

            // Hide notification workflows from the list
            if (isset($postTypes['psppnotif_workflow'])) {
                unset($postTypes['psppnotif_workflow']);
            }

            return $postTypes;
        }

        /**
         * Collect all of the active post types for a given module
         *
         * @param object $module Module's data
         *
         * @return array $post_types All of the post types that are 'on'
         *
         * @since 0.7
         */
        public function get_post_types_for_module($module)
        {
            return PublishPress\Legacy\Util::get_post_types_for_module($module);
        }

        /**
         * Get all of the currently available post statuses
         *
         * @return array $post_statuses All of the post statuses that aren't a published state
         *
         * @since 0.7
         */
        public function get_post_statuses()
        {
            global $publishpress;

            return $publishpress->getPostStatuses();
        }

        /**
         * Get core's 'draft' and 'pending' post statuses, but include our special attributes
         *
         * @return array
         * @since 0.8.1
         *
         */
        protected function get_core_post_statuses()
        {
            global $publishpress;

            return $publishpress->getCorePostStatuses();
        }

        /**
         * Back compat for existing code calling $publishpress->custom_status->get_custom_status_by()
         *
         * @return object
         *
         * @since 4.0
         */
        public function get_custom_status_by($field, $value) {
            global $publishpress;

            return $publishpress->getPostStatusBy($field, $value);
        }

        /**
         * Filter to all posts with a given post status (can be a custom status or a built-in status) and optional custom post type.
         *
         * @param string $slug The slug for the post status to which to filter
         * @param string $post_type Optional post type to which to filter
         *
         * @return an edit.php link to all posts with the given post status and, optionally, the given post type
         * @since 0.7
         *
         */
        public function filter_posts_link($slug, $post_type = 'post')
        {
            $filter_link = add_query_arg('post_status', $slug, get_admin_url(null, 'edit.php'));
            if ($post_type != 'post' && in_array($post_type, get_post_types('', 'names'))) {
                $filter_link = add_query_arg('post_type', $post_type, $filter_link);
            }

            return $filter_link;
        }

        /**
         * Returns the friendly name for a given status
         *
         * @param string $status The status slug
         *
         * @return string $status_friendly_name The friendly name for the status
         * @since 0.7
         *
         */
        public function get_post_status_friendly_name($status)
        {
            global $publishpress;

            $status_friendly_name = '';

            $builtin_stati = [
                'publish' => __('Published', 'publishpress'),
                'draft'   => __('Draft', 'publishpress'),
                'future'  => __('Scheduled', 'publishpress'),
                'private' => __('Private', 'publishpress'),
                'pending' => __('Pending Review', 'publishpress'),
                'trash'   => __('Trash', 'publishpress'),
            ];

            // Custom statuses only handles workflow statuses
            if (!in_array($status, ['publish', 'future', 'private', 'trash'])) {
                $status_object = $publishpress->getPostStatusBy('slug', $status);

                if ($status_object && !is_wp_error($status_object)) {
                    $status_friendly_name = $status_object->label;
                }
            } elseif (array_key_exists($status, $builtin_stati)) {
                $status_friendly_name = $builtin_stati[$status];
            }

            return $status_friendly_name;
        }

        /**
         * Enqueue any resources (CSS or JS) associated with datepicker functionality
         *
         * @since 0.7
         */
        public function enqueue_datepicker_resources()
        {
            // Datepicker is available WordPress 3.3. We have to register it ourselves for previous versions of WordPress
            wp_enqueue_script('jquery-ui-datepicker');

            // Timepicker needs to come after jquery-ui-datepicker and jquery
            wp_enqueue_script(
                'publishpress-timepicker',
                PUBLISHPRESS_URL . 'common/libs/timepicker-v1.6.3.1/jquery-ui-timepicker-addon.min.js',
                ['jquery', 'jquery-ui-datepicker'],
                PUBLISHPRESS_VERSION,
                true
            );

            wp_enqueue_script(
                'publishpress-date_picker',
                PUBLISHPRESS_URL . 'common/js/pp_date.js',
                ['jquery', 'jquery-ui-datepicker', 'publishpress-timepicker'],
                PUBLISHPRESS_VERSION,
                true
            );

            // Now styles
            wp_enqueue_style(
                'publishpress-timepicker',
                PUBLISHPRESS_URL . 'common/libs/timepicker-v1.6.3.1/jquery-ui-timepicker-addon.min.css',
                ['wp-jquery-ui-dialog'],
                PUBLISHPRESS_VERSION,
                'screen'
            );
            wp_enqueue_style(
                'jquery-ui-datepicker',
                PUBLISHPRESS_URL . 'common/css/jquery.ui.datepicker.css',
                ['wp-jquery-ui-dialog'],
                PUBLISHPRESS_VERSION,
                'screen'
            );
            wp_enqueue_style(
                'jquery-ui-theme',
                PUBLISHPRESS_URL . 'common/css/jquery.ui.theme.css',
                false,
                PUBLISHPRESS_VERSION,
                'screen'
            );

            wp_localize_script(
                'publishpress-date_picker',
                'objectL10ndate',
                [
                    'date_format' => pp_convert_date_format_to_jqueryui_datepicker(get_option('date_format')),
                    'week_first_day' => esc_js(get_option('start_of_week')),
                ]
            );
        }

        /**
         * Checks for the current post type
         *
         * @return string|null $post_type The post type we've found, or null if no post type
         * @since 0.7
         */
        public function get_current_post_type()
        {
            return PublishPress\Legacy\Util::get_current_post_type();
        }

        /**
         * Wrapper for the get_user_meta() function so we can replace it if we need to
         *
         * @param int $user_id Unique ID for the user
         * @param string $key Key to search against
         * @param bool $single Whether or not to return just one value
         *
         * @return string|bool|array $value Whatever the stored value was
         * @since 0.7
         *
         */
        public function get_user_meta($user_id, $key, $string = true)
        {
            $response = null;
            $response = apply_filters('pp_get_user_meta', $response, $user_id, $key, $string);
            if (!is_null($response)) {
                return $response;
            }

            return get_user_meta($user_id, $key, $string);
        }

        /**
         * Wrapper for the update_user_meta() function so we can replace it if we need to
         *
         * @param int $user_id Unique ID for the user
         * @param string $key Key to search against
         * @param string|bool|array $value Whether or not to return just one value
         * @param string|bool|array $previous (optional) Previous value to replace
         *
         * @return bool $success Whether we were successful in saving
         * @since 0.7
         *
         */
        public function update_user_meta($user_id, $key, $value, $previous = null)
        {
            $response = null;
            $response = apply_filters('pp_update_user_meta', $response, $user_id, $key, $value, $previous);
            if (!is_null($response)) {
                return $response;
            }

            return update_user_meta($user_id, $key, $value, $previous);
        }

        /**
         * Take a status and a message, JSON encode and print
         *
         * @param string $status Whether it was a 'success' or an 'error'
         * @param string $message
         * @param array $data
         *
         * @since 0.7
         *
         */
        public function print_ajax_response($status, $message = '', $data = null)
        {
            header('Content-type: application/json;');

            $result = [
                'status'  => $status,
                'message' => $message,
            ];

            if (!is_null($data)) {
                $result['data'] = $data;
            }

            echo json_encode($result);

            exit;
        }

        /**
         * Whether or not the current page is a user-facing PublishPress View
         *
         * @param string $module_name (Optional) Module name to check against
         *
         * @since 0.7
         *
         * @todo  Think of a creative way to make this work
         *
         */
        protected function is_whitelisted_functional_view($module_name = null)
        {
            $whitelisted_pages = ['pp-calendar', 'pp-content-overview', 'pp-notif-log', 'pp-modules-settings'];
            
            if (isset($_GET['page']) &&  in_array($_GET['page'], $whitelisted_pages)) {
                return true;
            }
            
            return false;
        }

        /**
         * Whether or not the current page is an PublishPress settings view (either main or module)
         * Determination is based on $pagenow, $_GET['page'], and the module's $settings_slug
         * If there's no module name specified, it will return true against all PublishPress settings views
         *
         * @param string $module_name (Optional) Module name to check against
         *
         * @return bool $is_settings_view Return true if it is
         * @since 0.7
         *
         */
        public function is_whitelisted_settings_view($module_name = null)
        {
            global $pagenow, $publishpress;

            // All of the settings views are based on admin.php and a $_GET['page'] parameter
            if ($pagenow != 'admin.php' || !isset($_GET['page'])) {
                return false;
            }

            if (isset($_GET['page']) && ($_GET['page'] === 'pp-modules-settings' || $_GET['page'] === 'pp-editorial-metadata')) {

                if (empty($module_name)) {
                    return true;
                }

                if (!isset($_GET['settings_module']) || $_GET['settings_module'] === 'pp-modules-settings-settings') {
                    if (in_array($module_name, ['editorial_comments', 'notifications', 'dashboard', 'editorial_metadata'])) {
                        return true;
                    }
                }

                $slug = str_replace('_', '-', $module_name);
                if (isset($_GET['settings_module']) && $_GET['settings_module'] === 'pp-' . $slug . '-settings') {
                    return true;
                }
            }

            return false;
        }

        /**
         * Encode all of the given arguments as a serialized array, and then base64_encode
         * Used to store extra data in a term's description field.
         *
         * @param array $args The arguments to encode
         *
         * @return string Arguments encoded in base64
         * @since 0.7
         *
         */
        public function get_encoded_description($args = [])
        {
            return base64_encode(maybe_serialize($args));
        }

        /**
         * If given an encoded string from a term's description field,
         * return an array of values. Otherwise, return the original string
         *
         * @param string $string_to_unencode Possibly encoded string
         *
         * @return array Array if string was encoded, otherwise the string as the 'description' field
         * @since 0.7
         *
         */
        public function get_unencoded_description($string_to_unencode)
        {
            return maybe_unserialize(base64_decode($string_to_unencode));
        }

        public function get_path_base()
        {
            return PUBLISHPRESS_BASE_PATH;
        }

        public function get_publishpress_url()
        {
            return PUBLISHPRESS_URL;
        }

        /**
         * This method looks redundant but it is just an abstraction needed to make it possible to
         * test Windows paths on a *unix machine. If not used and overridden in stubs on the tests
         * it always return "." because Windows paths are not valid on *unix machines.
         *
         * @param $file
         * @return string
         */
        public function dirname($file)
        {
            return dirname($file);
        }

        /**
         * Get the publicly accessible URL for the module based on the filename
         *
         * @param string $filepath File path for the module
         *
         * @return string $module_url Publicly accessible URL for the module
         * @since 0.7
         *
         */
        public function get_module_url($file)
        {
            $file = str_replace($this->get_path_base(), '', $this->dirname($file));
            $module_url = untrailingslashit($this->get_publishpress_url()) . $file;

            return str_replace('\\', '/', trailingslashit($module_url));
        }

        /**
         * Produce a human-readable version of the time since a timestamp
         *
         * @param int $original The UNIX timestamp we're producing a relative time for
         *
         * @return string $relative_time Human-readable version of the difference between the timestamp and now
         */
        public function timesince($original)
        {
            // array of time period chunks
            $chunks = [
                [60 * 60 * 24 * 365, 'year'],
                [60 * 60 * 24 * 30, 'month'],
                [60 * 60 * 24 * 7, 'week'],
                [60 * 60 * 24, 'day'],
                [60 * 60, 'hour'],
                [60, 'minute'],
                [1, 'second'],
            ];

            $today = time(); /* Current unix time  */
            $since = $today - $original;

            if ($since > $chunks[2][0] || $original > $today) {
                $dateFormat = get_option('date_format', 'Y-m-d');

                $print = date($dateFormat, $original);

                if ($since > $chunks[0][0]) { // Seconds in a year
                    $print .= ', ' . date('Y', $original);
                }

                return $print;
            }

            // $j saves performing the count function each time around the loop
            for ($i = 0, $j = count($chunks); $i < $j; $i++) {
                $seconds = $chunks[$i][0];
                $name    = $chunks[$i][1];

                // finding the biggest chunk (if the chunk fits, break)
                if (($count = floor($since / $seconds)) != 0) {
                    break;
                }
            }

            return sprintf(_n("1 $name ago", "$count {$name}s ago", $count), $count);
        }

        /**
         * Displays a list of users that can be selected!
         *
         * @param ???
         * @param ???
         *
         * @since 0.7
         *
         * @todo  Add pagination support for blogs with billions of users
         *
         */
        public function users_select_form($selected = null, $args = null)
        {
            // Set up arguments
            $defaults    = [
                'list_class' => 'pp-users-select-form',
                'input_id'   => 'pp-selected-users',
            ];
            $parsed_args = wp_parse_args($args, $defaults);
            extract($parsed_args, EXTR_SKIP);

            $args = [
                'fields'  => [
                    'ID',
                    'display_name',
                    'user_email',
                ],
                'orderby' => 'display_name',
            ];
            $args = apply_filters('pp_users_select_form_get_users_args', $args);

            /**
             * Filters the list of users available for notification.
             *
             * @param array $users
             * @param int $post_id
             */
            $post_id = isset($_GET['post']) ? (int)$_GET['post'] : null;
            $users   = apply_filters('publishpress_notification_users_meta_box', get_users($args), $post_id);

            if (!is_array($selected)) {
                $selected = [];
            }

            // Extract emails from the selected list, if there is any.
            $emails = [];
            if (!empty($selected)) {
                foreach ($selected as $item) {
                    if (strpos($item, '@') > 0) {
                        $emails[] = $item;
                    }
                }
            }

            /**
             * Filters the list of roles available for notification.
             *
             * @param array $roles
             * @param int $post_id
             */
            $roles = apply_filters('publishpress_notification_roles_meta_box', get_editable_roles(), $post_id);
            ?>

            <?php if (!empty($users) || !empty($roles) || !empty($emails)) : ?>
            <select id="to_notify" class="chosen-select" name="to_notify[]" multiple="multiple">
                <?php if (!empty($roles)) : ?>
                    <optgroup label="<?php echo esc_attr__('Roles', 'publishpress'); ?>">
                        <?php foreach ($roles as $role => $data) : ?>
                            <?php $attrSelected = (in_array($role, $selected)) ? 'selected="selected"' : ''; ?>
                            <option value="<?php echo esc_attr($role); ?>" <?php echo $attrSelected; ?>><?php echo __(
                                    'Role',
                                    'publishpress'
                                ); ?>: <?php echo $data['name']; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if (!empty($users)) : ?>
                    <optgroup label="<?php echo __('Users', 'publishpress'); ?>">
                        <?php foreach ($users as $user) : ?>
                            <?php $attrSelected = (in_array($user->ID, $selected)) ? 'selected="selected"' : ''; ?>
                            <option value="<?php echo esc_attr(
                                $user->ID
                            ); ?>" <?php echo $attrSelected; ?>><?php echo $user->display_name; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if (!empty($emails)) : ?>
                    <optgroup label="<?php echo __('E-mails', 'publishpress'); ?>">
                        <?php foreach ($emails as $email) : ?>
                            <?php $attrSelected = (in_array($email, $selected)) ? 'selected="selected"' : ''; ?>
                            <option value="<?php echo esc_attr(
                                $email
                            ); ?>" <?php echo $attrSelected; ?>><?php echo $email; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
        <?php endif; ?>
            <?php
        }


        /**
         * @param $role
         * @param $caps
         *
         * @deprecated 1.9.8 Use PublishPress\Util class instead
         */
        public function add_caps_to_role($role, $caps)
        {
            PublishPress\Legacy\Util::add_caps_to_role($role, $caps);
        }

        /**
         * Add settings help menus to our module screens if the values exist
         * Auto-registered in PublishPress::register_module()
         *
         * @since 0.7
         */
        public function action_settings_help_menu()
        {
            $screen = get_current_screen();

            if (!method_exists($screen, 'add_help_tab')) {
                return;
            }

            if ($screen->id != 'planner_page_' . $this->module->settings_slug) {
                return;
            }

            // Make sure we have all of the required values for our tab
            if (isset($this->module->settings_help_tab['id'], $this->module->settings_help_tab['title'], $this->module->settings_help_tab['content'])) {
                $screen->add_help_tab($this->module->settings_help_tab);

                if (isset($this->module->settings_help_sidebar)) {
                    $screen->set_help_sidebar($this->module->settings_help_sidebar);
                }
            }
        }

        /**
         * Upgrade the term descriptions for all of the terms in a given taxonomy
         */
        public function upgrade_074_term_descriptions($taxonomy)
        {
            $args = [
                'hide_empty' => false,
            ];

            $terms = get_terms($taxonomy, $args);
            foreach ($terms as $term) {
                // If we can detect that this term already follows the new scheme, let's skip it
                $maybe_serialized = base64_decode($term->description);
                if (is_serialized($maybe_serialized)) {
                    continue;
                }

                $description_args = [];

                // This description has been JSON-encoded, so let's decode it
                if (0 === strpos($term->description, '{')) {
                    $string_to_unencode = stripslashes(htmlspecialchars_decode($term->description));
                    $unencoded_array    = json_decode($string_to_unencode, true);
                    // Only continue processing if it actually was an array. Otherwise, set to the original string
                    if (is_array($unencoded_array)) {
                        foreach ($unencoded_array as $key => $value) {
                            // html_entity_decode only works on strings but sometimes we store nested arrays
                            if (!is_array($value)) {
                                $description_args[$key] = html_entity_decode($value, ENT_QUOTES);
                            } else {
                                $description_args[$key] = $value;
                            }
                        }
                    }
                } else {
                    $description_args['description'] = $term->description;
                }

                $new_description = $this->get_encoded_description($description_args);
                wp_update_term(
                    $term->term_id,
                    $taxonomy,
                    [
                        'description' => $new_description,
                    ]
                );
            }
        }

        public static function isPublishPressModuleEnabled($module_slug)
        {
            global $publishpress;

            return isset($publishpress->{$module_slug})
                && $publishpress->{$module_slug}->module->options->enabled === 'on';
        }
    }
}
