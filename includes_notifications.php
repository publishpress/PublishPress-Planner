<?php
/**
 * File responsible for defining basic general constants used by the plugin.
 *
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

defined('ABSPATH') or die('No direct script access allowed.');

if ( ! defined('PUBLISHPRESS_NOTIF_LOADED')) {
    define('PUBLISHPRESS_NOTIF_MODULE_PATH', __DIR__ . '/modules/improved-notifications');
    define('PUBLISHPRESS_NOTIF_TWIG_PATH', PUBLISHPRESS_BASE_PATH . '/twig');
    define('PUBLISHPRESS_NOTIF_LOADED', 1);

    define('PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW', 'psppnotif_workflow');
}
