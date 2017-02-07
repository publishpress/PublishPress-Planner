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

/**
 * class PP_Efmigration
 */

if (!class_exists('PP_Efmigration')) {
    class PP_Efmigration extends PP_Module
    {
        const DISSMISS_MIGRATION_OPTION = 'publishpress_dismiss_migration';
        const EDITFLOW_MIGRATION_URL_FLAG = 'publishpress_import_editflow';
        const PAGE_SLUG = 'pp-efmigration';

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
            $args = array(
                'title'             => __('Edit Flow Migration', 'publishpress'),
                'short_description' => __('Migrate data from Edit Flow into PublishPress', 'publishpress'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-groups',
                'slug'              => 'efmigration',
                'settings_slug'     => 'pp-efmigration',
                'default_options'   => array(
                    'enabled'    => 'on'
                ),
                'autoload'          => true
            );
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
            add_action('admin_menu', array($this, 'action_register_page'));
            add_action('admin_notices', array($this, 'action_admin_notice'));
            add_action('admin_init', array($this, 'action_editflow_migrate'));

            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_print_styles', array($this, 'enqueue_admin_styles'));

            add_action('wp_ajax_pp_migrate_ef_data', array($this, 'migrate_data'));
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
         * @uses wp_enqueue_script()
         */
        public function enqueue_admin_scripts()
        {
            // wp_enqueue_script('pp-reactjs', $this->module_url . 'lib/js/react-with-addons.min.js', array(), PUBLISHPRESS_VERSION, true);
            // wp_enqueue_script('pp-reactjs', $this->module_url . 'lib/js/react-with-addons.js', array(), PUBLISHPRESS_VERSION, true);
            // wp_enqueue_script('pp-reactjs', $this->module_url . 'lib/js/react.min.js', array(), PUBLISHPRESS_VERSION, true);
            wp_enqueue_script('pp-reactjs', $this->module_url . 'lib/js/react.js', array(), PUBLISHPRESS_VERSION, true);
            wp_enqueue_script('pp-reactjs-dom', $this->module_url . 'lib/js/react-dom.min.js', array('pp-reactjs'), PUBLISHPRESS_VERSION, true);
            wp_enqueue_script('pp-efmigration', $this->module_url . 'lib/js/efmigration.js', array('pp-reactjs', 'pp-reactjs-dom'), PUBLISHPRESS_VERSION, true);

            wp_localize_script('pp-efmigration', 'objectL10n', array(
                'options'           => esc_html__('Options', 'publishpress'),
                'taxonomy'          => esc_html__('Taxonomy', 'publishpress'),
                'user_meta'         => esc_html__('User Meta-data', 'publishpress'),
                'success_msg'       => esc_html__('Finished', 'publishpress'),
                'header_msg'        => esc_html__('Please, wait while we migrate your legacy data...', 'publishpress'),
                'error'             => esc_html__('Error', 'publishpress'),
                'error_msg_intro'   => esc_html__('If needed, feel free to', 'publishpress'),
                'error_msg_contact' => esc_html__('contact the support team', 'publishpress'),
            ));
        }

        /**
         * Enqueue necessary admin styles, but only on the proper pages
         *
         * @since 0.7
         *
         * @uses wp_enqueue_style()
         */
        public function enqueue_admin_styles()
        {
            wp_enqueue_style('pp-efmigration-css', $this->module_url . 'lib/efmigration.css', false, PUBLISHPRESS_VERSION);
        }

        /**
         * Settings page for notifications
         *
         * @since 0.7
         */
        public function print_view()
        {
            ?>
            <div class="wrap publishpress-admin">
                <h2>
                    <?php _e('PublishPress', 'publishpress'); ?>:&nbsp;
                    <?php _e('Edit Flow Data Migration'); ?>
                </h2>
                <div id="pp-content"></div>
            </div>
            <?php
        }

        public function action_register_page()
        {
            add_submenu_page(
                null,
                'Test1',
                'Test2',
                'manage_options',
                self::PAGE_SLUG,
                array($this, 'print_view')
            );
        }

        /**
         * Check if the Edit Flow plugin is installed, looking for its options.
         * We don't check if it is activate or deactivate because we would like
         * to be able to recover the settings, even if the files aren't there
         * anymore.
         *
         * @return bool
         */
        private function checkEditFlowIsInstalled()
        {
            $editFlowVersion = get_site_option('edit_flow_version', null);

            return !empty($editFlowVersion);
        }

        /**
         * Show a notice for admins giving the option to migrate data from EditFlow
         */
        public function action_admin_notice()
        {
            // Check if EditFlow is installed
            if ($this->checkEditFlowIsInstalled()) {
                $dismissMigration = (bool)get_site_option(self::DISSMISS_MIGRATION_OPTION, 0);
                if (!$dismissMigration && (!isset($_GET['page']) || self::PAGE_SLUG !== $_GET['page'])) {
                    echo '<div class="updated"><p>';
                    printf(
                        __('We have found Edit Flow and its data! Would you like to import the data into PublishPress? | <a href="%1$s">Yes, import the data</a> | <a href="%2$s">Dismiss</a>'),
                        add_query_arg(
                            array(
                                'page'                            => self::PAGE_SLUG,
                                self::EDITFLOW_MIGRATION_URL_FLAG => 1),
                            admin_url()
                        ),
                        add_query_arg(
                            array(self::EDITFLOW_MIGRATION_URL_FLAG => 0)
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
                $dismissMigration = (bool)get_site_option(self::DISSMISS_MIGRATION_OPTION, 0);
                if (!$dismissMigration) {
                    // If user clicks to ignore the notice, and register in the options
                    if (isset($_GET[self::EDITFLOW_MIGRATION_URL_FLAG])) {
                        $migrate = (bool)$_GET[self::EDITFLOW_MIGRATION_URL_FLAG];
                        if (!$migrate) {
                            add_site_option(self::DISSMISS_MIGRATION_OPTION, 1);
                        } else {
                            // Migrate Options
                            // add_site_option(self::DISSMISS_MIGRATION_OPTION, 1);
                        }
                    }
                }
            }
        }

        public function migrate_data()
        {
            $allowedSteps = array('options', 'taxonomy', 'user-meta');
            $result       = (object)array(
                'error' => false,
            );

            // Get and validate the step
            $step = $_POST['step'];
            if (!in_array($step, $allowedSteps)) {
                $result->error = __('Unknown step', 'publishpress');
            }

            echo json_encode($result);
            wp_die();
        }
    }
}
