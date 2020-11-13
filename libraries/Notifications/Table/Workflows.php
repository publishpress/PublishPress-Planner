<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Table;

use WP_Query;

class Workflows extends Base
{
    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
    public function __construct()
    {
        parent::__construct(
            [
                'singular' => 'pp_notification_list_workflow',
                'plural'   => 'pp_notification_list_workflows',
                'ajax'     => false,
            ]
        );
    }

    /**
     * Add extra markup in the toolbars before or after the list
     *
     * @param string $which , helps you decide if you add the markup after (bottom) or before (top) the list
     */
    public function extra_tablenav($which)
    {
        if ($which == "top") {
            //The code that goes before the table is here
        }

        if ($which == "bottom") {
            //The code that goes after the table is there
        }
    }

    /**
     * Handles the post date column output.
     *
     * @param WP_Post $post The current WP_Post object.
     * @global string $mode List table view mode.
     *
     */
    public function column_post_date($post)
    {
        if ('0000-00-00 00:00:00' === $post->post_date) {
            $t_time = $h_time = __('Undefined');
        } else {
            $t_time = get_the_time(__('Y/m/d g:i:s a'));
            $m_time = $post->post_date;
            $time   = get_post_time('G', true, $post);

            $time_diff = time() - $time;

            if ($time_diff > 0 && $time_diff < DAY_IN_SECONDS) {
                $h_time = sprintf(__('%s ago'), human_time_diff($time));
            } else {
                $h_time = mysql2date(__('Y/m/d'), $m_time);
            }
        }

        /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
        echo '<abbr title="' . esc_attr($t_time) . '">' . $h_time . '</abbr>';
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    public function prepare_items()
    {
        global $wpdb;

        $this->_column_headers = [
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
        ];

        /* -- Ordering parameters -- */
        // Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? $wpdb->_real_escape($_GET["orderby"]) : '';
        $order   = !empty($_GET["order"]) ? $wpdb->_real_escape($_GET["order"]) : 'DESC';

        $posts_per_page = 10;

        $args = [
            'post_type'      => PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
            'posts_per_page' => $posts_per_page,
            'order'          => $order,
            'orderby'        => $orderby,
        ];

        $query = new WP_Query($args);

        /* -- Pagination parameters -- */
        $totalitems = $query->post_count;

        // Which page is this?
        $paged = !empty($_GET["paged"]) ? $wpdb->_real_escape($_GET["paged"]) : '';

        // Page Number
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }

        // How many pages do we have in total?
        $totalpages = ceil($totalitems / $posts_per_page);

        /* -- Register the pagination -- */
        $this->set_pagination_args(
            [
                'total_items' => $totalitems,
                'total_pages' => $totalpages,
                'per_page'    => $posts_per_page,
                'offset'      => (int)$offset,
            ]
        );

        /* -- Fetch the items -- */
        $this->items = $query->posts;
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @return array $columns, the array of columns to use with the table
     */
    public function get_columns()
    {
        return [
            'cb'         => 'cb',
            'post_title' => __('Title', 'publishpress'),
            'ID'         => __('ID', 'publishpress'),
        ];
    }

    /**
     * Decide which columns to hide
     *
     * @return array $hidden, the array of columns that will be hidden
     */
    public function get_hidden_columns()
    {
        return [
            'ID',
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns()
    {
        return [
            'ID'         => ['ID', true],
            'post_title' => ['post_title', true],
            'post_date'  => ['post_date', true],
        ];
    }

    /**
     * Method to return the content of the columns
     *
     * @param WP_Post $item
     * @param string $column_name
     *
     * @return string
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'ID':
            case 'post_title':
                return $item->$column_name;
            default:
                //Show the whole array for troubleshooting purposes
                return print_r($item, true);
        }
    }

    /**
     * Handles the checkbox column output.
     *
     * @param object $post The current post object.
     */
    public function column_cb($post)
    {
        ?>
        <label class="screen-reader-text"
               for="cb-select-<?php echo esc_attr($post->ID); ?>"><?php echo sprintf(
                __('Select %s'),
                $post->post_title
            ); ?></label>
        <input type="checkbox" name="linkcheck[]" id="cb-select-<?php echo esc_attr($post->ID); ?>"
               value="<?php echo esc_attr($post->ID); ?>"/>
        <?php
    }


    /**
     * Message to be displayed when there are no items
     */
    public function no_items()
    {
        _e('No workflows found.', 'publishpress');
    }
}
