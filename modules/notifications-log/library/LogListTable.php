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

use PublishPress\Notifications\Traits\Dependency_Injector;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Class LogListTable
 *
 * @package PublishPress\NotificationsLog
 */
class LogListTable extends \WP_List_Table
{
    use Dependency_Injector;

    const POSTS_PER_PAGE = 20;

    const BULK_ACTION_DELETE = 'delete';

    const BULK_ACTION_DELETE_ALL = 'delete_all';

    /**
     * @var LogHandler
     */
    private $logHandler;

    /**
     * LogListTable constructor.
     *
     * @param $logHandler
     */
    public function __construct($logHandler)
    {
        global $status, $page;

        //Set parent defaults
        parent::__construct([
            'singular' => 'log',     //singular name of the listed records
            'plural'   => 'log',    //plural name of the listed records
            'ajax'     => false        //does this table support ajax?
        ]);

        $this->logHandler = $logHandler;
    }


    /**
     * @param object $item
     * @param string $column_name
     *
     * @return mixed|string|void
     */
    public function column_default($item, $column_name)
    {
        $log = new LogModel($item);

        switch ($column_name) {
            case 'date':
                $output = $log->date;
                break;

            case 'action':
                $output = $log->action;
                break;

            case 'post':
                $output = $log->postTitle;
                break;

            case 'workflow':
                $output = $log->workflowTitle;
                break;

            case 'receiver':
                if ($log->receiverIsUser()) {
                    $output = $log->receiverName . '&nbsp;<span class="user-id">(id:' . $log->receiver . ')</span>';
                } else {
                    $output = $log->receiver;
                }
                break;

            case 'channel':
                $output = $log->channel;
                break;

            case 'status':
                if ($log->success) {
                    $output = '<i class="dashicons dashicons-yes-alt"></i>';
                } else {
                    $output = '<i class="dashicons dashicons-no"></i>';

                    if (!empty($log->error)) {
                        $output .= '<span class="error"> - ' . $log->error . '</span>';
                    }
                }
                $output .= $log->async ? __(' (Scheduled in the cron)', 'publishpress') : '';

                break;

            case 'async':
                $output = $log->async ? __('Yes', 'publishpress') : __('No', 'publishpress');
                break;

            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }

        return $output;
    }

