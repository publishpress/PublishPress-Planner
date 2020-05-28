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
 * Class LogHandler
 *
 * @package PublishPress\NotificationsLog
 */
class LogHandler
{
    const META_NOTIF_ACTION = '_ppnotif_action';

    /**
     * Register the notification log for the post.
     *
     *      $data = [
     *          post_id,
     *          content
     *          workflow_id
     *
     *      ];
     *
     * @param $data
     *
     * @return false|int
     * @throws Exception
     */
    public function register($data = [])
    {
        $user = wp_get_current_user();

        $defaultData = [
            'post_id'     => 0,
            'content'     => '',
            'workflow_id' => 0,
            'action'      => '',
            'old_status'  => '',
            'new_status'  => '',
            'channel'     => '',
            'receiver'    => '',
            'success'     => false,
            'error'       => null,
            'async'       => false,
        ];

        $data = array_merge($defaultData, $data);

        $data = [
            'comment_post_ID'      => $data['post_id'],
            'comment_author'       => LogModel::COMMENT_AUTHOR,
            'comment_author_email' => '',
            'comment_content'      => $data['content'],
            'comment_type'         => LogModel::COMMENT_TYPE,
            'comment_parent'       => 0,
            'user_id'              => $user->ID,
            'comment_agent'        => LogModel::COMMENT_USER_AGENT,
            'comment_approved'     => LogModel::COMMENT_APPROVED,
            'comment_meta'         => [
                LogModel::META_NOTIF_WORKFLOW_ID => $data['workflow_id'],
                LogModel::META_NOTIF_ACTION      => $data['action'],
                LogModel::META_NOTIF_OLD_STATUS  => $data['old_status'],
                LogModel::META_NOTIF_NEW_STATUS  => $data['new_status'],
                LogModel::META_NOTIF_CHANNEL     => $data['channel'],
                LogModel::META_NOTIF_RECEIVER    => $data['receiver'],
                LogModel::META_NOTIF_SUCCESS     => $data['success'],
                LogModel::META_NOTIF_ERROR       => $data['error'],
                LogModel::META_NOTIF_ASYNC       => $data['async'],
            ],
        ];

        $result = wp_insert_comment($data);

        $last_changed = microtime();
        wp_cache_set('last_changed', $last_changed, 'comment');

        return $result;
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
    public function getNotifications(
        $postID = null,
        $orderBy = 'comment_date',
        $order = 'desc',
        $returnTotal = false,
        $filters = [],
        $perPage = null,
        $currentPage = null
    ) {
        $args = [
            'type'    => LogModel::COMMENT_TYPE,
            'orderby' => $orderBy,
            'order'   => $order,
            'count'   => (bool)$returnTotal,
            'status'  => LogModel::COMMENT_APPROVED,
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
            if ($filters['status'] === 'success') {
                $args['meta_key']   = LogModel::META_NOTIF_SUCCESS;
                $args['meta_value'] = true;
            } elseif ($filters['status'] === 'error') {
                $args['meta_key']   = LogModel::META_NOTIF_SUCCESS;
                $args['meta_value'] = false;
            }
        }

        if (isset($filters['workflow_id'])) {
            $args['meta_query'] = [
                [
                    'key'   => LogModel::META_NOTIF_WORKFLOW_ID,
                    'value' => (int)$filters['workflow_id'],
                ],
            ];
        }

        if (isset($filters['workflow_action']) && !empty($filters['workflow_action'])) {
            $args['meta_query'] = [
                [
                    'key'   => LogModel::META_NOTIF_ACTION,
                    'value' => $filters['workflow_action'],
                ],
            ];
        }

        if (isset($filters['channel']) && !empty($filters['channel'])) {
            $args['meta_query'] = [
                [
                    'key'   => LogModel::META_NOTIF_CHANNEL,
                    'value' => $filters['channel'],
                ],
            ];
        }

        if (isset($filters['receiver']) && !empty($filters['receiver'])) {
            $args['meta_query'] = [
                [
                    'key'     => LogModel::META_NOTIF_RECEIVER,
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
