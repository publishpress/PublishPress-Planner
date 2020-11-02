<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Action;

use Exception;
use PublishPress\Notifications\Workflow\Workflow;

class Notification
{
    const DEFAULT_DUPLICATED_NOTIFICATION_THRESHOLD = 10;

    public function __construct()
    {
        add_action('publishpress_notifications_running_for_post', [$this, 'send_sync_notifications']);
        add_action('publishpress_notifications_send_notifications_action', [$this, 'send_notifications'], 10, 2);
    }

    public function send_sync_notifications($workflow)
    {
        /**
         * @param bool $stop
         */
        if (apply_filters('publishpress_notifications_stop_sync_notifications', false)) {
            return;
        }

        $this->send_notifications($workflow);
    }

    /**
     * Check if the notification was just sent, to avoid duplicated notifications when
     * multiple requests try to run the same job.
     *
     * @param array $content
     * @param string $receiver
     * @param string $channel
     *
     * @return bool
     */
    private function is_duplicated_notification($content, $receiver, $channel)
    {
        $uid = $this->calculateNotificationUID($content, $receiver, $channel);

        $transientName = 'ppnotif_' . $uid;

        // Check if we already have the transient.
        if (get_transient($transientName)) {
            // Yes, duplicated notification.
            return true;
        }

        global $publishpress;
        if ($publishpress->improved_notifications->module->options->duplicated_notification_threshold) {
            $threshold = (int)$publishpress->improved_notifications->module->options->duplicated_notification_threshold;
        } else {
            $threshold = self::DEFAULT_DUPLICATED_NOTIFICATION_THRESHOLD;
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
            $threshold * 60,
            $uid
        );

        // Set the flag and return as non-duplicated.
        set_transient($transientName, 1, $timeout);

        return false;
    }

    /**
     * @param array $content
     * @param string $receiver
     * @param string $channel
     *
     * @return string
     */
    private function calculateNotificationUID($content, $receiver, $channel)
    {
        return md5(maybe_serialize([$content, $receiver, $channel]));
    }

    /**
     * @param Workflow $workflow
     * @param bool $async
     * @throws Exception
     */
    public function send_notifications($workflow, $async = false)
    {
        // Who will receive the notification?
        $receivers = $workflow->get_receivers_by_channel();

        // If we don't have receivers, abort the workflow.
        if (empty($receivers)) {
            return;
        }

        /*
         * What will the notification says?
         */
        $content_template = $workflow->get_content();

        // Run the action to each receiver.
        foreach ($receivers as $channel => $channel_receivers) {
            foreach ($channel_receivers as $receiver) {
                /**
                 * Prepare the content replacing shortcodes.
                 */
                $content = $workflow->do_shortcodes_in_content($content_template, $receiver['receiver'], $channel);

                /**
                 * @param
                 */
                $receiverAddress = apply_filters(
                    'publishpress_notifications_receiver_address',
                    $receiver['receiver'],
                    $channel
                );

                global $publishpress;
                if ($publishpress->improved_notifications->module->options->duplicated_notification_threshold) {
                    $threshold = $publishpress->improved_notifications->module->options->duplicated_notification_threshold;
                } else {
                    $threshold = self::DEFAULT_DUPLICATED_NOTIFICATION_THRESHOLD;
                }

                // Check if this is a duplicated notification and skip it.
                // I hope this is a temporary fix. When scheduled, some notifications seems to be triggered multiple times
                // by the same cron task.
                if ($this->is_duplicated_notification($content, $receiverAddress, $channel)) {
                    do_action(
                        'publishpress_notifications_skipped_duplicated',
                        $workflow,
                        $receiver,
                        $content,
                        $channel,
                        $async,
                        $threshold
                    );
                    continue;
                }

                /**
                 * Triggers the notification. This can be caught by notification channels.
                 * But can be intercepted by other plugins (cache, async, etc) to change the
                 * workflow.
                 *
                 * @param Workflow $workflow
                 * @param array $receiver
                 * @param array $content
                 * @param string $channel
                 * @param bool $async
                 */
                do_action(
                    'publishpress_notif_send_notification_' . $channel,
                    $workflow,
                    $receiver,
                    $content,
                    $channel,
                    $async
                );
            }
        }

        // Remove the shortcodes.
        $workflow->unregister_shortcodes();
    }
}
