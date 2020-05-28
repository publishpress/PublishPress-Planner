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
 * Copyright (c ) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option ) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use PPVersionNotices\Module\MenuLink\Module;
use PublishPress\Legacy\Auto_loader;

if (!defined('PP_LOADED')) {
    $settingsPage = add_query_arg(
        [
            'page'   => 'pp-modules-settings',
            'module' => 'pp-modules-settings-settings',
        ],
        get_admin_url(null, 'admin.php')
    );

    // Define contants
    define('PUBLISHPRESS_VERSION', '2.1.0');
    define('PUBLISHPRESS_BASE_PATH', __DIR__);
    define('PUBLISHPRESS_FILE_PATH', PUBLISHPRESS_BASE_PATH . '/publishpress.php');
    define('PUBLISHPRESS_URL', plugins_url('/', __FILE__));
    define('PUBLISHPRESS_SETTINGS_PAGE', $settingsPage);
    define('PUBLISHPRESS_LIBRARIES_PATH', PUBLISHPRESS_BASE_PATH . '/libraries');
    define('PUBLISHPRESS_BASENAME', plugin_basename(PUBLISHPRESS_FILE_PATH));

    /**
     * Use PUBLISHPRESS_BASE_PATH instead.
     *
     * @deprecated
     */
    define('PUBLISHPRESS_ROOT', PUBLISHPRESS_BASE_PATH);

    // Define the Priority for the notification/notification_status_change method
    // Added to allow users select a custom priority
    if (!defined('PP_NOTIFICATION_PRIORITY_STATUS_CHANGE')) {
        define('PP_NOTIFICATION_PRIORITY_STATUS_CHANGE', 10);
    }

    if (file_exists(PUBLISHPRESS_BASE_PATH . '/vendor/autoload.php')) {
        require_once PUBLISHPRESS_BASE_PATH . '/vendor/autoload.php';
    }

    // Register the autoloader
    if (!class_exists('\\PublishPress\\Legacy\\Auto_loader')) {
        require_once PUBLISHPRESS_LIBRARIES_PATH . '/Legacy/Auto_loader.php';
    }

    // Register the library
    Auto_loader::register('\\PublishPress\\Legacy\\', PUBLISHPRESS_LIBRARIES_PATH . '/Legacy');
    Auto_loader::register(
        '\\PublishPress\\Notifications\\',
        PUBLISHPRESS_LIBRARIES_PATH . '/Notifications'
    );

    require_once PUBLISHPRESS_BASE_PATH . '/deprecated.php';

    if (is_admin() && !defined('PUBLISHPRESS_SKIP_VERSION_NOTICES')) {
        $includesFile = __DIR__ . '/vendor/publishpress/wordpress-version-notices/includes.php';

        if (file_exists($includesFile)) {
            require_once $includesFile;
        }

        add_filter(
            \PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER,
            function ($settings) {
                $settings['publishpress'] = [
                    'message' => 'You\'re using PublishPress Free. The Pro version has more features and support. %sUpgrade to Pro%s',
                    'link'    => 'https://publishpress.com/links/publishpress-banner',
                    'screens' => [
                        ['base' => 'publishpress_page_pp-modules-settings',],
                        ['base' => 'publishpress_page_pp-manage-roles',],
                        ['base' => 'publishpress_page_pp-notif-log',],
                        ['base' => 'edit', 'id' => 'edit-psppnotif_workflow',],
                        ['base' => 'post', 'id' => 'psppnotif_workflow',],
                        ['base' => 'publishpress_page_pp-content-overview',],
                        ['base' => 'toplevel_page_pp-calendar', 'id' => 'toplevel_page_pp-calendar',],
                    ]
                ];

                return $settings;
            }
        );

        add_filter(
            Module::SETTINGS_FILTER,
            function ($settings) {
                $settings['publishpress'] = [
                    'parent' => [
                        'pp-calendar',
                        'pp-content-overview',
                        'edit.php?post_type=psppnotif_workflow',
                        'pp-notif-log',
                        'pp-manage-roles',
                        'pp-modules-settings',
                    ],
                    'label'  => 'Upgrade to Pro',
                    'link'   => 'https://publishpress.com/links/publishpress-menu',
                ];

                return $settings;
            }
        );
    }

    define('PP_LOADED', 1);
}
