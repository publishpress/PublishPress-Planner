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

use PublishPress\Auto_loader;
use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;

if (!class_exists('PP_Async_Notifications'))
{
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
                'short_description'    => __('Async notifications for PublishPress', 'publishpress'),
                'extended_description' => __('Async Notifications for PublishPress', 'publishpress'),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'async-notifications',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('publishpress_async_notif_default_options', $args['default_options']);
            $this->module            = $publishpress->register_module(
                PublishPress\Util::sanitize_module_name($this->module_name),
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
            add_filter('publishpress_notif_workflow_run_action', [$this, 'filter_workflow_run_action'], 10, 3);

            add_action('publishpress_notif_queue', [$this, 'action_notif_queue'], 10, 5);

            add_action('publishpress_cron_notify', [$this, 'action_cron_notify'], 10, 8);
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
         * @param $workflowPostId
         * @param $action
         * @param $postId
         * @param $content
         * @param $oldStatus
         * @param $newStatus
         * @param $channel
         * @param $receiver
         */
        public function action_cron_notify($workflowPostId, $action, $postId, $content, $oldStatus, $newStatus, $channel, $receiver)
        {
            $workflowPost = get_post($workflowPostId);
            $actionArgs   = [
                'action'     => $action,
                'post'       => get_post($postId),
                'new_status' => $newStatus,
                'old_status' => $oldStatus,
            ];
            $receivers    = [$receiver];

            /**
             * Triggers the notification. This can be caught by notification channels.
             *
             * @param WP_Post $workflow_post
             * @param array   $action_args
             * @param array   $receivers
             * @param array   $content
             * @param array   $channel
             */
            do_action('publishpress_notif_send_notification_' . $channel, $workflowPost, $actionArgs, $receivers, $content, $channel);
        }
    }
}
