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

    // Define constants
    define('PUBLISHPRESS_VERSION', '2.2.0-hotfix-652');
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

    define('PP_LOADED', 1);
}
