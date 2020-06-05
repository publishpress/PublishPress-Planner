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

use PublishPress\Legacy\Auto_loader;
use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Workflow\Workflow;
use PublishPress\NotificationsLog\CliHandler;
use PublishPress\NotificationsLog\NotificationsLogHandler;
use PublishPress\NotificationsLog\NotificationsLogModel;
use PublishPress\NotificationsLog\NotificationsLogTable;

if (!class_exists('PP_Notifications_Log')) {
    /**
     * class PP_Notifications_Log
     */
    class PP_Notifications_Log extends PP_Module
    {
        use Dependency_Injector;

        const SETTINGS_SLUG = 'pp-notifications-log';

        public $module_name = 'notifications-log';

        public $module_url = '';

        /**
         * @var string
         */
        const MENU_SLUG = 'pp-notif-log';

        /**
         * The constructor
         */
        public function __construct()
        {
            global $publishpress;

            $this->twigPath = dirname(dirname(dirname(__FILE__))) . '/twig';

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Notifications Log', 'publishpress'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'notifications-log',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'general_options'      => false,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_notifications_queue_default_options',
                $args['default_options']
            );
            $args['default_options'] = apply_filters(
                'pp_notifications_queue_default_options',
                $args['default_options']
            );
            $this->module            = $publishpress->register_module(
                PublishPress\Legacy\Util::sanitize_module_name($this->module_name),
                $args
            );

            Auto_loader::register('\\PublishPress\\NotificationsLog\\', __DIR__ . '/library');

            parent::__construct();
        }

        protected function configure_twig()
        {
            if ($this->twig_configured) {
                return;
            }

            $function = new Twig_SimpleFunction(
                'settings_fields', function () {
                return settings_fields($this->module->options_group_name);
            }
            );
            $this->twig->addFunction($function);

            $function = new Twig_SimpleFunction(
                'nonce_field', function ($context) {
                return wp_nonce_field($context);
            }
            );
            $this->twig->addFunction($function);

            $function = new Twig_SimpleFunction(
                'submit_button', function () {
                return submit_button();
            }
            );
            $this->twig->addFunction($function);

            $function = new Twig_SimpleFunction(
                '__', function ($id) {
                return __($id, 'publishpress');
            }
            );
            $this->twig->addFunction($function);

            $function = new Twig_SimpleFunction(
                'do_settings_sections', function ($section) {
                return do_settings_sections($section);
            }
            );
            $this->twig->addFunction($function);

            $this->twig_configured = true;
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
            add_action('publishpress_notif_post_metabox', [$this, 'postNotificationMetaBox']);
            add_action('publishpress_notif_notification_sending', [$this, 'actionNotificationSending'], 10, 6);
            add_filter('publishpress_notifications_queue_data', [$this, 'registerAsyncNotificationLogAndAddLogId']);
            add_action('publishpress_admin_submenu', [$this, 'action_admin_submenu'], 20);
            add_filter('set-screen-option', [$this, 'tableSetOptions'], 10, 3);
            add_action('wp_ajax_publishpress_search_post', [$this, 'ajaxSearchPost']);
            add_action('wp_ajax_publishpress_search_workflow', [$this, 'ajaxSearchWorkflow']);
            add_action('wp_ajax_publishpress_view_notification', [$this, 'ajaxViewNotification']);

            if (class_exists('WP_Cli')) {
                new CliHandler();
            }
        }

        /**
         * Settings page for notifications
         *
         * @since 1.18.1-beta.1
         */
        public function print_configure_view()
        {
        }

        /**
         * Enqueue necessary admin scripts
         *
         * @since 0.7
         *
         * @uses  wp_enqueue_script()
         */
        public function enqueueAdminScripts()
        {
            if ($this->is_whitelisted_functional_view()) {
                wp_enqueue_script('jquery-ui-dialog');
                wp_enqueue_style('wp-jquery-ui-dialog');

                wp_enqueue_style(
                    'pressshack-admin-css',
                    PUBLISHPRESS_URL . 'common/css/pressshack-admin.css',
                    false,
                    PUBLISHPRESS_VERSION,
                    'screen'
                );

                wp_enqueue_style(
                    'pp-admin-css',
                    PUBLISHPRESS_URL . 'common/css/publishpress-admin.css',
                    false,
                    PUBLISHPRESS_VERSION,
                    'screen'
                );

                wp_enqueue_script(
                    'publishpress-notifications-log',
                    $this->module_url . 'assets/js/admin.js',
                    [
                        'jquery-ui-dialog',
                    ],
                    PUBLISHPRESS_VERSION,
                    true
                );

                wp_localize_script(
                    'publishpress-notifications-log',
                    'ppNotifLog',
                    [
                        'nonce' => wp_create_nonce('notifications-log-admin'),
                        'text'  => [
                            'allPosts'     => __('All Posts', 'publishpress'),
                            'allWorkflows' => __('All Workflows', 'publishpress'),
                            'allActions'   => __('All Actions', 'publishpress'),
                            'allChannels'  => __('All Channels', 'publishpress'),
                            'allStatuses'  => __('All Statuses', 'publishpress'),
                            'dialogTitle'  => __('Notification', 'publishpress'),
                            'loading'      => __('Loading...', 'publishpress'),
                        ],
                    ]
                );

                wp_enqueue_style(
                    'publishpress-notifications-log',
                    $this->module_url . 'assets/css/admin.css',
                    [],
                    PUBLISHPRESS_VERSION
                );

                wp_enqueue_style(
                    'publishpress-select2-css',
                    plugins_url('common/libs/select2/css/select2.min.css', PUBLISHPRESS_FILE_PATH),
                    false,
                    PUBLISHPRESS_VERSION,
                    'all'
                );

                wp_enqueue_script(
                    'publishpress-select2',
                    plugins_url('common/libs/select2/js/select2.full.min.js', PUBLISHPRESS_FILE_PATH),
                    ['jquery'],
                    PUBLISHPRESS_VERSION
                );
            }
        }

        /**
         * Whether or not the current page is a user-facing PublishPress View
         *
         * @param string $module_name (Optional) Module name to check against
         *
         * @since 0.7
         */
        public function is_whitelisted_functional_view($module_name = null)
        {
            global $current_screen;

            return $current_screen->base === 'publishpress_page_pp-notif-log';
        }

        /**
         * @param WP_Post
         */
        public function postNotificationMetaBox($post)
        {
            $logHandler = new NotificationsLogHandler();
            $logCount   = $logHandler->getNotificationLogEntries($post->ID, null, null, true);
            ?>
            <div class="pp_post_notify_queue">
                <h3><?php echo __('Notifications Log', 'publishpress'); ?></h3>

                <?php if ($logCount > 0) : ?>
                    <a href="/wp-admin/admin.php?page=pp-notif-log&orderby=date&order=desc&post_id=<?php echo $post->ID; ?>"
                       class="view_log"><?php printf(
                            _n(
                                '%s notification found.',
                                '%s notifications found.',
                                $logCount,
                                'publishpress'
                            ),
                            $logCount
                        ); ?></a>
                <?php else: ?>
                    <p class="no-workflows"><?php echo __('No notifications found.', 'publishpress'); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }

        public function registerAsyncNotificationLogAndAddLogId($data)
        {
            $logHandler = new NotificationsLogHandler();

            $data['logId'] = $logHandler->registerLog(
                [
                    'event'       => $data['event'],
                    'post_id'     => $data['postId'],
                    'workflow_id' => $data['workflowId'],
                    'user_id'     => $data['userId'],
                    'content'     => null,
                    'old_status'  => isset($data['oldStatus']) ? $data['oldStatus'] : null,
                    'new_status'  => isset($data['newStatus']) ? $data['newStatus'] : null,
                    'comment_id'  => isset($data['commentId']) ? (int)$data['commentId'] : null,
                    'async'       => true,
                    'status'      => 'scheduled',
                    'channel'     => null,
                    'receiver'    => null,
                    'success'     => null,
                    'error'       => null,
                    'event_args'  => $data,
                ]
            );

            return $data;
        }

        /**
         * @param $workflowPost
         * @param $eventArgs
         * @param $channel
         * @param $subject
         * @param $body
         * @param $deliveryResult
         */
        public
        function actionNotificationSending(
            $workflowPost,
            $eventArgs,
            $channel,
            $subject,
            $body,
            $deliveryResult
        ) {
            if (!empty($deliveryResult)) {
                $logHandler = new NotificationsLogHandler();

                $post = $eventArgs['post'];
                foreach ($deliveryResult as $receiver => $result) {
                    $error = '';

                    if (true !== $result) {
                        $error = apply_filters(
                            'publishpress_notif_error_log',
                            $error,
                            $result,
                            $receiver,
                            $subject,
                            $body
                        );
                    }

                    $async = isset($eventArgs['async']) ? (bool)$eventArgs['async'] : false;

                    $logHandler->registerLog(
                        [
                            'post_id'     => $post->ID,
                            'content'     => maybe_serialize(['subject' => $subject, 'body' => $body]),
                            'workflow_id' => $workflowPost->ID,
                            'event'       => $eventArgs['event'],
                            'old_status'  => $eventArgs['old_status'],
                            'new_status'  => $eventArgs['new_status'],
                            'channel'     => $channel,
                            'receiver'    => $receiver,
                            'success'     => $result,
                            'error'       => $error,
                            'async'       => $async,
                        ]
                    );
                }
            }
        }

        /**
         * Add necessary things to the admin menu
         */
        public
        function action_admin_submenu()
        {
            $publishpress = $this->get_service('publishpress');

            // Main Menu
            $hook = add_submenu_page(
                $publishpress->get_menu_slug(),
                esc_html__('Notifications Log', 'publishpress'),
                esc_html__('Notifications Log', 'publishpress'),
                apply_filters('pp_view_notifications_cap', 'read_pp_notif_workflow'),
                self::MENU_SLUG,
                [$this, 'render_admin_page'],
                40
            );

            add_action('load-' . $hook, [$this, 'addScreenOptions']);
        }

        public
        function addScreenOptions()
        {
            $option = 'per_page';
            $args   = [
                'label'   => 'Logs',
                'default' => NotificationsLogTable::POSTS_PER_PAGE,
                'option'  => 'logs_per_page',
            ];
            add_screen_option($option, $args);
        }

        public
        function tableSetOptions(
            $status,
            $option,
            $value
        ) {
            return $value;
        }

        /**
         * Create the content overview view. This calls lots of other methods to do its work. This will
         * output any messages, create the table navigation, then print the columns based on
         * get_num_columns(), which will in turn print the stories themselves.
         */
        public
        function render_admin_page()
        {
            $publishpress = $this->get_service('publishpress');
            $publishpress->settings->print_default_header($publishpress->modules->notifications_log);

            $logTable = new NotificationsLogTable(new NotificationsLogHandler());
            $logTable->views();

            //Fetch, prepare, sort, and filter our data...
            $logTable->prepare_items();

            ?>
            <div class="wrap">
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="log-filter" method="get">
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>

                    <!-- Now we can render the completed list table -->
                    <?php $logTable->display() ?>
                </form>

            </div>
            <?php

            $publishpress->settings->print_default_footer($publishpress->modules->notifications_log);
        }

        public
        function ajaxSearchPost()
        {
            if (!wp_verify_nonce($_GET['nonce'], 'notifications-log-admin')) {
                echo '401';

                die();
            }

            global $wpdb;

            $commentType = NotificationsLogModel::COMMENT_TYPE;
            $search      = isset($_GET['search']) ? $wpdb->esc_like($_GET['search']) : '';
            $search      = '%' . $search . '%';

            $sql = $wpdb->prepare(
                "SELECT DISTINCT c.comment_post_id AS 'ID', p.post_title AS 'post_title'
                FROM {$wpdb->comments} AS c
                LEFT JOIN {$wpdb->posts} AS p ON (c.comment_post_id = p.ID)
                WHERE c.comment_type = '{$commentType}'
                AND (p.post_title LIKE '%s' OR p.ID LIKE '%s')",
                $search,
                $search
            );

            $posts = $wpdb->get_results($sql);


            $output = [
                'results' => [],
            ];

            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $output['results'][] = [
                        'id'   => $post->ID,
                        'text' => $post->post_title,
                    ];
                }
            }

            $output['pagination'] = [
                'more' => false,
            ];

            echo json_encode($output);
            die;
        }

        public
        function ajaxSearchWorkflow()
        {
            if (!wp_verify_nonce($_GET['nonce'], 'notifications-log-admin')) {
                echo '401';

                die();
            }

            global $wpdb;

            $metaKeyWorkflow = NotificationsLogModel::META_NOTIF_WORKFLOW_ID;
            $search          = isset($_GET['search']) ? $wpdb->esc_like($_GET['search']) : '';
            $search          = '%' . $search . '%';

            $sql = $wpdb->prepare(
                "SELECT DISTINCT cm.meta_value AS 'ID', p.post_title AS 'post_title'
                FROM {$wpdb->commentmeta} AS cm
                LEFT JOIN {$wpdb->posts} AS p ON (cm.meta_value = p.ID)
                WHERE cm.meta_key = '{$metaKeyWorkflow}'
                AND (p.post_title LIKE '%s' OR p.ID LIKE '%s')",
                $search,
                $search
            );

            $posts = $wpdb->get_results($sql);


            $output = [
                'results' => [],
            ];

            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $output['results'][] = [
                        'id'   => $post->ID,
                        'text' => $post->post_title,
                    ];
                }
            }

            $output['pagination'] = [
                'more' => false,
            ];

            echo json_encode($output);
            die;
        }

        public function ajaxViewNotification()
        {
            if (!wp_verify_nonce($_REQUEST['nonce'], 'notifications-log-admin')) {
                echo '401';

                die();
            }

            $output = '';

            $id       = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
            $receiver = isset($_REQUEST['receiver']) ? sanitize_text_field($_REQUEST['receiver']) : '';
            $channel  = isset($_REQUEST['channel']) ? sanitize_text_field($_REQUEST['channel']) : '';

            if (empty($receiver)) {
                echo $this->get_error_markup(__('Invalid receiver', 'publishpress'));
                exit();
            }

            if (empty($channel)) {
                echo $this->get_error_markup(__('Invalid channel', 'publishpress'));
                exit();
            }

            if (!empty($id)) {
                $comment = get_comment($id);
                $log     = new NotificationsLogModel($comment);
                ob_start();
                ?>
                <em class="preview-notification">
                <?php if ($log->status === 'scheduled') : ?>
                    <?php
                    $workflow = Workflow::load_by_id($log->workflowId);

                    $workflow->event_args = $log->eventArgs;

                    $content_template = $workflow->get_content();
                    $content          = $workflow->do_shortcodes_in_content($content_template, $receiver, $channel);
                    ?>
                    <div class="subject"><label><?php _e(
                                'Subject:',
                                'publishpress'
                            ); ?></label><?php echo esc_html($content['subject']); ?></div>
                    <div class="content">
                        <label>
                            <?php _e('Content:', 'publishpress'); ?>
                        </label>
                        <?php echo wpautop($content['body']); ?>
                    </div>
                <?php else: ?>
                    <?php if (isset($log->content['subject'])) : ?>
                        <?php echo $log->content['subject']; ?><br>
                    <?php endif; ?>
                    <pre><?php echo $log->content['body']; ?></pre>
                <?php endif; ?>
                <?php if ($log->status === 'scheduled') : ?>
                    <em><?php echo __(
                            'This is a preview of the scheduled message. The content can still change until the notification is sent.',
                            'publishpress'
                        ); ?></em>
                <?php endif; ?>
                </div>
                <?php
                $output = ob_get_clean();
            } else {
                $output = $this->get_error_markup(__('Notification log not found.', 'publishpress'));
            }

            echo $output;
            exit();
        }

        private function get_error_markup($message)
        {
            return '<p><div class="notice notice-error">' . $message . '</div></p>';
        }
    }
}
