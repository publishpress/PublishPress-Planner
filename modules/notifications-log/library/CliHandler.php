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

use WP_CLI;

/**
 * Class NotificationsLogHandler
 *
 * @package PublishPress\NotificationsLog
 */
class CliHandler
{
    public function __construct()
    {
        \WP_CLI::add_command(
            'publishpress-notifications',
            [$this, 'handleCommands'],
            [
                'shortdesc' => 'Manages content notifications',
            ]
        );
    }

    public function handleCommands($args, $assocArgs)
    {
        switch ($args[0]) {
            case 'list-actions':
                $this->listActions();
                break;

            case 'notify':
                $this->notify($assocArgs);
                break;

            default:
                $this->halt('Command not found');
        }
    }

    private function listActions()
    {
        $actions = apply_filters('publishpress_notifications_workflow_events', []);

        foreach ($actions as $action) {
            WP_CLI::line($action);
        }

        WP_CLI::success('Done');
    }

    private function halt($message)
    {
        WP_CLI::error($message);
        WP_CLI::halt(1);
    }

    private function notify($assocArgs)
    {
        try {
            $requiredArguments = ['post_id', 'action', 'user_id'];

            foreach ($requiredArguments as $requiredArgument) {
                if (!key_exists($requiredArgument, $assocArgs)) {
                    $this->halt('Missed argument: ' . $requiredArgument);
                }
            }

            $post = get_post((int)$assocArgs['post_id']);
            if (empty($post) || is_wp_error($post) || empty($assocArgs['post_id'])) {
                $this->halt('Post not found');
            }

            if ($assocArgs['action'] === 'editorial_comment') {
                $this->create_editorial_comment_and_notify($assocArgs);
            }

            WP_CLI::success('Notification created');
        } catch (\Exception $e) {
            $this->halt('Exception: ' . $e->getMessage());
        }
    }

    private function create_editorial_comment_and_notify($assocArgs)
    {
        $current_user = get_user_by('ID', (int)$assocArgs['user_id']);

        $comment_content = 'This is a fake comment added by the cli script for tests purpose';

        // Set comment data
        $data = [
            'comment_post_ID'      => (int)$assocArgs['post_id'],
            'comment_author'       => esc_sql($current_user->display_name),
            'comment_author_email' => esc_sql($current_user->user_email),
            'comment_author_url'   => esc_sql($current_user->user_url),
            'comment_content'      => $comment_content,
            'comment_type'         => \PP_Editorial_Comments::comment_type,
            'comment_parent'       => 0,
            'user_id'              => $current_user->ID,
            'comment_author_IP'    => '0.0.0.0',
            'comment_agent'        => 'WP_Cli/Publishpress-notifications',
            'comment_date'         => date('Y-m-d H:i:s'),
            'comment_date_gmt'     => gmdate('Y-m-d H:i:s'),
            // Set to -1?
            'comment_approved'     => \PP_Editorial_Comments::comment_type,
        ];

        // Insert Comment
        $comment_id = wp_insert_comment($data);
        $comment    = get_comment($comment_id);

        // Register actions -- will be used to set up notifications and other modules can hook into this
        if ($comment_id) {
            do_action('pp_post_insert_editorial_comment', $comment);
            WP_CLI::success('New comment created');
        }
    }
}
