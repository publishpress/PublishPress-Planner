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

namespace PublishPress\NotificationsLog;

use PublishPress\Notifications\Workflow\Workflow;
use WP_Comment;

/**
 * Class NotificationsLogModel
 *
 * @package PublishPress\NotificationsLog
 */
class NotificationsLogModel
{
    const COMMENT_TYPE = 'notification';

    const COMMENT_APPROVED = 'notification-log';

    const COMMENT_USER_AGENT = 'PublishPress/NotificationsLog';

    const COMMENT_AUTHOR = 'NotificationsLog';

    const META_NOTIF_RECEIVER = '_ppnotif_receiver';

    const META_NOTIF_WORKFLOW_ID = '_ppnotif_workflow_id';

    const META_NOTIF_OLD_STATUS = '_ppnotif_old_status';

    const META_NOTIF_NEW_STATUS = '_ppnotif_new_status';

    const META_NOTIF_CHANNEL = '_ppnotif_channel';

    const META_NOTIF_EVENT = '_ppnotif_event';

    const META_NOTIF_STATUS = '_ppnotif_status';

    const META_NOTIF_SUCCESS = '_ppnotif_success';

    const META_NOTIF_ERROR = '_ppnotif_error';

    const META_NOTIF_ASYNC = '_ppnotif_async';

    const META_NOTIF_COMMENT_ID = '_ppnotif_comment_id';

    const META_NOTIF_EVENT_ARGS = '_ppnotif_event_args';

    const META_NOTIF_USER_ID = '_ppnotif_user_id';

    const META_NOTIF_POST_ID = '_ppnotif_post_id';

    const META_NOTIF_CRON_ID = '_ppnotif_cron_id';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $postId;

    /**
     * @var string
     */
    public $content;

    /**
     * @var int
     */
    public $workflowId;

    /**
     * @var string
     */
    public $workflowTitle;

    /**
     * @var string
     */
    public $event;

    /**
     * @var string
     */
    public $oldStatus;

    /**
     * @var string
     */
    public $newStatus;

    /**
     * @var string
     */
    public $channel;

    /**
     * @var string
     */
    public $receiver;

    /**
     * @var string
     */
    public $author;

    /**
     * @var string
     */
    public $date;

    /**
     * @var string
     */
    public $status;

    /**
     * @var bool
     */
    public $success;

    /**
     * @var string
     */
    public $error;

    /**
     * @var bool
     */
    public $async;

    /**
     * @var int
     */
    public $commentId;

    /**
     * @var string
     */
    public $postTitle;

    /**
     * @var array
     */
    public $eventArgs;

    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $cronId;

    /**
     * NotificationsLogModel constructor.
     *
     * @param WP_Comment $log
     */
    public function __construct(WP_Comment $log)
    {
        $this->id         = (int)$log->comment_ID;
        $this->postId     = (int)$log->comment_post_ID;
        $this->author     = self::COMMENT_AUTHOR;
        $this->content    = maybe_unserialize($log->comment_content);
        $this->date       = $log->comment_date;
        $this->workflowId = (int)$this->get_meta(self::META_NOTIF_WORKFLOW_ID);
        $this->userId     = (int)$this->get_meta(self::META_NOTIF_USER_ID);
        $this->event      = $this->get_meta(self::META_NOTIF_EVENT);
        $this->oldStatus  = $this->get_meta(self::META_NOTIF_OLD_STATUS);
        $this->newStatus  = $this->get_meta(self::META_NOTIF_NEW_STATUS);
        $this->channel    = $this->get_meta(self::META_NOTIF_CHANNEL);
        $this->receiver   = $this->get_meta(self::META_NOTIF_RECEIVER);
        $this->status     = $this->get_meta(self::META_NOTIF_STATUS);
        $this->success    = $this->get_meta(self::META_NOTIF_SUCCESS);
        $this->error      = $this->get_meta(self::META_NOTIF_ERROR);
        $this->async      = $this->get_meta(self::META_NOTIF_ASYNC);
        $this->commentId  = (int)$this->get_meta(self::META_NOTIF_COMMENT_ID);
        $this->eventArgs  = $this->get_meta(self::META_NOTIF_EVENT_ARGS);
        $this->cronId     = $this->get_meta(self::META_NOTIF_CRON_ID);

        if (!empty($this->eventArgs) && isset($this->eventArgs['postId'])) {
            $this->eventArgs['post'] = get_post((int)$this->eventArgs['postId']);
        }

        $workflow = get_post($this->workflowId);
        $post     = get_post($this->postId);

        $this->workflowTitle = $workflow->post_title;
        $this->postTitle     = $post->post_title;
    }

    private function getWorkflow()
    {
        $workflow = Workflow::load_by_id($this->workflowId);

        $workflow->event_args = $this->eventArgs;

        return $workflow;
    }

    public function getReceiversByGroup()
    {
        if ($this->status === 'scheduled') {
            $workflow = $this->getWorkflow();

            return $workflow->get_receivers_by_group();
        } else {
            return [$this->receiver];
        }
    }

    /**
     * @param $meta_key
     * @param bool $single
     * @return mixed
     */
    private function get_meta($meta_key, $single = true)
    {
        return get_comment_meta($this->id, $meta_key, $single);
    }

    public function delete()
    {
        $cronTask = $this->getCronTask();

        if (!empty($cronTask)) {
            wp_clear_scheduled_hook('publishpress_notifications_send_from_cron', $cronTask['args']);
        }

        wp_delete_comment($this->id, true);
    }

    public function getCronTask()
    {
        $cronArray = _get_cron_array();

        $expectedHooks = ['publishpress_notifications_send_from_cron',];

        if (!empty($cronArray)) {
            foreach ($cronArray as $time => $cronTasks) {
                foreach ($cronTasks as $hook => $dings) {
                    if (!in_array($hook, $expectedHooks)) {
                        continue;
                    }

                    if (array_key_exists($this->cronId, $dings)) {
                        $data = $dings[$this->cronId];

                        return [
                            'cronId' => $this->cronId,
                            'time'   => $time,
                            'hook'   => $hook,
                            'args'   => $data['args'],
                        ];
                    }
                }
            }
        }

        return false;
    }
}
