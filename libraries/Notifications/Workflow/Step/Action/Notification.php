<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Action;

class Notification
{
    public function __construct()
    {
        add_action('publishpress_notifications_running_for_post', [$this, 'send_sync_notifications']);
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

    public function send_notifications($workflow)
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
        $content_template = $this->get_content();

        // Run the action to each receiver.
        foreach ($receivers as $channel => $channel_receivers) {
            foreach ($channel_receivers as $receiver) {
                /**
                 * Prepare the content replacing shortcodes.
                 */
                $content = $workflow->do_shortcodes_in_content($content_template, $receiver, $channel);

                /**
                 * Filters the action to be executed. By default it will trigger the notification.
                 * But it can be changed to do another action. This allows to change the flow and
                 * catch the params to cache or queue for async notifications.
                 *
                 * @param string $action
                 * @param Workflow $workflow
                 * @param string $channel
                 */
                $action = apply_filters(
                    'publishpress_notif_workflow_do_action',
                    'publishpress_notif_send_notification_' . $channel,
                    $this,
                    $channel
                );

                /**
                 * Triggers the notification. This can be caught by notification channels.
                 * But can be intercepted by other plugins (cache, async, etc) to change the
                 * workflow.
                 *
                 * @param WP_Post $workflow_post
                 * @param array $event_args
                 * @param array $receiver
                 * @param array $content
                 * @param string $channel
                 */
                do_action($action, $workflow->workflow_post, $workflow->event_args, $receiver, $content, $channel);
            }
        }

        // Remove the shortcodes.
        $workflow->unregister_shortcodes();
    }
}
