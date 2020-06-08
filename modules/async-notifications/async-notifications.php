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

use PublishPress\AsyncNotifications\WPCron;
use PublishPress\Legacy\Auto_loader;
use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;
use PublishPress\Notifications\Workflow\Workflow;

if (!class_exists('PP_Async_Notifications')) {
    /**
     * class PP_Async_Notifications. Depends on the Improved Notifications module.
     */
    class PP_Async_Notifications extends PP_Module
    {
        use Dependency_Injector, PublishPress_Module;

        const SETTINGS_SLUG = 'pp-async-notifications-settings';

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
            add_action('publishpress_notifications_running_for_post', [$this, 'schedule_notifications'], 7);
            add_action(WPCron::SEND_NOTIFICATION_HOOK, [$this, 'send_notification'], 10, 8);
            add_filter('debug_information', [$this, 'filterDebugInformation']);
            add_filter('publishpress_notifications_stop_sync_notifications', '__return_true');
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
             * @param int $timeout
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
         * @param array $params
         */
        public function send_notification($params)
        {
            // Check if this is a duplicated notification and skip it.
            // I hope this is a temporary fix. When scheduled, some notifications seems to be triggered multiple times
            // by the same cron task.
            if ($this->is_duplicated_notification(func_get_args())) {
                return;
            }

            // Work the notification
            $workflow             = Workflow::load_by_id((int)$params['workflow_id']);
            $workflow->event_args = $params['event_args'];

            do_action('publishpress_notifications_send_notifications_action', $workflow, true);

            do_action('publishpress_notifications_async_notification_sent', $params);
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
                WPCron::SEND_NOTIFICATION_HOOK,
                'publishpress_cron_notify', // @deprecated
            ];

            if (!empty($cronTasks)) {
                foreach ($cronTasks as $time => $cron) {
                    foreach ($cron as $hook => $dings) {
                        if (!in_array($hook, $expectedHooks)) {
                            continue;
                        }

                        foreach ($dings as $sig => $data) {
                            $formattedDate = date('Y-m-d H:i:s', $time);

                            $scheduledNotifications["$hook-$sig-$time"] = [
                                'label' => $formattedDate,
                                'value' => sprintf(
                                    __('Event: %s, Workflow ID: %s, Post ID: %s, User ID: %s', 'publishpress'),
                                    $data['args']['event'],
                                    $data['args']['workflowId'],
                                    $data['args']['postId'],
                                    $data['args']['userId'],
                                ),
                            ];
                        }
                    }
                }
            }

            $debugInfo['publishpress-scheduled-notifications'] = [
                'label'       => 'PublishPress Scheduled Notifications in the Cron',
                'description' => '',
                'show_count'  => true,
                'fields'      => $scheduledNotifications,
            ];

            return $debugInfo;
        }

        public function schedule_notifications($workflow)
        {
            $queue = $this->get_service('notification_queue');

            $queue->enqueueNotification($workflow->workflow_post->ID, $workflow->event_args);
        }
    }
}
