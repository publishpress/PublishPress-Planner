<?php
/**
 * @package     PublishPress\Slack
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Channel;

use WP_Post;

interface Channel_Interface
{
    /**
     * Check if this channel is selected and triggers the notification.
     *
     * @param WP_Post $workflow_post
     * @param array   $action_args
     * @param array   $receivers
     * @param array   $content
     * @param string  $channel
     */
    public function action_send_notification($workflow_post, $action_args, $receivers, $content, $channel);
}
