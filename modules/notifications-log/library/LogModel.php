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

/**
 * Class LogModel
 *
 * @package PublishPress\NotificationsLog
 */
class LogModel
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

    const META_NOTIF_ACTION = '_ppnotif_action';

    const META_NOTIF_SUCCESS = '_ppnotif_success';

    const META_NOTIF_ERROR = '_ppnotif_error';

    const META_NOTIF_ASYNC = '_ppnotif_async';

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
    public $action;

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
    public $receiverName;

    /**
     * @var string
     */
    public $author;

    /**
     * @var string
     */
    public $date;

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
     * LogModel constructor.
     *
     * @param $log
     */
    public function __construct($log)
    {
        $log = get_comment($log);

        $this->id           = $log->comment_ID;
        $this->postId       = $log->comment_post_ID;
        $this->author       = self::COMMENT_AUTHOR;
        $this->content      = maybe_unserialize($log->comment_content);
        $this->date         = $log->comment_date;
        $this->workflowId   = get_comment_meta($log->comment_ID, self::META_NOTIF_WORKFLOW_ID, true);
        $this->action       = get_comment_meta($log->comment_ID, self::META_NOTIF_ACTION, true);
        $this->oldStatus    = get_comment_meta($log->comment_ID, self::META_NOTIF_OLD_STATUS, true);
        $this->newStatus    = get_comment_meta($log->comment_ID, self::META_NOTIF_NEW_STATUS, true);
        $this->channel      = get_comment_meta($log->comment_ID, self::META_NOTIF_CHANNEL, true);
        $this->receiver     = get_comment_meta($log->comment_ID, self::META_NOTIF_RECEIVER, true);
        $this->receiverName = '';
        $this->success      = get_comment_meta($log->comment_ID, self::META_NOTIF_SUCCESS, true);
        $this->error        = get_comment_meta($log->comment_ID, self::META_NOTIF_ERROR, true);
        $this->async        = get_comment_meta($log->comment_ID, self::META_NOTIF_ASYNC, true);

        $workflow = get_post($this->workflowId);
        $post     = get_post($this->postId);

        $this->workflowTitle = $workflow->post_title;
        $this->postTitle     = $post->post_title;

        if (is_numeric($this->receiver)) {
            $user = get_user_by('id', $this->receiver);

            if (!empty($user)) {
                if (!empty($user->first_name)) {
                    $this->receiverName = $user->first_name . ' ' . $user->last_name;
                } else {
                    $this->receiverName = $user->nickname;
                }
            }
        }
    }

    public function receiverIsUser()
    {
        return is_numeric($this->receiver);
    }
}
