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

if (is_admin()) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'publishpress' . DIRECTORY_SEPARATOR
        . 'wordpress-pro-plugins-ads' . DIRECTORY_SEPARATOR . 'includes.php';

    add_filter(\PPProAds\Module\TopBanner\Module::SETTINGS_FILTER, function ($settings) {
        $settings['publishpress'] = [
            'message' => 'You\'re using PublishPress Free. To unlock more features, consider %supgrading to Pro%s.',
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
    });

    do_action('pp_pro_ads_init');
}
