<?php
/**
 * @package     PublishPress\Slack
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Channel;

interface Channel_Interface
{
    /**
     * Check if this channel is selected and triggers the notification.
     *
     * @param Workflow $workflow
     * @param array $receiverData
     * @param array $content
     * @param string $channel
     * @param bool $async
     *
     * @throws Exception
     */
    public function action_send_notification($workflow, $receiverData, $content, $channel, $async);
}
