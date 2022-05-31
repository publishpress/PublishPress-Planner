<?php
/**
 * File responsible for defining basic plugin class
 *
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications;

use PublishPress\Notifications\Workflow\Step\Action\Notification;

defined('ABSPATH') or die('No direct script access allowed.');

abstract class Helper
{
    /**
     * @param array $content
     * @param string $receiver
     * @param string $channel
     *
     * @return string
     */
    public static function calculateNotificationUID($content, $receiver, $channel)
    {
        return md5(maybe_serialize([$content, $receiver, $channel]));
    }

    /**
     * @param $timestamp
     * @param $data
     *
     * @return string
     */
    public static function calculateNotificationScheduleUID($timestamp, $data)
    {
        return md5(maybe_serialize([$timestamp, $data]));
    }

    public static function getDuplicatedNotificationThreshold()
    {
        global $publishpress;

        if (isset($publishpress->improved_notifications->module->options->duplicated_notification_threshold)) {
            return $publishpress->improved_notifications->module->options->duplicated_notification_threshold;
        }

        return Notification::DEFAULT_DUPLICATED_NOTIFICATION_THRESHOLD_IN_MINUTES;
    }

    /**
     * Check if the notification was just sent, to avoid duplicated notifications when
     * multiple requests try to run the same job.
     *
     * @param array $content
     * @param string $receiver
     * @param string $channel
     * @param int $threshold
     *
     * @return bool
     */
    public static function isDuplicatedNotification($content, $receiver, $channel, $threshold)
    {
        $uid = static::calculateNotificationUID($content, $receiver, $channel);

        $transientName = 'ppnotif_' . $uid;

        // Check if we already have the transient.
        if (get_transient($transientName)) {
            // Yes, duplicated notification.
            return true;
        }

        /**
         * Filters the value of the timeout to ignore duplicated notifications.
         *
         * @param int $timeout
         * @param string $uid
         *
         * @return int
         */
        $timeout = (int)apply_filters(
            'pp_duplicated_notification_timeout',
            ($threshold + 5) * 60,
            $uid
        );

        // Set the flag and return as non-duplicated.
        set_transient($transientName, 1, $timeout);

        return false;
    }

    public static function isDuplicatedNotificationSchedule($timestamp, $data)
    {
        $uid = static::calculateNotificationScheduleUID($timestamp, $data);

        $transientName = 'ppnotif_schedule_' . $uid;

        // Check if we already have the transient.
        if (get_transient($transientName)) {
            // Yes, duplicated notification.
            return true;
        }

        /**
         * Filters the value of the timeout to ignore duplicated notifications.
         *
         * @param int $timeout
         * @param string $uid
         *
         * @return int
         */
        $timeout = (int)apply_filters(
            'pp_duplicated_notification_timeout',
            (static::getDuplicatedNotificationThreshold() + 5) * 60,
            $uid
        );

        // Set the flag and return as non-duplicated.
        set_transient($transientName, 1, $timeout);

        return false;
    }
}
