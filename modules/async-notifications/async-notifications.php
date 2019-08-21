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
use PublishPress\Notifications\Traits\PublishPress_Module;

if ( ! class_exists('PP_Async_Notifications')) {
    /**
     * class PP_Async_Notifications. Depends on the Improved Notifications module.
     */
    class PP_Async_Notifications extends PP_Module
    {
        use Dependency_Injector, PublishPress_Module;

        const SETTINGS_SLUG = 'pp-async-notifications-settings';

        const POST_STATUS_QUEUED = 'queued';

        const POST_STATUS_SENT = 'sent';

        const POST_STATUS_FAILED = 'failed';

        const DEFAULT_DUPLICATED_NOTIFICATION_TIMEOUT = 600;

        public $module_name = 'async-notifications';

        public $module_url;

        /**
         * Instace for the module
         *
         * @var stdClass
         */
        public $module;

        /**
         * Construct the Notifications class
         */
        public function __construct()
        {
            global $publishpress;

            $this->twigPath = dirname(dirname(dirname(__FILE__))) . '/twig';

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Async Notifications', 'publishpress'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'async-notifications',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'publishpress_async_notif_default_options',
                $args['default_options']
            );
            $this->module            = $publishpress->register_module(
                PublishPress\Legacy\Util::sanitize_module_name($this->module_name),
                $args
            );

            Auto_loader::register('\\PublishPress\\AsyncNotifications\\', __DIR__ . '/library');

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         *
         * @throws Exception
         */
        public function init()
        {
            add_action('publishpress_notif_queue', [$this, 'action_notif_queue'], 10, 5);
            add_action('publishpress_cron_notify', [$this, 'action_cron_notify'], 10, 8);
            add_filter('publishpress_notif_workflow_run_action', [$this, 'filter_workflow_run_action'], 10, 3);
            add_filter('debug_information', [$this, 'filterDebugInformation']);
        }

        /**
         * Load default editorial metadata the first time the module is loaded
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
         * @param string                               $action
         * @param PublishPress\Notifications\Workflow\ $workflow
         * @param string                               $channel
         *
         * @return string
         */
        public function filter_workflow_run_action($action, $workflow, $channel)
        {
            // Change the action to send the notification to the queue, instead sending to receiver, directly.
            $action = 'publishpress_notif_queue';

            return $action;
        }

        /**
         * Enqueue the notification inse
         *
         * @param $workflow_post
         * @param $action_args
         * @param $receiver
         * @param $content
         * @param $channel
         *
         * @throws Exception;
         */
        public function action_notif_queue($workflow_post, $action_args, $receiver, $content, $channel)
        {
            $queue = $this->get_service('notification_queue');

            $queue->enqueueNotification($workflow_post, $action_args, $receiver, $content, $channel);
        }

        /**
         * Check if the notification was just sent, to avoid duplicated notifications when
         * multiple requests try to run the same job.
         *
         * @param $args
         *
         * @return bool
         */
        protected function is_duplicated_notification($args)
        {
            $uid = $this->calculateNotificationUID($args);

            $transientName = 'ppnotif_' . $uid;

            // Check if we already have the transient.
            if (get_transient($transientName)) {
                // Yes, duplicated notification.
                return true;
            }

            /**
             * Filters the value of the timeout to ignore duplicated notifications.
             *
             * @param int    $timeout
             * @param string $uid
             *
             * @return int
             */
            $timeout = (int)apply_filters(
                'pp_duplicated_notification_timeout',
                self::DEFAULT_DUPLICATED_NOTIFICATION_TIMEOUT,
                $uid
            );

            // Set the flag and return as non-duplicated.
            set_transient($transientName, 1, $timeout);

            return false;
        }

        /**
         * @param array $args
         *
         * @return string
         */
        private function calculateNotificationUID($args)
        {
            return md5(maybe_serialize($args));
        }

        /**
         * @param $workflowPostId
         * @param $action
         * @param $postId
         * @param $content
         * @param $oldStatus
         * @param $newStatus
         * @param $channel
         * @param $receiver
         */
        public function action_cron_notify(
            $workflowPostId,
            $action,
            $postId,
            $content,
            $oldStatus,
            $newStatus,
            $channel,
            $receiver
        ) {
            // Check if this is a duplicated notification and skip it.
            // I hope this is a temporary fix. When scheduled, some notifications seems to be triggered multiple times
            // by the same cron task.
            if ($this->is_duplicated_notification(func_get_args())) {
                return;
            }

            // Work the notification
            $workflowPost = get_post($workflowPostId);
            $actionArgs   = [
                'action'     => $action,
                'post'       => get_post($postId),
                'new_status' => $newStatus,
                'old_status' => $oldStatus,
            ];
            $receivers    = [$receiver];

            // Decode the content
            $content = base64_decode(maybe_unserialize($content));

            /**
             * Triggers the notification. This can be caught by notification channels.
             *
             * @param WP_Post $workflow_post
             * @param array   $action_args
             * @param array   $receivers
             * @param array   $content
             * @param array   $channel
             */
            do_action(
                'publishpress_notif_send_notification_' . $channel,
                $workflowPost,
                $actionArgs,
                $receivers,
                $content,
                $channel
            );
        }

        /**
         * @param array $debugInfo
         *
         * @return array
         */
        public function filterDebugInformation($debugInfo)
        {
            $scheduledNotifications = [];

            $cronTasks = _get_cron_array();

            $expectedHooks = [
                'publishpress_cron_notify',
            ];

            if ( ! empty($cronTasks)) {
                foreach ( $cronTasks as $time => $cron ) {
                    foreach ( $cron as $hook => $dings ) {
                        if ( ! in_array($hook, $expectedHooks)) {
                            continue;
                        }

                        foreach ( $dings as $sig => $data ) {
                            $formattedDate = date('Y-m-d H:i:s', $time);

                            $event = $data['args'][1];
                            $postId = $data['args'][2];
                            $channel = $data['args'][6];

                            if ($channel === 'email') {

                                $details = $data['args'][7];

                                if (is_numeric($details)) {
                                    $user = get_userdata($details);

                                    $details .= ' - ' . $user->user_email;
                                }

                                $channel .= ' (' . $details . ')';
                            }

                            $scheduledNotifications["$hook-$sig-$time"] = [
                                'label' => $formattedDate,
                                'value' => sprintf(__('Event: %s, Post ID: %s, Channel: %s', 'publishpress'), $event, $postId, $channel),
                            ];
                        }
                    }
                }
            }

            $debugInfo['publishpress-scheduled-notifications'] = [
                'label'       => 'PublishPress Scheduled Notifications',
                'description' => '',
                'show_count'  => true,
                'fields'      => $scheduledNotifications,
            ];

            return $debugInfo;
        }
    }
}
