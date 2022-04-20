<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
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

namespace PublishPress\NotificationsLog;


use PublishPress\Core\ModuleErrorsInterface;

class ModuleErrors implements ModuleErrorsInterface
{
    const ERROR_CODE_INVALID_RECEIVER = 'PPE_NOTIF_1';

    const ERROR_CODE_INVALID_CHANNEL = 'PPE_NOTIF_2';

    const ERROR_CODE_NOTIFICATION_LOG_NOT_FOUND = 'PPE_NOTIF_3';

    private static $instance;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return [
            self::ERROR_CODE_INVALID_RECEIVER => __('Invalid receiver for the notification', 'publishpress'),
            self::ERROR_CODE_INVALID_CHANNEL => __('Invalid channel for the notification', 'publishpress'),
            self::ERROR_CODE_NOTIFICATION_LOG_NOT_FOUND => __('Notification log not found', 'publishpress'),
        ];
    }
}
