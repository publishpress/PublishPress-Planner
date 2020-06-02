<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2018 PublishPress
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

namespace PublishPress\AsyncNotifications;

use Exception;
use PublishPress\Notifications\Traits\Dependency_Injector;

/**
 * Class DBAdapter
 *
 * @package PublishPress\AsyncNotifications
 */
class WPCron implements QueueInterface
{
    use Dependency_Injector;

    /**
     * Enqueue the notification for async processing.
     *
     * @param $workflowPostId
     * @param $eventArgs
     *
     * @throws Exception
     */
    public function enqueueNotification($workflowPostId, $eventArgs)
    {
        $data = [
            'workflowId' => $workflowPostId,
            'event'      => $eventArgs['event'],
            'postId'     => (int)$eventArgs['params']['post']->ID,
            'userId'     => get_current_user_id(),
        ];

        if (isset($eventArgs['params']['comment'])) {
            $data['commentId'] = (int)$eventArgs['params']['comment']->comment_ID;
        }

        if (isset($eventArgs['params']['old_status'])) {
            $data['oldStatus'] = $eventArgs['params']['old_status'];
            $data['newStatus'] = $eventArgs['params']['new_status'];
        }

        /**
         * @param array $data
         */
        $data = apply_filters('publishpress_notifications_queue_data', $data);

        $timestamp = apply_filters(
            'publishpress_notifications_scheduled_time_for_notification',
            time(),
            $workflowPostId,
            $eventArgs['params']['post']->ID
        );

        if (false === $timestamp) {
            // Abort.
            error_log('PublishPress aborted a notification. Invalid timestamp for the workflow ' . $workflowPostId);

            return;
        }

        $data['result'] = $this->scheduleEvent($data, $timestamp);

        /**
         * @param array $data
         */
        do_action('publishpress_notifications_enqueued_notification', $data);
    }

    /**
     * Schedule the notification event.
     *
     * @param $data
     * @param $timestamp
     *
     * @return bool
     * @throws Exception
     *
     */
    protected function scheduleEvent($data, $timestamp)
    {
        return wp_schedule_single_event(
            $timestamp,
            'publishpress_notifications_send_from_cron',
            $data
        );
    }
}