    /**
     * @param object $item
     *
     * @return string|void
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item->comment_ID
        );
    }

    /**
     * @param $item
     *
     * @return string
     */
    public function column_date($item)
    {
        //Build row actions
        $actions = [
            'view'   => sprintf('<a href="#" class="view-log" data-id="%s">%s</a>',
                $item->comment_ID,
                __('View', 'publishpress')
            ),
            'delete' => sprintf('<a href="%s">%s</a>',
                esc_url(
                    add_query_arg([
                        'action'                        => 'delete',
                        $this->_args['singular'] . '[]' => $item->comment_ID,
                    ])
                ),
                __('Delete', 'publishpress')
            ),
        ];

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            $item->comment_date,
            $item->comment_ID,
            $this->row_actions($actions)
        );
    }

    /**
     * @return array
     */
    public function get_columns()
    {
        $columns = [
            'cb'       => '<input type="checkbox" />',
            'date'     => __('Date', 'publishpress'),
            'post'     => __('Post', 'publishpress'),
            'workflow' => __('Workflow', 'publishpress'),
            'action'   => __('Action', 'publishpress'),
            'receiver' => __('Receiver', 'publishpress'),
            'channel'  => __('Channel', 'publishpress'),
            'status'   => __('Status', 'publishpress'),
        ];

        return $columns;
    }


    /**
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = [
            'date' => ['date', true],
        ];

        return $sortable_columns;
    }


    /**
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            self::BULK_ACTION_DELETE     => 'Delete',
            self::BULK_ACTION_DELETE_ALL => 'Delete All',
        ];

        return $actions;
    }

    protected function get_views()
    {
        return [
            'all'     => '<a href="' . esc_url(add_query_arg('status', 'all')) . '">' . __('All',
                    'publishpress') . '</a>',
            'success' => '<a href="' . esc_url(add_query_arg('status', 'success')) . '">' . __('Success',
                    'publishpress') . '</a>',
            'error'   => '<a href="' . esc_url(add_query_arg('status', 'error')) . '">' . __('Errors',
                    'publishpress') . '</a>',
        ];
    }


    public function process_bulk_action()
    {
        if (self::BULK_ACTION_DELETE === $this->current_action()) {
            $ids = isset($_GET['log']) ? (array)$_GET['log'] : [];

            if (!empty($ids)) {
                foreach ($ids as $id) {
                    wp_delete_comment($id, true);
                }
            }
        } elseif (self::BULK_ACTION_DELETE_ALL === $this->current_action()) {
            $notifications = $this->logHandler->getNotifications(null, 'comment_date', 'desc', false, [], null, null);

            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    wp_delete_comment($notification->comment_ID, true);
                }
            }
        }
    }


    public function prepare_items()
    {
        $currentUserId = get_current_user_id();
        $per_page      = get_user_meta($currentUserId, 'logs_per_page', true);
        if (empty($per_page)) {
            $per_page = self::POSTS_PER_PAGE;
        }

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->process_bulk_action();

        $current_page = $this->get_pagenum();

        $filters = [];
        if (isset($_REQUEST['status'])) {
            $filters['status'] = sanitize_text_field($_REQUEST['status']);
        }

        if (isset($_REQUEST['s'])) {
            $filters['search'] = sanitize_text_field($_REQUEST['s']);
        }

        if (isset($_REQUEST['workflow_id'])) {
            $filters['workflow_id'] = (int)$_REQUEST['workflow_id'];
        }

        $postId = null;
        if (isset($_REQUEST['post_id'])) {
            $postId = (int)$_REQUEST['post_id'];
        }

        if (isset($_REQUEST['workflow_action'])) {
            $filters['workflow_action'] = sanitize_text_field($_REQUEST['workflow_action']);
        }

        if (isset($_REQUEST['channel'])) {
            $filters['channel'] = sanitize_text_field($_REQUEST['channel']);
        }

        if (isset($_REQUEST['date_begin']) && isset($_REQUEST['date_end'])) {
            $filters['date_begin'] = sanitize_text_field($_REQUEST['date_begin']);
            $filters['date_end']   = sanitize_text_field($_REQUEST['date_end']);
        }

        if (isset($_REQUEST['receiver'])) {
            $filters['receiver'] = sanitize_text_field($_REQUEST['receiver']);
        }

        $total_items = $this->logHandler->getNotifications($postId, null, null, true, $filters);

        $orderBy = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'comment_date'; //If no sort, default to title
        $order   = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc

        $this->items = $this->logHandler->getNotifications($postId, $orderBy, $order, false, $filters, $per_page,
            $current_page);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    /**
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @param string $which
     *
     * @since 3.1.0
     *
     */
    protected function extra_tablenav($which)
    {
        if ('top' !== $which) {
            return;
        }

        // Post
        $postId         = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        $selectedOption = '';
        if (!empty($postId)) {
            $post = get_post($postId);

            $selectedOption = '<option selected="selected" value="' . esc_attr($postId) . '">' . $post->post_title . '</option>';
        }

        echo '<select class="filter-posts" name="post_id">' . $selectedOption . '</select>';

        // Workflow
        $workflowId     = isset($_GET['workflow_id']) ? (int)$_GET['workflow_id'] : 0;
        $selectedOption = '';
        if (!empty($workflowId)) {
            $workflow = get_post($workflowId);

            $selectedOption = '<option selected="selected" value="' . esc_attr($workflowId) . '">' . $workflow->post_title . '</option>';
        }

        echo '<select class="filter-workflows" name="workflow_id">' . $selectedOption . '</select>';

        // Action
        $selectedAction = isset($_GET['action']) ? $_GET['action'] : '';

        echo '<select class="filter-actions" name="workflow_action">';
        $actions = apply_filters('publishpress_notif_workflow_actions', []);

        echo '<option value="">' . __('All actions', 'publishpress') . '</option>';

        foreach ($actions as $action) {
            echo '<option ' . selected($action,
                    $selectedAction) . ' value="' . esc_attr($action) . '">' . $action . '</option>';
        }
        echo '</select>';

        // Channel
        $selectedChannel = isset($_GET['channel']) ? $_GET['channel'] : '';

        echo '<select class="filter-channels" name="channel">';
        $channels = apply_filters('psppno_filter_channels', []);

        echo '<option value="">' . __('All channels', 'publishpress') . '</option>';

        foreach ($channels as $channel) {
            echo '<option ' . selected($channel->name,
                    $selectedChannel) . ' value="' . esc_attr($channel->name) . '">' . $channel->label . '</option>';
        }
        echo '</select>';

        echo '<br clear="all">';

        $dateBegin = isset($_GET['date_begin']) ? sanitize_text_field($_GET['date_begin']) : '';
        $dateEnd   = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '';

        echo '<div class="filter-2nd-line">';
        echo '<span class="filter-dates">';
        echo '<input type="text" class="filter-date-begin" name="date_begin" value="' . esc_attr($dateBegin) . '" placeholder="' . __('From date',
                'publishpress') . '" />&nbsp;';
        echo '&nbsp;<input type="text" class="filter-date-end" name="date_end" value="' . esc_attr($dateEnd) . '" placeholder="' . __('To date',
                'publishpress') . '" />&nbsp;';
        echo '</span>';

        // Receiver
        $receiver = isset($_GET['receiver']) ? sanitize_text_field($_GET['receiver']) : '';
        echo '<input type="text" placeholder="' . __('All Receivers',
                'publishpress') . '" name="receiver" value="' . esc_attr($receiver) . '" />';


        echo submit_button(__('Filter', 'publishpress'), 'secondary', 'submit', false);

        echo '</div>';
        echo '<br>';
    }
}
