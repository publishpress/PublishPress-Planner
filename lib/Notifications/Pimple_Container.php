<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications;

use PublishPress\Pimple\Container;
use PP_Debug;
use PublishPress\AsyncNotifications\SchedulerInterface;
use PublishPress\AsyncNotifications\WPCronAdapter;
use PublishPress\Core\View;

class Pimple_Container extends Container
{
    /**
     * Instance of the Pimple container
     */
    protected static $instance;

    public static function get_instance()
    {
        if (empty(static::$instance)) {
            $instance = new self;

            // Define the services

            $instance['view'] = function ($c) {
                return new View();
            };

            $instance['publishpress'] = function ($c) {
                global $publishpress;

                return $publishpress;
            };

            $instance['workflows_controller'] = function ($c) {
                return new Workflow\WorkflowsController;
            };

            $instance['shortcodes'] = function ($c) {
                return new Shortcodes;
            };

            /**
             * @param $c
             *
             * @return SchedulerInterface
             */
            $instance['notification_scheduler'] = function ($c) {
                return apply_filters('publishpress_notifications_notification_scheduler', new WPCronAdapter(), $c);
            };

            /**
             * @param $c
             *
             * @return bool
             */
            $instance['DEBUGGING'] = function ($c) {
                if (!isset($c['publishpress']->modules->debug)) {
                    return false;
                }

                if (!isset($c['publishpress']->modules->debug->options)) {
                    return false;
                }

                return $c['publishpress']->modules->debug->options->enabled === 'on';
            };

            /**
             * @param $c
             *
             * @return PP_Debug
             */
            $instance['debug'] = function ($c) {
                return $c['publishpress']->debug;
            };

            static::$instance = $instance;
        }

        return static::$instance;
    }
}
