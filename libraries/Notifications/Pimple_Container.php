<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications;

use Allex\Core;
use PublishPress\AsyncNotifications\Queue\WPCron;

class Pimple_Container extends \Pimple\Container
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

            $instance['EDD_API_URL'] = function ($c) {
                return 'https://publishpress.com';
            };

            $instance['PLUGIN_AUTHOR'] = function ($c) {
                return 'PublishPress';
            };

            $instance['twig_function_checked'] = function ($c) {
                return new \Twig_SimpleFunction('checked', function ($checked, $current = true, $echo = true) {
                    return checked($checked, $current, $echo);
                });
            };

            $instance['twig_function_selected'] = function ($c) {
                return new \Twig_SimpleFunction('selected', function ($selected, $current = true, $echo = true) {
                    return selected($selected, $current, $echo);
                });
            };

            $instance['twig_function_editor'] = function ($c) {
                return new \Twig_SimpleFunction('editor', function ($content, $editor_id, $attrs = []) {
                    wp_editor($content, $editor_id, $attrs);

                    return '';
                });
            };

            $instance['twig_loader_filesystem'] = function ($c) {
                return new \Twig_Loader_Filesystem(PUBLISHPRESS_NOTIF_TWIG_PATH);
            };

            $instance['twig'] = function ($c) {
                $twig = new \Twig_Environment(
                    $c['twig_loader_filesystem'],
                    // array('debug' => true)
                    []
                );

                $twig->addFunction($c['twig_function_checked']);
                $twig->addFunction($c['twig_function_selected']);
                $twig->addFunction($c['twig_function_editor']);

                // $twig->addExtension(new \Twig_Extension_Debug());

                return $twig;
            };

            $instance['publishpress'] = function ($c) {
                global $publishpress;

                return $publishpress;
            };

            $instance['workflow_controller'] = function ($c) {
                return new Workflow\Controller;
            };

            $instance['shortcodes'] = function ($c) {
                return new Shortcodes;
            };

            /**
             * @param $c
             *
             * @return WPCron
             */
            $instance['notification_queue'] = function ($c) {
                return new WPCron();
            };

            $instance['framework'] = function ($c) {
                // The 4th param is there just for backward compatibility with older versions of the Allex framework
                // packed in UpStream.
                return new Core($c['PLUGIN_BASENAME'], $c['EDD_API_URL'], $c['PLUGIN_AUTHOR'], '');
            };

            $instance['reviews'] = function ($c) {
                return new Reviews();
            };

            $instance['PLUGIN_BASENAME'] = function ($c) {
                return PUBLISHPRESS_BASENAME;
            };

            /**
             * @param $c
             *
             * @return bool
             */
            $instance['DEBUGGING'] = function ($c) {
                if ( ! isset($c['publishpress']->modules->debug)) {
                    return false;
                }

                if ( ! isset($c['publishpress']->modules->debug->options)) {
                    return false;
                }

                return $c['publishpress']->modules->debug->options->enabled === 'on';
            };

            /**
             * @param $c
             *
             * @return \PP_Debug
             */
            $instance['debug'] = function ($c) {
                return $c['publishpress']->debug;
            };

            static::$instance = $instance;
        }

        return static::$instance;
    }
}
