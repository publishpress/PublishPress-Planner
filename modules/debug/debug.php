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

if (!class_exists('PP_Debug')) {
    /**
     * Class PP_Debug
     */
    class PP_Debug extends PP_Module
    {
        use Dependency_Injector;

        const FILE = 'debug-publishpress.log';

        const PAGE_SLUG = 'publishpress_debug_log';

        const ACTION_DELETE_LOG = 'delete_log';

        protected $path;

        protected $initialized = false;

        protected $messages = [];

        /**
         * Load the PP_Debug class as an PublishPress module
         */
        public function __construct()
        {
            $this->twigPath = __DIR__ . '/twig';

            parent::__construct();

            // Register the module with PublishPress
            $this->module_url = $this->get_module_url(__FILE__);
            $args             = [
                'title'                => __('Debug', 'publishpress'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-bug',
                'slug'                 => 'debug',
                'default_options'      => [
                    'enabled' => 'off',
                ],
                'configure_page_cb'    => 'print_configure_view',
            ];
            $this->module     = PublishPress()->register_module('debug', $args);
        }

        /**
         * Initialize all of the class' functionality if its enabled
         */
        public function init()
        {
            // Register our settings
            add_action('admin_init', [$this, 'register_settings']);

            $uploadDir = wp_get_upload_dir();
            if (is_array($uploadDir) && isset($uploadDir['path'])) {
                $uploadDir = $uploadDir['path'];
            }

            $this->path = $uploadDir . '/' . self::FILE;

            // Admin bar.
            add_action('admin_bar_menu', [$this, 'admin_bar_menu'], 99);

            // Admin menu.
            add_action('admin_menu', [$this, 'admin_menu']);

            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

            add_action('publishpress_debug_write_log', [$this, 'write'], 10, 3);

            $this->initialized = true;
        }

        public function enqueue_admin_scripts()
        {
            if (isset($_GET['page']) && $_GET['page'] === self::PAGE_SLUG) {
                wp_enqueue_style(
                    'publishpress-debug',
                    PUBLISHPRESS_URL . 'modules/debug/assets/css/debug.css',
                    [],
                    PUBLISHPRESS_VERSION,
                    'screen'
                );

                wp_enqueue_script(
                    'publishpress-debug',
                    PUBLISHPRESS_URL . 'modules/debug/assets/js/debug.js',
                    [],
                    PUBLISHPRESS_VERSION
                );
            }
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         *
         */
        public function register_settings()
        {
        }

        /**
         * Validate the field submitted by the user
         *
         * @since 0.7
         */
        public function settings_validate($new_options)
        {
            // Follow whitelist validation for modules
            if (array_key_exists('post_status_widget', $new_options) && $new_options['post_status_widget'] != 'on') {
                $new_options['post_status_widget'] = 'off';
            }

            if (array_key_exists('my_posts_widget', $new_options) && $new_options['my_posts_widget'] != 'on') {
                $new_options['my_posts_widget'] = 'off';
            }

            return $new_options;
        }

        /**
         * Settings page for the dashboard
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            settings_fields($this->module->options_group_name);
            do_settings_sections($this->module->options_group_name);
        }

        /**
         * Write the given message into the log file.
         *
         * @param $message
         * @param $id
         * @param $line
         *
         * @throws Exception
         */
        public function write($message, $id = null, $line = 0)
        {
            if (!$this->get_service('DEBUGGING')) {
                return;
            }

            // Make sure we have a string to write.
            if (!is_string($message)) {
                if (is_bool($message)) {
                    $message = $message ? 'true' : 'false';
                } else {
                    $message = print_r($message, true);
                }
            }

            // Prepend the id, if set.
            if (!empty($id)) {
                if (!empty($line)) {
                    $id .= ':' . $line;
                }

                $message = $id . ' --> ' . $message;
            }

            // Add the timestamp to the message.
            $message = sprintf('[%s] %s', gmdate('Y-m-d H:i:s T O'), $message) . "\n";

            // phpcs:disable WordPress.PHP.DevelopmentFunctions
            error_log($message, 3, $this->path);
            // phpcs:enable
        }

        public function admin_bar_menu()
        {
            global $wp_admin_bar;

            $args = [
                'id'    => 'publishpress_debug',
                'title' => __('PublishPress Debug Log', 'publishpress'),
                'href'  => admin_url('admin.php?page=' . self::PAGE_SLUG),
            ];

            $wp_admin_bar->add_menu($args);
        }

        public function admin_menu()
        {
            // Admin menu.
            add_submenu_page(
                admin_url('admin.php?page=' . self::PAGE_SLUG),
                __('Debug Log'),
                __('Debug Log'),
                'activate_plugins',
                'publishpress_debug_log',
                [$this, 'view_log_page']
            );
        }

        public function view_log_page()
        {
            $this->handle_actions();

            global $wp_version;

            $is_log_found = file_exists($this->path);

            // Get all the plugins and versions
            $plugins     = get_plugins();
            $pluginsData = [];
            foreach ($plugins as $plugin => $data) {
                $pluginsData[$plugin] = (is_plugin_active(
                        $plugin
                    ) ? 'ACTIVATED' : 'deactivated') . ' [' . $data['Version'] . ']';
            }

            // phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
            $debug_data = [
                'php'       => [
                    'version'                   => PHP_VERSION,
                    'os'                        => PHP_OS,
                    'date_default_timezone_get' => date_default_timezone_get(),
                    'date(e)'                   => date('e'),
                    'date(T)'                   => date('T'),
                ],
                'wordpress' => [
                    'version'         => $wp_version,
                    'date_format'     => get_option('date_format'),
                    'time_format'     => get_option('time_format'),
                    'timezone_string' => get_option('timezone_string'),
                    'gmt_offset'      => get_option('gmt_offset'),
                    'plugins'         => $pluginsData,
                ],
            ];
            // phpcs:enable

            $context = [
                'label'         => [
                    'title'             => esc_html__('PublishPress Debug Log', 'publishpress'),
                    'file_info'         => esc_html__('File info', 'publishpress'),
                    'path'              => esc_html__('Path', 'publishpress'),
                    'log_content'       => esc_html__('Log content', 'publishpress'),
                    'size'              => esc_html__('Size', 'publishpress'),
                    'creation_time'     => esc_html__('Created on', 'publishpress'),
                    'modification_time' => esc_html__('Modified on', 'publishpress'),
                    'delete_file'       => esc_html__('Delete file', 'publishpress'),
                    'debug_data'        => esc_html__('Debug data', 'publishpress'),
                    'log_file'          => esc_html__('Log File', 'publishpress'),
                ],
                'message'       => [
                    'log_not_found'       => esc_html__('Log file not found.', 'publishpress'),
                    'contact_support_tip' => esc_html__(
                        'If you see any error or look for information regarding PublishPress, please don\'t hesitate to contact the support team. E-mail us:',
                        'publishpress'
                    ),
                    'click_to_delete'     => esc_html__(
                        'Click to delete the log file. Be careful, this operation can not be undone. ',
                        'publishpress'
                    ),
                ],
                'contact_email' => 'help@publishpress.com',
                'link_delete'   => admin_url(
                    sprintf(
                        'admin.php?page=%s&action=%s&_wpnonce=%s',
                        self::PAGE_SLUG,
                        self::ACTION_DELETE_LOG,
                        wp_create_nonce(self::ACTION_DELETE_LOG)
                    )
                ),
                'is_log_found'  => $is_log_found,
                'file'          => [
                    'path'              => $this->path,
                    'size'              => $is_log_found ? round(filesize($this->path) / 1024, 2) : 0,
                    'modification_time' => $is_log_found ? gmdate('Y-m-d H:i:s T O', filemtime($this->path)) : '',
                    'content'           => $is_log_found ? file_get_contents($this->path) : '',
                ],
                'debug_data'    => print_r($debug_data, true),
                'messages'      => $this->messages,
            ];

            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->twig->render('view_log.twig', $context);
            // phpcs:enable
        }

        protected function handle_actions()
        {
            // Are we on the correct page?
            if (!array_key_exists('page', $_GET) || $_GET['page'] !== self::PAGE_SLUG) {
                return;
            }

            // Do we have an action?
            if (!array_key_exists('action', $_GET) || empty($_GET['action'])) {
                return;
            }

            $action = preg_replace('/[^a-z0-9_\-]/i', '', sanitize_text_field($_GET['action']));

            // Do we have a nonce?
            if (!array_key_exists('_wpnonce', $_GET) || empty($_GET['_wpnonce'])) {
                $this->messages[] = __('Action nonce not found.', 'publishpress');

                return;
            }

            // Check the nonce.
            if (!wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), $action)) {
                $this->messages[] = __('Invalid action nonce.', 'publishpress');

                return;
            }

            if ($action === self::ACTION_DELETE_LOG) {
                if (file_exists($this->path)) {
                    // phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
                    unlink($this->path);
                    // phpcs:enable
                }
            }

            wp_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
            exit;
        }
    }
}
