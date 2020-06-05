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
            'workflow_id' => $workflowPostId,
            'event_args'  => $eventArgs,
        ];

        /**
         * @param array $data
         */
        $data = apply_filters('publishpress_notifications_queue_data', $data);

        $timestamp = apply_filters(
            'publishpress_notifications_scheduled_time_for_notification',
            time(),
            $workflowPostId,
            $eventArgs['params']['post_id']
        );

        if (false === $timestamp) {
            // Abort.
            error_log('PublishPress aborted a notification. Invalid timestamp for the workflow ' . $workflowPostId);

            return;
        }

        $cronId = $this->getCronId($data);
        do_action('publishpress_notifications_scheduled_cron', $data, $cronId);

        $data['result'] = $this->scheduleEvent($data, $timestamp);

        /**
         * @param array $data
         */
        do_action('publishpress_notifications_enqueued_notification', $data);
    }

    private function getCronId($args)
    {
        return md5(serialize($args));
    }

    /**
     * Schedule the notification event.
     *
     * @param $args
     * @param $timestamp
     *
     * @return bool
     * @throws Exception
     *
     */
    protected function scheduleEvent($args, $timestamp)
    {
        return wp_schedule_single_event(
            $timestamp,
            'publishpress_notifications_send_from_cron',
            $args
        );
    }
}
