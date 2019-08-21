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

namespace PublishPress\AsyncNotifications\Queue;

use PublishPress\Notifications\Traits\Dependency_Injector;

/**
 * Class DBAdapter
 *
 * @package PublishPress\AsyncNotifications\Queue
 */
class WPCron implements QueueInterface
{
    use Dependency_Injector;

    /**
     * Enqueue the notification for async processing.
     *
     * @param $workflowPost
     * @param $actionArgs
     * @param $receivers
     * @param $content
     * @param $channel
     *
     * @throws \Exception
     */
    public function enqueueNotification($workflowPost, $actionArgs, $receivers, $content, $channel)
    {
        if ( ! is_array($receivers)) {
            $receivers = [$receivers];
        }

        if ( ! empty($receivers)) {
            $baseData = [
                // workflow_post_id
                $workflowPost->ID,
                // action
                $actionArgs['action'],
                // post_id
                $actionArgs['post']->ID,
                // content
                base64_encode(maybe_serialize($content)),
                // old_status
                isset($actionArgs['old_status']) ? $actionArgs['old_status'] : null,
                // new_status
                isset($actionArgs['new_status']) ? $actionArgs['new_status'] : null,
                // channel
                $channel,
            ];

            $timestamp = apply_filters('publishpress_notif_async_timestamp', time(), $workflowPost->ID,
                $actionArgs['post']->ID);

            if (false === $timestamp) {
                // Abort.
                error_log('PublishPress aborted a notification. Invalid timestamp for workflow ' . $workflowPost->ID);

                return;
            }

            // Create one notification for each receiver in the queue
            foreach ($receivers as $receiver) {
                // Base data
                $data = $baseData;

                // Receiver
                $data[] = $receiver;

                $this->scheduleEvent($data, $timestamp);
            }

            do_action('publishpress_enqueue_notification', $workflowPost->ID, $actionArgs['action'],
                $actionArgs['post']->ID, $actionArgs);
        }
    }

    /**
     * Schedule the notification event.
     *
     * @param $data
     * @param $timestamp
     *
     * @throws \Exception
     */
    protected function scheduleEvent($data, $timestamp)
    {
        wp_schedule_single_event(
            $timestamp,
            'publishpress_cron_notify',
            $data
        );
    }
}
