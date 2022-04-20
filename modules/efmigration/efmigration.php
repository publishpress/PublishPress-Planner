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

/**
 * class PP_Efmigration
 */

if (! class_exists('PP_Efmigration')) {
    class PP_Efmigration extends PP_Module
    {
        const OPTION_PREFIX = 'publishpress_';

        const OPTION_DISMISS_MIGRATION = 'publishpress_dismiss_migration';

        const OPTION_MIGRATED_OPTIONS = 'publishpress_efmigration_migrated_options';

        const OPTION_MIGRATED_USERMETA = 'publishpress_efmigration_migrated_usermeta';

        const EDITFLOW_MIGRATION_URL_FLAG = 'publishpress_import_editflow';

        const PAGE_SLUG = 'pp-efmigration';

        const NONCE_KEY = 'pp-efmigration';

        const PLUGIN_NAMESPACE = 'publishpress';

        public $module;

        /**
         * Register the module with PublishPress but don't do anything else
         *
         * @since 0.7
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the User Groups module with PublishPress
            $args = [
                'title' => __('Edit Flow Migration', self::PLUGIN_NAMESPACE),
                'short_description' => __('Migrate data from Edit Flow into PublishPress', self::PLUGIN_NAMESPACE),
                'module_url' => $this->module_url,
                'icon_class' => 'dashicons dashicons-groups',
                'slug' => 'efmigration',
                'settings_slug' => self::PAGE_SLUG,
                'default_options' => [
                    'enabled' => 'on',
                ],
                'autoload' => true,
            ];
            $this->module = PublishPress()->register_module('efmigration', $args);
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
            if (false === current_user_can('manage_options') || false === is_admin()) {
                return;
            }

            add_action('admin_menu', [$this, 'action_register_page']);
            add_action('admin_notices', [$this, 'action_admin_notice_migration']);
            add_action('admin_init', [$this, 'action_editflow_migrate']);
            add_action('wp_ajax_pp_migrate_ef_data', [$this, 'migrate_data']);
            add_action('wp_ajax_pp_finish_migration', [$this, 'migrate_data_finish']);

            if (isset($_GET['page']) && $_GET['page'] === self::PAGE_SLUG) {
                add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
                add_action('admin_print_styles', [$this, 'enqueue_admin_styles']);
            }

            add_action('admin_init', [$this, 'checkEditFlowIsInstalledAndShowWarning'], 999);
        }

        /**
         * Load the capabilities onto users the first time the module is run
         *
         * @since 0.7
         */
        public function install()
        {
        }

        /**
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previous_version)
        {
        }

        /**
         * Enqueue necessary admin scripts
         *
         * @since 0.7
         *
         * @uses  wp_enqueue_script()
         */
        public function enqueue_admin_scripts()
        {
            global $wp_scripts;

            if ($this->checkEditFlowIsInstalled()) {
                if (! isset($wp_scripts->queue['react'])) {
                    wp_enqueue_script(
                        'react',
                        PUBLISHPRESS_URL . 'common/js/react.min.js',
                        [],
                        PUBLISHPRESS_VERSION,
                        true
                    );
                    wp_enqueue_script(
                        'react-dom',
                        PUBLISHPRESS_URL . 'common/js/react-dom.min.js',
                        ['react'],
                        PUBLISHPRESS_VERSION,
                        true
                    );
                }

                wp_enqueue_script(
                    'pp-efmigration',
                    $this->module_url . 'lib/js/efmigration.js',
                    ['react', 'react-dom'],
                    PUBLISHPRESS_VERSION,
                    true
                );

                $publishPressUrl = add_query_arg(
                    [
                        'page' => 'pp-modules-settings',
                    ],
                    admin_url('/admin.php')
                );

                wp_localize_script(
                    'pp-efmigration',
                    'objectL10n',
                    [
                        'intro_text' => esc_html__(
                            'This migration will import all of your data and settings from Edit Flow.',
                            self::PLUGIN_NAMESPACE
                        ),
                        'migration_warning' => esc_html__(
                            'Heads up! Importing data from EditFlow will overwrite any current data in PublishPress.',
                            self::PLUGIN_NAMESPACE
                        ),
                        'start_migration' => esc_html__('Start', self::PLUGIN_NAMESPACE),
                        'options' => esc_html__(
                            'Plugin and Modules Options',
                            self::PLUGIN_NAMESPACE
                        ),
                        'usermeta' => esc_html__('User Meta-data', self::PLUGIN_NAMESPACE),
                        'success_msg' => esc_html__('Finished!', self::PLUGIN_NAMESPACE),
                        'header_msg' => esc_html__(
                            'Please, wait while we migrate your legacy data...',
                            self::PLUGIN_NAMESPACE
                        ),
                        'error' => esc_html__('Error', self::PLUGIN_NAMESPACE),
                        'error_msg_intro' => esc_html__('If needed, feel free to', self::PLUGIN_NAMESPACE),
                        'error_msg_contact' => esc_html__('contact the support team', self::PLUGIN_NAMESPACE),
                        'back_to_publishpress_label' => esc_html__('Back to PublishPress', self::PLUGIN_NAMESPACE),
                        'back_to_publishpress_url' => $publishPressUrl,
                        'wpnonce' => wp_create_nonce(self::NONCE_KEY),
                    ]
                );
            }
        }

        /**
         * Enqueue necessary admin styles, but only on the proper pages
         *
         * @since 0.7
         *
         * @uses  wp_enqueue_style()
         */
        public function enqueue_admin_styles()
        {
            wp_enqueue_style(
                'pp-efmigration-css',
                $this->module_url . 'lib/efmigration.css',
                false,
                PUBLISHPRESS_VERSION
            );
        }

        /**
         * Settings page for notifications
         *
         * @since 0.7
         */
        public function print_view()
        {
            global $publishpress;

            if (! current_user_can('manage_options')) {
                _e('Access Denied', self::PLUGIN_NAMESPACE);

                return;
            }

            $publishpress->settings->print_default_header($this->module); ?>
            <div class="wrap publishpress-admin">
                <div id="pp-content"></div>
            </div>
            <?php

            $publishpress->settings->print_default_footer($this->module);
        }

        public function action_register_page()
        {
            add_submenu_page(
                null,
                'PublishPress',
                'PublishPress',
                'manage_options',
                self::PAGE_SLUG,
                [$this, 'print_view']
            );
        }

        /**
         * Check if the Edit Flow plugin is installed, looking for its options.
         * We don't check if it is activated or deactivated because we would like
         * to be able to recover the settings, even if the files aren't there
         * anymore.
         *
         * @return bool
         */
        private function checkEditFlowIsInstalled()
        {
            $editFlowVersion = get_site_option('edit_flow_version', null);

            return ! empty($editFlowVersion);
        }

        public function checkEditFlowIsInstalledAndShowWarning()
        {
            if (defined('EDIT_FLOW_VERSION')) {
                add_action('admin_notices', [$this, 'noticeEditFlowIsInstalled']);
            }
        }

        public function noticeEditFlowIsInstalled()
        {
            ?>
            <div class="updated notice">
                <p><?php
                    _e(
                        'Edit Flow should not be used alongside PublishPress. If you want to use it, deactive PublishPress first.',
                        'publishpress'
                    ); ?></p>
            </div>
            <?php
        }

        /**
         * Show a notice for admins giving the option to migrate data from EditFlow
         */
        public function action_admin_notice_migration()
        {
            // Check if EditFlow is installed
            if ($this->checkEditFlowIsInstalled()) {
                $dismissMigration = (bool)get_site_option(self::OPTION_DISMISS_MIGRATION, 0);
                if (! $dismissMigration && (! isset($_GET['page']) || self::PAGE_SLUG !== $_GET['page'])) {
                    echo '<div class="updated"><p>';
                    printf(
                        __(
                            'We have found Edit Flow and its data! Would you like to import the data into PublishPress? <a href="%1$s">Yes, import the data</a> | <a href="%2$s">Dismiss</a>'
                        ),
                        add_query_arg(
                            [
                                'page' => self::PAGE_SLUG,
                                self::EDITFLOW_MIGRATION_URL_FLAG => 1,
                            ],
                            admin_url()
                        ),
                        add_query_arg(
                            [self::EDITFLOW_MIGRATION_URL_FLAG => 0]
                        )
                    );
                    echo '</p></div>';
                }
            }
        }

        /**
         * Registers the user decision
         */
        public function action_editflow_migrate()
        {
            if ($this->checkEditFlowIsInstalled()) {
                $dismissMigration = (bool)get_site_option(self::OPTION_DISMISS_MIGRATION, 0);
                if (! $dismissMigration) {
                    // If user clicks to ignore the notice, and register in the options
                    if (isset($_GET[self::EDITFLOW_MIGRATION_URL_FLAG])) {
                        if (! current_user_can('manage_options')) {
                            $this->accessDenied();
                        }

                        $migrate = (bool)$_GET[self::EDITFLOW_MIGRATION_URL_FLAG];
                        if (! $migrate) {
                            update_site_option(self::OPTION_DISMISS_MIGRATION, 1, true);
                        }
                    }
                }
            }
        }

        public function migrate_data()
        {
            check_ajax_referer(self::NONCE_KEY);

            if (! current_user_can('manage_options')) {
                $this->accessDenied();
            }

            $allowedSteps = ['options', 'usermeta'];
            $result = (object)[
                'error' => false,
                'output' => '',
            ];

            // Get and validate the step
            $step = sanitize_text_field($_POST['step']);
            if (! in_array($step, $allowedSteps)) {
                $result->error = __('Unknown step', self::PLUGIN_NAMESPACE);
            }

            $methodName = 'migrate_data_' . $step;

            if (! method_exists($this, $methodName)) {
                $result->error = __('Undefined migration method', self::PLUGIN_NAMESPACE);
            } else {
                $this->$methodName();
            }

            echo json_encode($result);
            wp_die();
        }

        /**
         * Look for options with the prefix: edit_flow_.
         * Ignores the Edit Flow version register
         */
        protected function migrate_data_options()
        {
            if (! get_site_option(self::OPTION_MIGRATED_OPTIONS, false)) {
                $optionsToMigrate = [
                    'calendar_options',
                    'custom_status_options',
                    'dashboard_options',
                    'editorial_comments_options',
                    'editorial_metadata_options',
                    'notifications_options',
                    'settings_options',
                    'story_budget_options',
                    'user_groups_options',
                ];

                foreach ($optionsToMigrate as $option) {
                    $efOption = get_option('edit_flow_' . $option);

                    // Update the current publishpress settings
                    update_option(self::OPTION_PREFIX . $option, $efOption, true);
                }

                update_site_option(self::OPTION_MIGRATED_OPTIONS, 1, true);
            }
        }

        protected function migrate_data_usermeta()
        {
            global $wpdb;

            if (! get_site_option(self::OPTION_MIGRATED_USERMETA, false)) {
                // Remove PublishPress data
                $data = $wpdb->get_results(
                    "
                    SELECT user_id, meta_key, meta_value
                    FROM {$wpdb->usermeta}
                    WHERE meta_key LIKE \"ef_%\"
                    "
                );

                if (! empty($data)) {
                    foreach ($data as $meta) {
                        $key = preg_replace('/^ef_/', 'pp_', $meta->meta_key);
                        update_user_meta($meta->user_id, $key, $meta->meta_value);
                    }
                }

                update_site_option(self::OPTION_MIGRATED_USERMETA, 1, true);
            }
        }

        public function migrate_data_finish()
        {
            check_ajax_referer(self::NONCE_KEY);

            if (! current_user_can('manage_options')) {
                // todo: Replace with WP_Error and wp_send_json_error
                $this->accessDenied();
            }

            update_site_option(self::OPTION_DISMISS_MIGRATION, 1, true);

            wp_die();
        }

        protected function accessDenied()
        {
            if (! headers_sent()) {
                header('HTTP/1.1 403 ' . __('Access Denied', self::PLUGIN_NAMESPACE));
            } else {
                _e('Access Denied', self::PLUGIN_NAMESPACE);
            }

            wp_die();
        }
    }
}
