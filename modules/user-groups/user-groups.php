<?php
/**
 * @package PublishPress
 * @author  PublishPress
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

/**
 * class PP_User_Groups
 *
 * @todo Remove this module. It is deprecated.
 */

if (!class_exists('PP_User_Groups')) {
    /**
     * Class PP_User_Groups
     *
     * @deprecated
     */
    class PP_User_Groups extends PP_Module
    {
        const SETTINGS_SLUG = 'pp-user-groups-settings';

        public $module;

        /**
         * Keys for storing data
         * - taxonomy_key - used for custom taxonomy
         * - term_prefix - Used for custom taxonomy terms
         */
        const taxonomy_key = 'pp_usergroup';

        const term_prefix = 'pp-usergroup-';

        public $manage_usergroups_cap = 'edit_usergroups';

        /**
         * Register the module with PublishPress but don't do anything else
         *
         * @since 0.7
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the User Groups module with PublishPress
            $args         = [
                'title'                 => __('User Groups', 'publishpress'),
                'short_description'     => false,
                'extended_description'  => false,
                'module_url'            => $this->module_url,
                'icon_class'            => 'dashicons dashicons-groups',
                'slug'                  => 'user-groups',
                'default_options'       => [
                    'enabled'    => 'on',
                    'post_types' => [
                        'post' => 'on',
                        'page' => 'off',
                    ],
                ],
                'messages'              => [
                    'usergroup-added'   => __(
                        "User group created. Feel free to add users to the usergroup.",
                        'publishpress'
                    ),
                    'usergroup-updated' => __("User group updated.", 'publishpress'),
                    'usergroup-missing' => __("User group doesn't exist.", 'publishpress'),
                    'usergroup-deleted' => __("User group deleted.", 'publishpress'),
                ],
                'configure_page_cb'     => 'print_configure_view',
                'configure_link_text'   => __('Manage User Groups', 'publishpress'),
                'autoload'              => false,
                'settings_help_tab'     => [
                    'id'      => 'pp-user-groups-overview',
                    'title'   => __('Overview', 'publishpress'),
                    'content' => __(
                        '<p>For those with many people involved in the publishing process, user groups helps you keep them organized.</p><p>Currently, user groups are primarily used for subscribing a set of users to a post for notifications.</p>',
                        'publishpress'
                    ),
                ],
                'settings_help_sidebar' => __(
                    '<p><strong>For more information:</strong></p><p><a href="https://publishpress.com/features/user-groups/">User Groups Documentation</a></p><p><a href="https://github.com/ostraining/PublishPress">PublishPress on Github</a></p>',
                    'publishpress'
                ),
                //                'options_page'          => true,
            ];
            $this->module = PublishPress()->register_module('user_groups', $args);
        }

        /**
         * Module startup
         */

        /**
         * Initialize the rest of the stuff in the class if the module is active
         *
         * @since 0.7
         */
        public function init()
        {
            // Register the objects where we'll be storing data and relationships
            $this->register_usergroup_objects();

            $this->manage_usergroups_cap = apply_filters('pp_manage_usergroups_cap', $this->manage_usergroups_cap);

            // Register our settings
            add_action('admin_init', [$this, 'register_settings']);
        }

        /**
         * Load the capabilities onto users the first time the module is run
         *
         * @since 0.7
         */
        public function install()
        {
            // Create our default usergroups
            $default_usergroups = [
                [
                    'name'        => __('Copy Editors', 'publishpress'),
                    'description' => __('Making sure the quality is top-notch.', 'publishpress'),
                ],
                [
                    'name'        => __('Photographers', 'publishpress'),
                    'description' => __('Capturing the story visually.', 'publishpress'),
                ],
                [
                    'name'        => __('Reporters', 'publishpress'),
                    'description' => __('Out in the field, writing stories.', 'publishpress'),
                ],
                [
                    'name'        => __('Section Editors', 'publishpress'),
                    'description' => __('Providing feedback and direction.', 'publishpress'),
                ],
            ];
            foreach ($default_usergroups as $args) {
                $this->add_usergroup($args);
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
                global $wpdb;

                // Set all of the user group terms to our new taxonomy
                $wpdb->update(
                    $wpdb->term_taxonomy,
                    ['taxonomy' => self::taxonomy_key],
                    ['taxonomy' => 'following_usergroups']
                );

                // Get all of the users who are a part of user groups and assign them to their new user group values
                $query           = "SELECT * FROM $wpdb->usermeta WHERE meta_key='wp_pp_usergroups';";
                $usergroup_users = $wpdb->get_results($query);

                // Sort all of the users based on their usergroup(s)
                $users_to_add = [];
                foreach ((array)$usergroup_users as $usergroup_user) {
                    if (is_object($usergroup_user)) {
                        $users_to_add[$usergroup_user->meta_value][] = (int)$usergroup_user->user_id;
                    }
                }
                // Add user IDs to each usergroup
                foreach ($users_to_add as $usergroup_slug => $users_array) {
                    $usergroup = $this->get_usergroup_by('slug', $usergroup_slug);
                    $this->add_users_to_usergroup($users_array, $usergroup->term_id);
                }
                // Update the term slugs for each user group
                $all_usergroups = $this->get_usergroups();
                foreach ($all_usergroups as $usergroup) {
                    $new_slug = str_replace('pp_', self::term_prefix, $usergroup->slug);
                    $this->update_usergroup($usergroup->term_id, ['slug' => $new_slug]);
                }

                // Delete all of the previous usermeta values
                $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='wp_pp_usergroups';");

                // Technically we've run this code before so we don't want to auto-install new data
                $publishpress->update_module_option($this->module->name, 'loaded_once', true);
            }
            // Upgrade path to v0.7.4
            if (version_compare($previous_version, '0.7.4', '<')) {
                // Usergroup descriptions become base64_encoded, instead of maybe json_encoded.
                $this->upgrade_074_term_descriptions(self::taxonomy_key);
            }
        }

        /**
         * Individual Usergroups are stored using a custom taxonomy
         * Posts are associated with usergroups based on taxonomy relationship
         * User associations are stored serialized in the term's description field
         *
         * @since 0.7
         *
         * @uses  register_taxonomy()
         */
        public function register_usergroup_objects()
        {
            // Load the currently supported post types so we only register against those
            $supported_post_types = $this->get_post_types_for_module($this->module);

            // Use a taxonomy to manage relationships between posts and usergroups
            $args = [
                'public'  => false,
                'rewrite' => false,
            ];
            register_taxonomy(self::taxonomy_key, $supported_post_types, $args);
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         *
         * @since 0.7
         * @uses  add_settings_section(), add_settings_field()
         */
        public function register_settings()
        {
            add_settings_section(
                $this->module->options_group_name . '_general',
                false,
                '__return_false',
                $this->module->options_group_name
            );
            add_settings_field(
                'post_types',
                __('Add to these post types:', 'publishpress'),
                [$this, 'settings_post_types_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );
        }

        /**
         * Choose the post types for Usergroups
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
         * @since 0.7
         *
         */
        public function settings_validate($new_options)
        {
            // Whitelist validation for the post type options
            if (!isset($new_options['post_types'])) {
                $new_options['post_types'] = [];
            }
            $new_options['post_types'] = $this->clean_post_type_options(
                $new_options['post_types'],
                $this->module->post_type_support
            );

            return $new_options;
        }

        /**
         * Build a configuration view so we can manage our usergroups
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            echo '<div>' . __('The User Groups module is deprecated', 'publishpress') . '</div>';
        }

        /**
         * Generate a link to one of the usergroups actions
         *
         * @param string $action Action we want the user to take
         * @param array $args Any query args to add to the URL
         *
         * @return string $link Direct link to delete a usergroup
         * @since 0.7
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
                case 'delete-usergroup':
                    $args['nonce'] = wp_create_nonce($args['action']);
                    break;
                default:
                    break;
            }

            return add_query_arg($args, get_admin_url(null, 'admin.php'));
        }

        /**
         * Core Usergroups Module Functionality
         */

        /**
         * Get all of the registered usergroups. Returns an array of objects
         *
         * @param array $args Arguments to filter/sort by
         *
         * @return array|bool $usergroups Array of Usergroups with relevant data, false if none
         * @since 0.7
         *
         */
        public function get_usergroups($args = [])
        {
            // We want empty terms by default
            if (!isset($args['hide_empty'])) {
                $args['hide_empty'] = 0;
            }

            $usergroup_terms = get_terms(self::taxonomy_key, $args);
            if (!$usergroup_terms) {
                return false;
            }

            // Run the usergroups through get_usergroup_by() so we load users too
            $usergroups = [];
            foreach ($usergroup_terms as $usergroup_term) {
                $usergroups[] = $this->get_usergroup_by('id', $usergroup_term->term_id);
            }

            return $usergroups;
        }

        /**
         * Get all of the data associated with a single usergroup
         * Usergroup contains:
         * - ID (key = term_id)
         * - Slug (prefixed with our special key to avoid conflicts)
         * - Name
         * - Description
         * - User IDs (array of IDs)
         *
         * @param string $field 'id', 'name', or 'slug'
         * @param int|string $value Value for the search field
         *
         * @return object|array|WP_Error $usergroup Usergroup information as specified by $output
         * @since 0.7
         *
         */
        public function get_usergroup_by($field, $value)
        {
            $usergroup = get_term_by($field, $value, self::taxonomy_key);

            if (!$usergroup || is_wp_error($usergroup)) {
                return $usergroup;
            }

            // We're using an encoded description field to store extra values
            // Declare $user_ids ahead of time just in case it's empty
            $usergroup->user_ids   = [];
            $unencoded_description = $this->get_unencoded_description($usergroup->description);
            if (is_array($unencoded_description)) {
                foreach ($unencoded_description as $key => $value) {
                    $usergroup->$key = $value;
                }
            }

            return $usergroup;
        }

        /**
         * Create a new usergroup containing:
         * - Name
         * - Slug (prefixed with our special key to avoid conflicts)
         * - Description
         * - Users
         *
         * @param array $args Name (optional), slug and description for the usergroup
         * @param array $user_ids IDs for the users to be added to the Usergroup
         *
         * @return object|WP_Error $usergroup Object for the new Usergroup on success, WP_Error otherwise
         * @since 0.7
         *
         */
        public function add_usergroup($args = [], $user_ids = [])
        {
            if (!isset($args['name'])) {
                return new WP_Error('invalid', __('New user groups must have a name', 'publishpress'));
            }

            $name    = $args['name'];
            $default = [
                'name'        => '',
                'slug'        => self::term_prefix . sanitize_title($name),
                'description' => '',
            ];
            $args    = array_merge($default, $args);

            // Encode our extra fields and then store them in the description field
            $args_to_encode      = [
                'description' => $args['description'],
                'user_ids'    => array_unique($user_ids),
            ];
            $encoded_description = $this->get_encoded_description($args_to_encode);
            $args['description'] = $encoded_description;
            $usergroup           = wp_insert_term($name, self::taxonomy_key, $args);
            if (is_wp_error($usergroup)) {
                return $usergroup;
            }

            return $this->get_usergroup_by('id', $usergroup['term_id']);
        }

        /**
         * Update a usergroup with new data.
         * Fields can include:
         * - Name
         * - Slug (prefixed with our special key, of course)
         * - Description
         * - Users
         *
         * @param int $id Unique ID for the usergroup
         * @param array $args Usergroup meta to update (name, slug, description)
         * @param array $users Users to be added to the Usergroup. If set, removes existing users first.
         *
         * @return object|WP_Error $usergroup Object for the updated Usergroup on success, WP_Error otherwise
         * @since 0.7
         *
         */
        public function update_usergroup($id, $args = [], $users = null)
        {
            $existing_usergroup = $this->get_usergroup_by('id', $id);
            if (is_wp_error($existing_usergroup)) {
                return new WP_Error('invalid', __("User group doesn't exist.", 'publishpress'));
            }

            // Encode our extra fields and then store them in the description field
            $args_to_encode                = [];
            $args_to_encode['description'] = (isset($args['description'])) ? $args['description'] : $existing_usergroup->description;
            $args_to_encode['user_ids']    = (is_array($users)) ? $users : $existing_usergroup->user_ids;
            $args_to_encode['user_ids']    = array_unique($args_to_encode['user_ids']);
            $encoded_description           = $this->get_encoded_description($args_to_encode);
            $args['description']           = $encoded_description;

            $usergroup = wp_update_term($id, self::taxonomy_key, $args);
            if (is_wp_error($usergroup)) {
                return $usergroup;
            }

            return $this->get_usergroup_by('id', $usergroup['term_id']);
        }

        /**
         * Delete a usergroup based on its term ID
         *
         * @param int $id Unique ID for the Usergroup
         * @param bool|WP_Error Returns true on success, WP_Error on failure
         * @since 0.7
         *
         */
        public function delete_usergroup($id)
        {
            $retval = wp_delete_term($id, self::taxonomy_key);

            return $retval;
        }

        /**
         * Add an array of user logins or IDs to a given usergroup
         *
         * @param array $user_ids_or_logins User IDs or logins to be added to the usergroup
         * @param int $id Usergroup to perform the action on
         * @param bool $reset Delete all of the relationships before adding
         *
         * @return bool $success Whether or not we were successful
         * @since 0.7
         *
         */
        public function add_users_to_usergroup($user_ids_or_logins, $id, $reset = true)
        {
            if (!is_array($user_ids_or_logins)) {
                return new WP_Error('invalid', __("Invalid users variable. Should be array.", 'publishpress'));
            }

            // To dump the existing users from a usergroup, we need to pass an empty array
            $usergroup = $this->get_usergroup_by('id', $id);
            if ($reset) {
                $retval = $this->update_usergroup($id, null, []);
                if (is_wp_error($retval)) {
                    return $retval;
                }
            }

            // Add the new users one by one to an array we'll pass back to the usergroup
            $new_users = [];
            foreach ((array)$user_ids_or_logins as $user_id_or_login) {
                if (!is_numeric($user_id_or_login)) {
                    $new_users[] = get_user_by('login', $user_id_or_login)->ID;
                } else {
                    $new_users[] = (int)$user_id_or_login;
                }
            }
            $retval = $this->update_usergroup($id, null, $new_users);
            if (is_wp_error($retval)) {
                return $retval;
            }

            return true;
        }

        /**
         * Add a given user to a Usergroup. Can use User ID or user login
         *
         * @param int|string $user_id_or_login User ID or login to be added to the Usergroups
         * @param int|array $ids ID for the Usergroup(s)
         *
         * @return bool|WP_Error $retval Return true on success, WP_Error on error
         * @since 0.7
         *
         */
        public function add_user_to_usergroup($user_id_or_login, $ids)
        {
            if (!is_numeric($user_id_or_login)) {
                $user_id = get_user_by('login', $user_id_or_login)->ID;
            } else {
                $user_id = (int)$user_id_or_login;
            }

            foreach ((array)$ids as $usergroup_id) {
                $usergroup             = $this->get_usergroup_by('id', $usergroup_id);
                $usergroup->user_ids[] = $user_id;
                $retval                = $this->update_usergroup($usergroup_id, null, $usergroup->user_ids);
                if (is_wp_error($retval)) {
                    return $retval;
                }
            }

            return true;
        }

        /**
         * Remove a given user from one or more usergroups
         *
         * @param int|string $user_id_or_login User ID or login to be removed from the Usergroups
         * @param int|array $ids ID for the Usergroup(s)
         *
         * @return bool|WP_Error $retval Return true on success, WP_Error on error
         * @since 0.7
         *
         */
        public function remove_user_from_usergroup($user_id_or_login, $ids)
        {
            if (!is_numeric($user_id_or_login)) {
                $user_id = get_user_by('login', $user_id_or_login)->ID;
            } else {
                $user_id = (int)$user_id_or_login;
            }

            // Remove the user from each usergroup specified
            foreach ((array)$ids as $usergroup_id) {
                $usergroup = $this->get_usergroup_by('id', $usergroup_id);
                // @todo I bet there's a PHP function for this I couldn't look up at 35,000 over the Atlantic
                foreach ($usergroup->user_ids as $key => $usergroup_user_id) {
                    if ($usergroup_user_id == $user_id) {
                        unset($usergroup->user_ids[$key]);
                    }
                }
                $retval = $this->update_usergroup($usergroup_id, null, $usergroup->user_ids);
                if (is_wp_error($retval)) {
                    return $retval;
                }
            }

            return true;
        }

        /**
         * Get all of the Usergroup ids or objects for a given user
         *
         * @param int|string $user_id_or_login User ID or login to search against
         * @param array $ids_or_objects Whether to retrieve an array of IDs or usergroup objects
         * @param array|bool $usergroup_objects_or_ids Array of usergroup 'ids' or 'objects', false if none
         * @since 0.7
         *
         */
        public function get_usergroups_for_user($user_id_or_login, $ids_or_objects = 'ids')
        {
            if (!is_numeric($user_id_or_login)) {
                $user_id = get_user_by('login', $user_id_or_login)->ID;
            } else {
                $user_id = (int)$user_id_or_login;
            }

            // Unfortunately, the easiest way to do this is get all usergroups
            // and then loop through each one to see if the user ID is stored
            $all_usergroups = $this->get_usergroups();
            if (!empty($all_usergroups)) {
                $usergroup_objects_or_ids = [];
                foreach ($all_usergroups as $usergroup) {
                    // Not in this user group, so keep going
                    if (!isset($usergroup->user_ids) || false == ($usergroup->user_ids || !is_array(
                                $usergroup->user_ids
                            ))) {
                        continue;
                    }

                    if (!in_array($user_id, $usergroup->user_ids)) {
                        continue;
                    }
                    if ($ids_or_objects == 'ids') {
                        $usergroup_objects_or_ids[] = (int)$usergroup->term_id;
                    } elseif ($ids_or_objects == 'objects') {
                        $usergroup_objects_or_ids[] = $usergroup;
                    }
                }

                return $usergroup_objects_or_ids;
            } else {
                return false;
            }
        }
    }
}


if (!class_exists('PP_Usergroups_List_Table')) {
    /**
     * Usergroups uses WordPress' List Table API for generating the Usergroup management table
     *
     * @since 0.7
     */
    class PP_Usergroups_List_Table extends WP_List_Table
    {
        public $callback_args;

        public function __construct()
        {
            parent::__construct(
                [
                    'plural'   => 'user groups',
                    'singular' => 'user group',
                    'ajax'     => true,
                ]
            );
        }

        /**
         * @todo  Paginate if we have a lot of usergroups
         *
         * @since 0.7
         */
        public function prepare_items()
        {
            global $publishpress;

            $columns  = $this->get_columns();
            $hidden   = [];
            $sortable = [];

            $this->_column_headers = [$columns, $hidden, $sortable];

            $this->items = $publishpress->user_groups->get_usergroups();

            $this->set_pagination_args(
                [
                    'total_items' => count($this->items),
                    'per_page'    => count($this->items),
                ]
            );
        }

        /**
         * Message to be displayed when there are no usergroups
         *
         * @since 0.7
         */
        public function no_items()
        {
            _e('No user groups found.', 'publishpress');
        }

        /**
         * Columns in our Usergroups table
         *
         * @since 0.7
         */
        public function get_columns()
        {
            $columns = [
                'name'        => __('Name', 'publishpress'),
                'description' => __('Description', 'publishpress'),
                'users'       => __('Users in Group', 'publishpress'),
            ];

            return $columns;
        }

        /**
         * Process the Usergroup column value for all methods that aren't registered
         *
         * @since 0.7
         */
        public function column_default($usergroup, $column_name)
        {
        }

        /**
         * Process the Usergroup name column value.
         * Displays the name of the Usergroup, and action links
         *
         * @since 0.7
         */
        public function column_name($usergroup)
        {
            global $publishpress;

            // @todo direct edit link
            $output = '<strong><a href="' . esc_url(
                    $publishpress->user_groups->get_link(
                        [
                            'action'       => 'edit-usergroup',
                            'usergroup-id' => $usergroup->term_id,
                        ]
                    )
                ) . '">' . esc_html($usergroup->name) . '</a></strong>';

            $actions                            = [];
            $actions['edit edit-usergroup']     = sprintf(
                '<a href="%1$s">' . __('Edit', 'publishpress') . '</a>',
                esc_url(
                    $publishpress->user_groups->get_link(
                        [
                            'action'       => 'edit-usergroup',
                            'usergroup-id' => $usergroup->term_id,
                        ]
                    )
                )
            );
            $actions['delete delete-usergroup'] = sprintf(
                '<a href="%1$s">' . __('Delete', 'publishpress') . '</a>',
                esc_url(
                    $publishpress->user_groups->get_link(
                        [
                            'action'       => 'delete-usergroup',
                            'usergroup-id' => $usergroup->term_id,
                        ]
                    )
                )
            );

            $output .= $this->row_actions($actions, false);
            $output .= '<div class="hidden" id="inline_' . esc_attr($usergroup->term_id) . '">';
            $output .= '<div class="name">' . esc_html($usergroup->name) . '</div>';
            $output .= '<div class="description">' . esc_html($usergroup->description) . '</div>';
            $output .= '</div>';

            return $output;
        }

        /**
         * Handle the 'description' column for the table of Usergroups
         * Don't need to unencode this because we already did when the usergroup was loaded
         *
         * @since 0.7
         */
        public function column_description($usergroup)
        {
            return esc_html($usergroup->description);
        }

        /**
         * Show the "Total Users" in a given usergroup
         *
         * @since 0.7
         */
        public function column_users($usergroup)
        {
            global $publishpress;

            return '<a href="' . esc_url(
                    $publishpress->user_groups->get_link(
                        [
                            'action'       => 'edit-usergroup',
                            'usergroup-id' => $usergroup->term_id,
                        ]
                    )
                ) . '">' . count($usergroup->user_ids) . '</a>';
        }

        /**
         * Prepare a single row of information about a usergroup
         *
         * @since 0.7
         */
        public function single_row($usergroup)
        {
            static $row_class = '';
            $row_class = ($row_class == '' ? ' class="alternate"' : '');

            echo '<tr id="usergroup-' . esc_attr($usergroup->term_id) . '"' . $row_class . '>';
            echo $this->single_row_columns($usergroup);
            echo '</tr>';
        }
    }
}
