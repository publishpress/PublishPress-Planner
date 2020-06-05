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

use Exception;

/**
 * Class NotificationsLogHandler
 *
 * @package PublishPress\NotificationsLog
 */
class NotificationsLogHandler
{
    /**
     * Register the notification log for the post.
     *
     * @param $data
     *
     * @return false|int
     * @throws Exception
     */
    public function registerLog($data = [])
    {
        $defaultData = [
            'event'       => null,
            'user_id'     => null,
            'workflow_id' => null,
            'old_status'  => null,
            'new_status'  => null,
            'comment_id'  => null,
            'async'       => null,
            'status'      => null,
            'channel'     => null,
            'receiver'    => null,
            'success'     => null,
            'error'       => null,
            'event_args'  => null,
        ];

        $data = array_merge($defaultData, $data);

        $commentData = [
            'comment_post_ID'      => $data['post_id'],
            'comment_author'       => NotificationsLogModel::COMMENT_AUTHOR,
            'comment_author_email' => '',
            'comment_content'      => null,
            'comment_type'         => NotificationsLogModel::COMMENT_TYPE,
            'comment_parent'       => 0,
            'user_id'              => $data['user_id'],
            'comment_agent'        => NotificationsLogModel::COMMENT_USER_AGENT,
            'comment_approved'     => NotificationsLogModel::COMMENT_APPROVED,
            'comment_meta'         => [
                NotificationsLogModel::META_NOTIF_EVENT       => $data['event'],
                NotificationsLogModel::META_NOTIF_USER_ID     => $data['user_id'],
                NotificationsLogModel::META_NOTIF_WORKFLOW_ID => $data['workflow_id'],
                NotificationsLogModel::META_NOTIF_OLD_STATUS  => $data['old_status'],
                NotificationsLogModel::META_NOTIF_NEW_STATUS  => $data['new_status'],
                NotificationsLogModel::META_NOTIF_COMMENT_ID  => $data['comment_id'],
                NotificationsLogModel::META_NOTIF_ASYNC       => $data['async'],
                NotificationsLogModel::META_NOTIF_STATUS      => $data['status'],
                NotificationsLogModel::META_NOTIF_CHANNEL     => $data['channel'],
                NotificationsLogModel::META_NOTIF_RECEIVER    => $data['receiver'],
                NotificationsLogModel::META_NOTIF_SUCCESS     => $data['success'],
                NotificationsLogModel::META_NOTIF_ERROR       => $data['error'],
                NotificationsLogModel::META_NOTIF_EVENT_ARGS  => $data['event_args'],
            ],
        ];

        $logId = wp_insert_comment($commentData);

        $last_changed = microtime();
        wp_cache_set('last_changed', $last_changed, 'comment');

        return $logId;
    }

    /**
     * @param        $postID
     *
     * @param string $orderBy
     * @param string $order
     * @param bool $returnTotal
     * @param array $filters
     * @param int $perPage
     * @param int $currentPage
     *
     * @return array
     */
    public function getNotificationLogEntries(
        $postID = null,
        $orderBy = 'comment_date',
        $order = 'desc',
        $returnTotal = false,
        $filters = [],
        $perPage = null,
        $currentPage = null
    ) {
        $args = [
            'type'    => NotificationsLogModel::COMMENT_TYPE,
            'orderby' => $orderBy,
            'order'   => $order,
            'count'   => (bool)$returnTotal,
            'status'  => NotificationsLogModel::COMMENT_APPROVED,
        ];

        if (!empty($postID)) {
            $args['post_id'] = $postID;
        }

        if (!empty($perPage)) {
            $args['number'] = (int)$perPage;
        }

        if (!empty($currentPage)) {
            $args['offset'] = ($currentPage - 1) * $perPage;
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'scheduled') {
                $args['meta_key']   = NotificationsLogModel::META_NOTIF_STATUS;
                $args['meta_value'] = 'scheduled';
            } elseif ($filters['status'] === 'success') {
                $args['meta_key']   = NotificationsLogModel::META_NOTIF_SUCCESS;
                $args['meta_value'] = true;
            } elseif ($filters['status'] === 'error') {
                $args['meta_key']   = NotificationsLogModel::META_NOTIF_SUCCESS;
                $args['meta_value'] = false;
            }
        }

        if (isset($filters['workflow_id'])) {
            $args['meta_query'] = [
                [
                    'key'   => NotificationsLogModel::META_NOTIF_WORKFLOW_ID,
                    'value' => (int)$filters['workflow_id'],
                ],
            ];
        }

        if (isset($filters['workflow_action']) && !empty($filters['workflow_action'])) {
            $args['meta_query'] = [
                [
                    'key'   => NotificationsLogModel::META_NOTIF_ACTION,
                    'value' => $filters['workflow_action'],
                ],
            ];
        }

        if (isset($filters['channel']) && !empty($filters['channel'])) {
            $args['meta_query'] = [
                [
                    'key'   => NotificationsLogModel::META_NOTIF_CHANNEL,
                    'value' => $filters['channel'],
                ],
            ];
        }

        if (isset($filters['receiver']) && !empty($filters['receiver'])) {
            $args['meta_query'] = [
                [
                    'key'     => NotificationsLogModel::META_NOTIF_RECEIVER,
                    'value'   => $filters['receiver'],
                    'compare' => 'LIKE',
                ],
            ];
        }

        if (isset($filters['date_begin']) && !empty($filters['date_begin'])) {
            $args['date_query'] = [
                'after'     => $filters['date_begin'],
                'before'    => $filters['date_end'],
                'inclusive' => true,
            ];
        }

        $list = get_comments($args);

        return $list;
    }
}
