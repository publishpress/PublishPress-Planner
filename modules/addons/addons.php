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

use PublishPress\Notifications\Traits\Dependency_Injector;

if ( ! class_exists('PP_Addons')) {
    /**
     * class PP_Addons
     */
    class PP_Addons extends PP_Module
    {
        use Dependency_Injector;

        /**
         * The name of the module
         */
        const NAME = 'addons';

        /**
         * The settings slug
         */
        const SETTINGS_SLUG = 'pp-addons';

        /**
         * @var string
         */
        const MENU_SLUG = 'pp-addons';

        /**
         * Flag for debug
         *
         * @var boolean
         */
        protected $debug = false;

        /**
         * The constructor method
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Add-ons for PublishPress', 'publishpress'),
                'short_description'    => '',
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-admin-settings',
                'slug'                 => static::NAME,
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'configure_page_cb'    => 'print_configure_view',
                'autoload'             => true,
                'options_page'         => false,
            ];

            $this->module = PublishPress()->register_module(static::NAME, $args);
        }

        /**
         * Initialize the rest of the stuff in the class if the module is active
         */
        public function init()
        {
            // Menu
            add_filter('publishpress_admin_menu_slug', [$this, 'filter_admin_menu_slug'], 1000);
            add_action('publishpress_admin_menu_page', [$this, 'action_admin_menu_page'], 1000);
            add_action('publishpress_admin_submenu', [$this, 'action_admin_submenu'], 1000);

            add_filter('allex_addons', [$this, 'filter_allex_addons'], 10, 2);
            add_action('allex_addon_update_license', [$this, 'action_allex_addon_update_license'], 10, 4);
            add_filter('allex_addons_get_license_key', [$this, 'filter_allex_addons_get_license_key'], 10, 2);
            add_filter('allex_addons_get_license_status', [$this, 'filter_allex_addons_get_license_status'], 10, 2);
            add_filter('allex_upgrade_link', [$this, 'filter_allex_upgrade_link'], 10, 2);

            $this->init_allex_addons();
        }

        /**
         * @throws Exception
         */
        protected function init_allex_addons()
        {
            $this->get_service('framework')->get_service('module_addons')->init();
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
            if (empty($menu_slug) && $this->module_enabled('addons')) {
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
                esc_html__('Add-ons', 'publishpress'),
                apply_filters('pp_view_addons_cap', 'manage_options'),
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
                esc_html__('Add-ons', 'publishpress'),
                esc_html__('Add-ons', 'publishpress'),
                apply_filters('pp_view_addons_cap', 'manage_options'),
                self::MENU_SLUG,
                [$this, 'render_admin_page']
            );
        }

        /**
         * @param $plugin_name
         * @param $addon_slug
         * @param $license_key
         * @param $license_status
         */
        public function action_allex_addon_update_license($plugin_name, $addon_slug, $license_key, $license_status)
        {
            /**
             * Duplicate the license key for backward compatibility with add-ons.
             */

            $option_name = $this->get_option_name_from_slug($addon_slug);

            // Get current option
            $options = get_option($option_name);

            if (empty($options)) {
                $options = new stdClass();
            }

            $options->license_key    = $license_key;
            $options->license_status = $license_status;

            update_option($option_name, $options);
        }

        /**
         * @return array
         */
        protected function get_addons_list()
        {
            $addons = [
                'publishpress-content-checklist'     => [
                    'slug'        => 'publishpress-content-checklist',
                    'title'       => __('Content Checklist', 'publishpress'),
                    'description' => __(
                        'Allows PublishPress teams to define tasks that must be complete before content is published.',
                        'publishpress'
                    ),
                    'icon_class'  => 'fa fa-check-circle',
                    'edd_id'      => 6465,
                ],
                'publishpress-slack'                 => [
                    'slug'        => 'publishpress-slack',
                    'title'       => __('Slack support', 'publishpress'),
                    'description' => __(
                        'PublishPress with Slack, so you can get comment and status change notifications directly on Slack.',
                        'publishpress'
                    ),
                    'icon_class'  => 'fab fa-slack',
                    'edd_id'      => 6728,
                ],
                'publishpress-permissions'           => [
                    'slug'        => 'publishpress-permissions',
                    'title'       => __('Permissions', 'publishpress'),
                    'description' => __(
                        'Allows you to control which users can complete certain tasks, such as publishing content.',
                        'publishpress'
                    ),
                    'icon_class'  => 'fa fa-lock',
                    'edd_id'      => 6920,
                ],
                'publishpress-woocommerce-checklist' => [
                    'slug'        => 'publishpress-woocommerce-checklist',
                    'title'       => __('WooCommerce Checklist', 'publishpress'),
                    'description' => __(
                        'This add-on allows WooCommerce teams to define tasks that must be complete before products are published.',
                        'publishpress'
                    ),
                    'icon_class'  => 'fa fa-shopping-cart',
                    'edd_id'      => 7000,
                ],
                'publishpress-multiple-authors'      => [
                    'slug'        => 'publishpress-multiple-authors',
                    'title'       => __('Multiple authors support', 'publishpress'),
                    'description' => __(
                        'Allows you choose multiple authors for a single post. This add-on is ideal for teams who write collaboratively.',
                        'publishpress'
                    ),
                    'icon_class'  => 'fas fa-user-edit',
                    'edd_id'      => 7203,
                ],
                'publishpress-reminders'             => [
                    'slug'        => 'publishpress-reminders',
                    'title'       => __('Reminders', 'publishpress'),
                    'description' => __(
                        'Automatically send notifications before or after content is published. Reminders are very useful for making sure your team meets its deadlines.',
                        'publishpress'
                    ),
                    'icon_class'  => 'fa fa-bell',
                    'edd_id'      => 12556,
                ],
            ];

            return $addons;
        }

        /**
         * @param $addons
         * @param $plugin_name
         *
         * @return array
         */
        public function filter_allex_addons($addons, $plugin_name)
        {
            if ('publishpress' === $plugin_name) {
                $addons = $this->get_addons_list();
            }

            return $addons;
        }

        /**
         * @param $slug
         *
         * @return string
         */
        protected function get_option_name_from_slug($slug)
        {
            $options_map = [
                'publishpress-content-checklist'     => 'publishpress_checklist_options',
                'publishpress-multiple-authors'      => 'publishpress_multiple_authors_options',
                'publishpress-woocommerce-checklist' => 'publishpress_woocommerce_checklist_options',
                'publishpress-slack'                 => 'publishpress_slack_options',
                'publishpress-permissions'           => 'publishpress_permissions_options',
                'publishpress-reminders'             => 'publishpress_reminders_options',
            ];

            if (array_key_exists($slug, $options_map)) {
                return $options_map[$slug];
            }

            return false;
        }

        /**
         * @param $license_key
         * @param $addon_slug
         *
         * @return string
         */
        public function filter_allex_addons_get_license_key($license_key, $addon_slug)
        {
            $option_name = $this->get_option_name_from_slug($addon_slug);

            // Get the option
            $options = get_option($option_name);

            if ( ! empty($options) && is_object($options) && isset($options->license_key)) {
                $license_key = $options->license_key;
            }

            return $license_key;
        }

        /**
         * @param $license_status
         * @param $addon_slug
         *
         * @return string
         */
        public function filter_allex_addons_get_license_status($license_status, $addon_slug)
        {
            $option_name = $this->get_option_name_from_slug($addon_slug);

            // Get the option
            $options = get_option($option_name);

            if ( ! empty($options) && is_object($options) && isset($options->license_status)) {
                $license_status = $options->license_status;
            }

            return $license_status;
        }

        /**
         * @param string $ad_link
         * @param string $plugin_name
         *
         * @return array
         */
        public function filter_allex_upgrade_link($ad_link, $plugin_name)
        {
            if ($plugin_name === 'publishpress') {
                $ad_link = 'https://publishpress.com/welcome-coupon/';
            }

            return $ad_link;
        }

        /**
         * Renders the admin page
         */
        public function render_admin_page()
        {
            global $publishpress;

            $publishpress->settings->print_default_header($publishpress->modules->addons, '');

            do_action('allex_echo_addons_page', 'https://publishpress.com/pricing/', 'publishpress');

            $publishpress->settings->print_default_footer($publishpress->modules->addons);
        }
    }
}
