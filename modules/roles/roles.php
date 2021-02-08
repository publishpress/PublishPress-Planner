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

use PublishPress\Core\Modules\AbstractModule;
use PublishPress\Core\Modules\ModuleInterface;
use PublishPress\Legacy\Util;
use PublishPress\Notifications\Traits\Dependency_Injector;
use Twig\TwigFunction;

if (!class_exists('PP_Roles')) {
    if (!class_exists('PP_Roles_List_Table')) {
        require_once __DIR__ . '/lib/list_table.php';
    }

    /**
     * Class PP_Roles.
     *
     * @todo Rename this class for PSR-2 compliance.
     */
    class PP_Roles extends AbstractModule implements ModuleInterface
    {
        use Dependency_Injector;

        const SETTINGS_SLUG = 'pp-roles-settings';

        /**
         * @var string
         */
        const MENU_SLUG = 'pp-manage-roles';

        const VALUE_YES = 'yes';

        const VALUE_NO = 'no';

        const PAGE_SLUG = 'pp-manage-roles';

        public $module_name = 'roles';

        public $module;

        public $cap_manage_roles = 'pp_manage_roles';

        /**
         * Construct the PP_Roles class
         */
        public function __construct()
        {
            $this->twigPath = __DIR__ . '/twig';

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'           => __('Roles', 'publishpress'),
                'module_url'      => $this->module_url,
                'icon_class'      => 'dashicons dashicons-feedback',
                'slug'            => 'roles',
                'default_options' => [
                    'enabled' => 'on',
                ],
                'messages'        => [
                    'role-added'   => __("Role created. Feel free to add users to the role.", 'publishpress'),
                    'role-updated' => __("Role updated.", 'publishpress'),
                    'role-missing' => __("Role doesn't exist.", 'publishpress'),
                    'role-deleted' => __("Role deleted.", 'publishpress'),
                ],
            ];

            $this->module = PublishPress()->register_module($this->module_name, $args);

            parent::__construct();
        }

        protected function configureTwig()
        {
            $function = new TwigFunction(
                'settings_fields', function () {
                return settings_fields($this->module->options_group_name);
            }
            );
            $this->twig->addFunction($function);

            $function = new TwigFunction(
                'nonce_field', function ($context) {
                return wp_nonce_field($context);
            }
            );
            $this->twig->addFunction($function);

            $function = new TwigFunction(
                'submit_button', function () {
                return submit_button();
            }
            );
            $this->twig->addFunction($function);

            $function = new TwigFunction(
                'do_settings_sections', function ($section) {
                return do_settings_sections($section);
            }
            );
            $this->twig->addFunction($function);

            $function = new TwigFunction(
                'display_role_list_table', function () {
                $wp_list_table = new PP_Roles_List_Table($this->twig);
                $wp_list_table->prepare_items();

                ob_start();
                $wp_list_table->display();
                $output = ob_get_clean();

                return $output;
            }
            );
            $this->twig->addFunction($function);
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            add_action('admin_init', [$this, 'register_settings']);

            $requestMethod = Util::getRequestMethod();

            if ($requestMethod === 'POST') {
                // Handle any adding, editing or saving
                add_action('admin_init', [$this, 'handle_add_role']);
                add_action('admin_init', [$this, 'handle_edit_role']);
            }

            if ($requestMethod === 'GET') {
                add_action('admin_init', [$this, 'handle_delete_role']);
            }

            $this->cap_manage_roles = apply_filters('pp_cap_manage_roles', $this->cap_manage_roles);

            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

            // Menu
            add_filter('publishpress_admin_menu_slug', [$this, 'filter_admin_menu_slug'], 40);
            add_action('publishpress_admin_menu_page', [$this, 'action_admin_menu_page'], 40);
            add_action('publishpress_admin_submenu', [$this, 'action_admin_submenu'], 40);

            add_action('profile_update', [$this, 'action_profile_update'], 10, 2);

            if (is_multisite()) {
                add_action('add_user_to_blog', [$this, 'action_profile_update'], 9);
            } else {
            	add_action('user_register', [$this, 'action_profile_update'], 9);
            }

            if ($this->wasPublishPressInstalledBefore()) {
                add_action('publishpress_migrate_groups_to_role', [$this, 'migrateUserGroupsToRoles']);
            }
        }

        /**
         * Detects if PublishPress was previously installed.
         */
        public function wasPublishPressInstalledBefore()
        {
            $version = get_option('publishpress_version', false);

            $installed = !empty($version);

            if (PUBLISHPRESS_VERSION === $version) {
                $installed = false;
            }

            return $installed;
        }

        /**
         * Install
         *
         * @since 0.7
         */
        public function install()
        {
            $this->addCapabilitiesToAdmin();

            if ($this->wasPublishPressInstalledBefore()) {
                $this->scheduleUserGroupMigration();
            }
        }

        /*[$*
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previous_version)
        {
            if (version_compare($previous_version, '1.11.3', '<')) {
                $this->addCapabilitiesToAdmin();
                $this->scheduleUserGroupMigration();
            }
        }

        /**
         * @return bool
         */
        protected function isUserGroupMigrationScheduled()
        {
            $scheduled = false;

            $crons = get_option('cron');

            if (!empty($crons)) {
                foreach ($crons as $time => $list) {
                    if (is_array($list) && array_key_exists('publishpress_migrate_groups_to_role', $list)) {
                        $scheduled = true;
                    }
                }
            }

            return $scheduled;
        }

        public function scheduleUserGroupMigration()
        {
            // Check if the cron do not exists before schedule another one
            if (!$this->isUserGroupMigrationScheduled()) {
                // Schedule for after 15 seconds.
                wp_schedule_single_event(
                    time() + 15,
                    'publishpress_migrate_groups_to_role',
                    []
                );

                add_action('admin_notices', [$this, 'showAdminNoticeMigrationScheduled']);
            }
        }

        /**
         * Migrate the groups into roles. Expected to run as cron job.
         */
        public function migrateUserGroupsToRoles()
        {
            // Check if we have the flag saying the data is already migrated.
            $flag = get_option('publishpress_migrated_usergroups_to_role', 0);
            if ($flag != 0) {
                return;
            }

            $this->convertLegacyUserGroupsToRoles();
            $this->convertLegacyPostFollowingUser();
            $this->convertLegacyPostFollowingUserGroupsToRoles();
            $this->cleanupUserGroups();

            add_action('admin_notices', [$this, 'showAdminNoticeMigrationFinished']);

            // Set a flag to say we already migrated the data.
            update_option('publishpress_migrated_usergroups_to_role', 1);
        }

        /**
         * Show admin notice for saying the migration is scheduled.
         */
        public function showAdminNoticeMigrationScheduled()
        {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php _e(
                        'PublishPress detected legacy data which needs to be migrated. This task should run in the background in the next few minutes.',
                        'publishpress'
                    ); ?></p>
            </div>
            <?php
        }

        /**
         * Show admin notice for saying the migration has finished.
         */
        public function showAdminNoticeMigrationFinished()
        {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('PublishPress finished migrating the legacy data.', 'publishpress'); ?></p>
            </div>
            <?php
        }

        /**
         * Add the capabilities to the admin for managing the roles.
         */
        protected function addCapabilitiesToAdmin()
        {
            // Give permissions for the Admin to edit roles
            $admin_role = get_role('administrator');
            $admin_role->add_cap($this->cap_manage_roles);
        }

        /**
         * Convert the user groups to roles.
         */
        protected function convertLegacyUserGroupsToRoles()
        {
            // Try to get the user groups.
            $userGroups = $this->getLegacyUserGroups();

            if (!empty($userGroups)) {
                foreach ($userGroups as $userGroup) {
                    $this->convertUserGroupToRole($userGroup);
                }
            }
        }

        /**
         * Convert following_user taxonomy to pp_notify_user.
         */
        protected function convertLegacyPostFollowingUser()
        {
            global $wpdb;

            $wpdb->update($wpdb->term_taxonomy, ['taxonomy' => 'pp_notify_user'], ['taxonomy' => 'following_users']);
        }

        /**
         * Convert user groups to roles into posts' selected for notification.
         */
        protected function convertLegacyPostFollowingUserGroupsToRoles()
        {
            global $wpdb;

            // Create the terms for each role
            $userGroups = $this->getLegacyUserGroups();
            if (!empty($userGroups)) {
                foreach ($userGroups as $userGroup) {
                    $term = term_exists($userGroup->slug, 'pp_notify_role');

                    if (empty($term)) {
                        // Add a term and taxonomy for the role (usergroup->slug).
                        $term = wp_insert_term(
                            $userGroup->slug,
                            'pp_notify_role',
                            [
                                'slug' => $userGroup->slug,
                            ]
                        );
                    }

                    if (is_wp_error($term)) {
                        error_log('PublishPress error loading term for migrating: ' . maybe_serialize($term));
                    }
                }
            }
            // We do a custom query to save memory.
            $query   = "SELECT `ID` FROM {$wpdb->prefix}posts";
            $postIds = $wpdb->get_results($query);

            if (!empty($postIds)) {
                foreach ($postIds as $postId) {
                    $postId = $postId->ID;

                    // Get the current post terms, to avoid duplicate
                    $postTerms    = get_the_terms($postId, 'pp_notify_role');
                    $postTermsIds = [];

                    if (!empty($postTerms)) {
                        foreach ($postTerms as $term) {
                            if (is_object($term)) {
                                $termId = $term->term_id;
                            } elseif (is_array($term)) {
                                $termId = $term['term_id'];
                            }

                            $postTermsIds[] = $termId;
                        }
                    }

                    // debug
                    if (!in_array($postId, [138, 140])) {
                        continue;
                    }

                    // Get the following user groups
                    $terms = get_the_terms($postId, 'pp_usergroup');

                    if (!empty($terms)) {
                        foreach ($terms as $oldTerm) {
                            // Get the new term
                            $newTerm = get_term_by('slug', $oldTerm->slug, 'pp_notify_role');

                            if (is_object($newTerm)) {
                                // Append to the post
                                $result = wp_set_post_terms(
                                    $postId,
                                    $newTerm->slug,
                                    'pp_notify_role',
                                    true
                                );

                                if (!empty($result)) {
                                    // Remove old terms
                                    wp_remove_object_terms(
                                        $postId,
                                        $oldTerm->slug,
                                        'pp_usergroup'
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Convert the given user group in to a role. Capabilities should be ported by
         * the Permissions add-on.
         *
         * @param WP_Term $userGroup
         */
        protected function convertUserGroupToRole($userGroup)
        {
            if (!($userGroup instanceof WP_Term)) {
                return;
            }

            // Check if the role doesn't exist.
            $role = $this->getRole($userGroup->slug);

            if (empty($role)) {
                // The role doesn't exist. Let's add it.
                $role = $this->addRole($userGroup->slug, $userGroup->name);

                // Check if we need to add users from the user group to the role.
                if (!empty($userGroup->user_ids)) {
                    foreach ($userGroup->user_ids as $userId) {
                        $user = $this->getUserById($userId);

                        if (!empty($user)) {
                            $user->add_role($role->name);
                        }
                    }
                }
            }
        }

        /**
         * Get the role.
         *
         * @param string $name
         *
         * @return WP_Role;
         */
        protected function getRole($name)
        {
            return get_role($name);
        }

        /**
         * Add a role.
         *
         * @param string $name
         * @param string $displayName
         * @param array $capabilities
         *
         * @return WP_Role
         */
        protected function addRole($name, $displayName, $capabilities = [])
        {
            return add_role($name, $displayName, $capabilities);
        }

        /**
         * Get a user by id.
         *
         * @param int $id
         *
         * @return WP_User
         */
        protected function getUserById($id)
        {
            return get_user_by('ID', $id);
        }

        /**
         * Remove the given user group.
         *
         * @param int $userGroupId
         */
        protected function removeUserGroup($userGroupId)
        {
            wp_delete_term($userGroupId, 'pp_usergroup');
        }

        /**
         * Get legacy User Groups.
         *
         * @return array|false
         */
        public function getLegacyUserGroups()
        {
            // Make sure the taxonomy is registered, so we can query it.
            register_taxonomy(
                'pp_usergroup',
                'post',
                [
                    'label'        => 'User Group',
                    'public'       => false,
                    'rewrite'      => false,
                    'hierarchical' => false,
                ]
            );

            // Query the user groups
            $userGroupTerms = get_terms(
                [
                    'taxonomy'   => 'pp_usergroup',
                    'hide_empty' => false,
                ]
            );

            if (empty($userGroupTerms)) {
                return false;
            }

            $userGroups = [];
            foreach ($userGroupTerms as $userGroupTerm) {
                $userGroup = get_term_by('id', $userGroupTerm->term_id, 'pp_usergroup');

                if (!$userGroup || is_wp_error($userGroup)) {
                    return $userGroup;
                }

                // We're using an encoded description field to store extra values
                // Declare $user_ids ahead of time just in case it's empty
                $userGroup->user_ids = [];
                $decodedDescription  = maybe_unserialize(base64_decode($userGroup->description));

                if (is_array($decodedDescription)) {
                    foreach ($decodedDescription as $key => $value) {
                        $userGroup->$key = $value;
                    }
                }

                $userGroups[] = $userGroup;
            }

            return $userGroups;
        }

        /**
         * Cleanup user groups.
         */
        protected function cleanupUserGroups()
        {
            // Try to get thea user groups.
            $userGroups = $this->getLegacyUserGroups();

            if (!empty($userGroups)) {
                foreach ($userGroups as $userGroup) {
                    $this->removeUserGroup($userGroup->term_id);
                }
            }

            return false;
        }

        /**
         * Enqueue necessary admin styles, but only on the proper pages
         */
        public function enqueue_admin_scripts()
        {
            if (isset($_GET['page']) && $_GET['page'] === 'pp-manage-roles') {
                // Settings page
                wp_enqueue_script(
                    'publishpress-chosen-js',
                    PUBLISHPRESS_URL . 'common/libs/chosen-v1.8.3/chosen.jquery.js',
                    ['jquery'],
                    PUBLISHPRESS_VERSION
                );
                wp_enqueue_script(
                    'publishpress-roles-js',
                    $this->module_url . 'assets/js/admin.js',
                    ['jquery', 'publishpress-chosen-js'],
                    PUBLISHPRESS_VERSION
                );

                wp_enqueue_style(
                    'publishpress-chosen-css',
                    PUBLISHPRESS_URL . 'common/libs/chosen-v1.8.3/chosen.css',
                    false,
                    PUBLISHPRESS_VERSION
                );
                wp_enqueue_style(
                    'publishpress-roles-css',
                    $this->module_url . 'assets/css/admin.css',
                    ['publishpress-chosen-css'],
                    PUBLISHPRESS_VERSION
                );
            } else {
                if (function_exists('get_current_screen')) {
                    $screen = get_current_screen();

                    if ('user-edit' === $screen->base || ('user' === $screen->base && 'add' === $screen->action)) {
                        // Check if we are on the user's profile page
                        wp_enqueue_script(
                            'publishpress-chosen-js',
                            PUBLISHPRESS_URL . 'common/libs/chosen-v1.8.3/chosen.jquery.js',
                            ['jquery'],
                            PUBLISHPRESS_VERSION
                        );
                        wp_enqueue_script(
                            'publishpress-roles-profile-js',
                            $this->module_url . 'assets/js/profile.js',
                            ['jquery', 'publishpress-chosen-js'],
                            PUBLISHPRESS_VERSION
                        );

                        wp_enqueue_style(
                            'publishpress-chosen-css',
                            PUBLISHPRESS_URL . 'common/libs/chosen-v1.8.3/chosen.css',
                            false,
                            PUBLISHPRESS_VERSION
                        );
                        wp_enqueue_style(
                            'publishpress-roles-profile-css',
                            $this->module_url . 'assets/css/profile.css',
                            ['publishpress-chosen-css'],
                            PUBLISHPRESS_VERSION
                        );

                        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

                        wp_localize_script(
                            'publishpress-roles-profile-js',
                            'publishpressProfileData',
                            [
                                'selected_roles' => $this->getUsersRoles($userId),
                            ]
                        );
                    }
                }
            }
        }

        /**
         * Action executed when the user profile is updated.
         *
         * @param $userId
         * @param $oldUserData
         */
        public function action_profile_update($userId, $oldUserData = [])
        {
            // Check if we need to update the user's roles, allowing to set multiple roles.
            if (isset($_POST['pp_roles']) && current_user_can('promote_users')) {
                // Remove the user's roles
                $user = get_user_by('ID', $userId);

                $newRoles     = $_POST['pp_roles'];
                $currentRoles = $user->roles;

                if (empty($newRoles) || !is_array($newRoles)) {
                    return;
                }

                // Remove unselected roles
                foreach ($currentRoles as $role) {
                    // Check if it is a bbPress rule. If so, don't remove it.
                    $isBBPressRole = preg_match('/^bbp_/', $role);

                    if (!in_array($role, $newRoles) && !$isBBPressRole) {
                        $user->remove_role($role);
                    }
                }

                // Add new roles
                foreach ($newRoles as $role) {
                    if (!in_array($role, $currentRoles)) {
                        $user->add_role($role);
                    }
                }
            }
        }

        /**
         * Returns a list of roles with name and display name to populate a select field.
         *
         * @param int $userId
         *
         * @return array
         */
        protected function getUsersRoles($userId)
        {
            if (empty($userId)) {
                return [];
            }

            $user = get_user_by('id', $userId);

            if (empty($user)) {
                return [];
            }

            return $user->roles;
        }

        /**
         * Generate a link to one of the routes actions
         *
         * @param string $action Action we want the user to take
         * @param array $args Any query args to add to the URL
         *
         * @return string $link Direct link to delete a route
         * @since 0.7
         *
         */
        public function getLink($args = [])
        {
            if (!isset($args['action'])) {
                $args['action'] = '';
            }

            if (!isset($args['page'])) {
                $args['page'] = static::PAGE_SLUG;
            }

            // Add other things we may need depending on the action
            switch ($args['action']) {
                case 'delete-role':
                    $args['nonce'] = wp_create_nonce('manage-role');
                    break;
                default:
                    break;
            }

            return add_query_arg($args, get_admin_url(null, 'admin.php'));
        }

        /**
         * Filters the menu slug.
         *
         * @param $menu_slug
         *
         * @return string
         */
        public function filter_admin_menu_slug($menu_slug)
        {
            if (empty($menu_slug) && $this->module_enabled('roles')) {
                $menu_slug = self::MENU_SLUG;
            }

            return $menu_slug;
        }

        /**
         * Creates the admin menu if there is no menu set.
         */
        public function action_admin_menu_page()
        {
            $publishpress = $this->get_service('publishpress');

            if ($publishpress->get_menu_slug() !== self::MENU_SLUG) {
                return;
            }

            $publishpress->add_menu_page(
                esc_html__('Roles', 'publishpress'),
                apply_filters('pp_manage_roles_cap', 'pp_manage_roles'),
                self::MENU_SLUG,
                [$this, 'render_admin_page']
            );
        }

        /**
         * Add necessary things to the admin menu
         */
        public function action_admin_submenu()
        {
            $publishpress = $this->get_service('publishpress');

            // Main Menu
            add_submenu_page(
                $publishpress->get_menu_slug(),
                esc_html__('Roles', 'publishpress'),
                esc_html__('Roles', 'publishpress'),
                apply_filters('pp_manage_roles_cap', 'pp_manage_roles'),
                self::MENU_SLUG,
                [$this, 'render_admin_page'],
                50
            );
        }

        /**
         * @throws Exception
         */
        public function render_admin_page()
        {
            global $publishpress;


            $publishpress->settings->print_default_header($publishpress->modules->roles);

            echo '<div class="wrap">';

            $action = isset($_GET['action']) && !empty($_GET['action']) ? $_GET['action'] : 'add-role';

            $role = (object)[
                'name'         => isset($_POST['name']) ? $_POST['name'] : '',
                'display_name' => isset($_POST['display_name']) ? $_POST['display_name'] : '',
            ];

            $link_args = [];

            if (isset($_GET['role-id']) && $action == 'edit-role') {
                $role_id = preg_replace('[a-z\-]', '', $_GET['role-id']);

                if (!empty($role_id)) {
                    $role_obj = get_role($role_id);

                    if (!empty($role_obj)) {
                        $editable_roles = get_editable_roles();

                        $role->name = $role_obj->name;

                        // Look for the display name of the role
                        foreach ($editable_roles as $editable_role => $editable_role_data) {
                            if ($editable_role === $role->name) {
                                $role->display_name = $editable_role_data['name'];
                            }
                        }
                    }

                    $link_args = [
                        'role-id' => $role->name,
                    ];
                }
            }

            $this->configureTwig();

            echo $this->twig->render(
                'settings-tab-roles.twig.html',
                [
                    'form_action'        => $this->getLink($link_args),
                    'action'             => $action,
                    'options_group_name' => $this->module->options_group_name,
                    'module_name'        => $this->module->slug,
                    'labels'             => [
                        'add_new'                  => __('Add New Role', 'publishpress'),
                        'edit'                     => __('Edit Role', 'publishpress'),
                        'display_name'             => __("Display name", 'publishpress'),
                        'display_name_description' => __("This is the name that users will see.", 'publishpress'),
                        'name'                     => __('Developer Name (ID)', 'publishpress'),
                        'name_description'         => __(
                            'This is the name that developers can use to interact with this role. Only use A-Z letters and the "-" sign.',
                            'publishpress'
                        ),
                        'users'                    => __("Users", 'publishpress'),
                        'users_description'        => __("Add users to this role.", 'publishpress'),
                    ],
                    'role'               => $role,
                    'nonce'              => wp_nonce_field('manage-role'),
                    'errors'             => isset($_REQUEST['form-errors']) ? $_REQUEST['form-errors'] : [],
                ]
            );

            echo '</div>';

            $publishpress->settings->print_default_footer($publishpress->modules->roles);
        }

        /**
         * Handles a POST request to add a new Role.
         */
        public function handle_add_role()
        {
            if (!isset($_POST['submit'], $_POST['form-action'], $_GET['page'])
                || ($_GET['page'] != static::PAGE_SLUG) || $_POST['form-action'] != 'add-role') {
                return;
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'manage-role')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can($this->cap_manage_roles)) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            // Sanitize all of the user-entered values
            $name         = strtolower(sanitize_title(strip_tags(trim($_POST['name']))));
            $display_name = stripslashes(strip_tags(trim($_POST['display_name'])));
            $users        = $_POST['users'];

            $_REQUEST['form-errors'] = [];

            /**
             * Form validation for adding new Role
             *
             * Details
             * - 'name' is a required field, but can't match an existing name or slug. Needs to be 40 characters or less
             * - 'display_name' is a required field
             */
            // Field is required
            if (empty($name)) {
                $_REQUEST['form-errors']['name'] = __('Please enter a name for the role.', 'publishpress');
            }

            // Check to ensure a role with the same name doesn't exist
            $role = get_role($name);
            if (!empty($role)) {
                $_REQUEST['form-errors']['name'] = __('Name already in use. Please choose another.', 'publishpress');
            }

            if (strlen($name) > 40) {
                $_REQUEST['form-errors']['name'] = __(
                    'Role name cannot exceed 40 characters. Please try a shorter name.',
                    'publishpress'
                );
            }

            if (empty($display_name)) {
                $_REQUEST['form-errors']['display_name'] = __(
                    'Please enter a display name for the role.',
                    'publishpress'
                );
            }

            if (strlen($display_name) > 40) {
                $_REQUEST['form-errors']['display_name'] = __(
                    'Role\'s display name cannot exceed 40 characters. Please try a shorter name.',
                    'publishpress'
                );
            }

            // Kick out if there are any errors
            if (count($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';

                return;
            }

            // Try to add the role
            $role = $this->addRole($name, $display_name, []);
            if (is_wp_error($role)) {
                wp_die(__('Error adding role.', 'publishpress'));
            }

            $args         = [
                'action'  => 'edit-role',
                'role-id' => $role->name,
                'message' => 'role-added',
            ];
            $redirect_url = $this->getLink($args);
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handles a POST request to edit a Role.
         */
        public function handle_edit_role()
        {
            global $wpdb;

            if (!isset($_POST['submit'], $_POST['form-action'], $_GET['page'])
                || ($_GET['page'] != static::PAGE_SLUG) || $_POST['form-action'] != 'edit-role') {
                return;
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'manage-role')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can($this->cap_manage_roles)) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            // Sanitize all of the user-entered values
            $name         = sanitize_title(strip_tags(trim($_POST['role-id'])));
            $display_name = stripslashes(strip_tags(trim($_POST['display_name'])));
            $users        = isset($_POST['users']) ? $_POST['users'] : [];

            $_REQUEST['form-errors'] = [];

            /**
             * Form validation for adding new Role
             *
             * Details
             * - 'name' is a required field, but can't match an existing name or slug. Needs to be 40 characters or less
             * - 'display_name' is a required field
             */
            // Field is required
            if (empty($display_name)) {
                $_REQUEST['form-errors']['name'] = __('Please enter a display name for the role.', 'publishpress');
            }

            if (strlen($display_name) > 40) {
                $_REQUEST['form-errors']['name'] = __(
                    'Role\'s display name cannot exceed 40 characters. Please try a shorter name.',
                    'publishpress'
                );
            }

            // Kick out if there are any errors
            if (count($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';

                return;
            }

            // Get all the roles and edit the current role. Saving all the roles again in the options table.
            $roles = get_option($wpdb->prefix . 'user_roles');
            if (is_wp_error($roles) || empty($roles)) {
                wp_die(__('Error loading role.', 'publishpress'));
            }

            if (!isset($roles[$name])) {
                wp_die(__('Role not found. Can\'t edit.', 'publishpress'));
            }

            $roles[$name]['name'] = $display_name;

            update_option($wpdb->prefix . 'user_roles', $roles);

            $args         = [
                'action'  => 'edit-role',
                'role-id' => $name,
                'message' => 'role-updated',
            ];
            $redirect_url = $this->getLink($args);
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handles a POST request to delete a Role.
         */
        public function handle_delete_role()
        {
            if (!isset($_GET['action'], $_GET['page'])
                || ($_GET['page'] != static::PAGE_SLUG) || $_GET['action'] != 'delete-role') {
                return;
            }

            if (!wp_verify_nonce($_GET['nonce'], 'manage-role')) {
                wp_die($this->module->messages['nonce-failed']);
            }

            if (!current_user_can($this->cap_manage_roles)) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            // Sanitize all of the user-entered values
            $name = sanitize_title(strip_tags(trim($_GET['role-id'])));

            // Avoid deleting administrator
            if ('administrator' === $name) {
                wp_die(__('You can\'t delete the administrator role.', 'publishpress'));
            }

            // Check if the role exists
            $role = get_role($name);

            if (empty($role)) {
                wp_die(__('Role not found. Can\'t edit.', 'publishpress'));
            }

            remove_role($name);

            $args         = [
                'action'  => 'add-role',
                'message' => 'role-deleted',
            ];
            $redirect_url = $this->getLink($args);
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         */
        public function register_settings()
        {
        }
    }
}
