<?php
/**
 * File responsible for defining basic plugin class
 *
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications;

defined('ABSPATH') or die('No direct script access allowed.');

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;

class Plugin
{
    use Dependency_Injector, PublishPress_Module;

    /**
     * The method which runs the plugin
     */
    public function init()
    {
        add_action('load-edit.php', [$this, 'add_load_edit_hooks']);

        add_filter('post_updated_messages', [$this, 'filter_post_updated_messages']);
        add_filter('bulk_post_updated_messages', [$this, 'filter_bulk_post_updated_messages'], 10, 2);

        if (is_admin()) {
            add_filter('posts_clauses_request', [$this, 'fltSuppressInactiveNotifications'], 50, 2);
            add_filter('wp_count_posts', [$this, 'fltSuppressInactivePostCounts'], 50, 2);
        }

        add_filter('views_edit-psppnotif_workflow', [$this, 'fltRemoveWorkflowsMineLink']);
    }

    public function add_load_edit_hooks()
    {
        $post_type = 'psppnotif_workflow';
        $screen    = get_current_screen();

        if (!isset($screen->id)) {
            return;
        }

        if ("edit-$post_type" !== $screen->id) {
            return;
        }

        add_filter("manage_{$post_type}_posts_columns", [$this, 'filter_manage_post_columns']);

        add_action("manage_{$post_type}_posts_custom_column", [$this, 'action_manage_post_custom_column'], 10, 2);
    }

    public function filter_manage_post_columns($post_columns)
    {
        // Remove the Date column.
        unset($post_columns['date']);

        $post_columns['events']    = __('When to notify?', 'publishpress');
        $post_columns['filter']    = __('Filter the content?', 'publishpress');
        $post_columns['receivers'] = __('Who to notify?', 'publishpress');

        return $post_columns;
    }

    public function action_manage_post_custom_column($column_name, $post_id)
    {
        $columns = [
            'events',
            'filter',
            'receivers',
        ];
        // Ignore other columns
        if (!in_array($column_name, $columns)) {
            return;
        }

        $method_name = 'print_column_' . $column_name;
        $this->$method_name($post_id);
    }

    function fltSuppressInactiveNotifications($clauses, $_wp_query = false, $args = [])
    {
        global $wpdb, $pagenow, $typenow;

        if (!empty($pagenow) && ('edit.php' == $pagenow) && !empty($typenow) && ('psppnotif_workflow' == $typenow)) {
            if (!defined('PUBLISHPRESS_REVISIONS_PRO_VERSION')) {
                $clauses['where'] .= " AND $wpdb->posts.post_name NOT IN ('revision-scheduled-publication', 'scheduled-revision-is-published', 'revision-scheduled', 'revision-is-scheduled', 'revision-declined', 'revision-deferred-or-rejected', 'revision-submission', 'revision-is-submitted', 'new-revision', 'new-revision-created', 'revision-status-changed', 'revision-is-applied', 'revision-is-published')";
            }
            
            if (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && !defined('PUBLISHPRESS_RETAIN_DEFAULT_STATUS_CHANGED_WORKFLOW')) {
                $clauses['where'] .= " AND $wpdb->posts.post_name NOT IN ('post-status-changed', 'post-deferred-or-rejected', 'post-declined')";
            }
        }

        return $clauses;
    }

    function fltSuppressInactivePostCounts($counts, $type, $perm = '') {
        global $wpdb, $pagenow;

        if (
            ('psppnotif_workflow' == $type) && !empty($pagenow) && ('edit.php' == $pagenow)
            && (!defined('PUBLISHPRESS_REVISIONS_PRO_VERSION') || !defined('PUBLISHPRESS_STATUSES_PRO_VERSION'))
        ) {
            $query = "SELECT post_status, COUNT(*) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";

            if (!defined('PUBLISHPRESS_REVISIONS_PRO_VERSION')) {
                $query .= " AND $wpdb->posts.post_name NOT IN ('revision-scheduled-publication', 'scheduled-revision-is-published', 'revision-scheduled', 'revision-is-scheduled', 'revision-declined', 'revision-deferred-or-rejected', 'revision-submission', 'revision-is-submitted', 'new-revision', 'new-revision-created', 'revision-status-changed', 'revision-is-applied', 'revision-is-published')";
            }
            
            if (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && !defined('PUBLISHPRESS_RETAIN_DEFAULT_STATUS_CHANGED_WORKFLOW')) {
                $query .= " AND $wpdb->posts.post_name NOT IN ('post-status-changed', 'post-deferred-or-rejected', 'post-declined')";
            }

            $query .= ' GROUP BY post_status';
        
            $results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
            $counts  = array_fill_keys( get_post_stati(), 0 );
        
            foreach ( $results as $row ) {
                $counts[ $row['post_status'] ] = $row['num_posts'];
            }
        
            $counts = (object) $counts;
        }
    
        return $counts;
    }

    function fltRemoveWorkflowsMineLink($view_links) {
        unset($view_links['mine']);

        return $view_links;
    }
    
    /**
     * Customize the post messages for the notification workflows when the
     * content is saved.
     *
     * @param string $messages
     *
     * @return array
     */
    public function filter_post_updated_messages($messages)
    {
        global $current_screen;

        if (PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW !== $current_screen->post_type) {
            return $messages;
        }

        $post = get_post();

        /* translators: Publish box date format, see https://secure.php.net/date */
        $scheduled_date = date_i18n(__('M j, Y @ H:i'), strtotime($post->post_date));

        $messages['post'][1]  = __('Notification workflow updated.', 'pulishpress');
        $messages['post'][4]  = __('Notification workflow updated.', 'pulishpress');
        $messages['post'][6]  = __('Notification workflow published.', 'pulishpress');
        $messages['post'][7]  = __('Notification workflow saved.', 'pulishpress');
        $messages['post'][8]  = __('Notification workflow submitted.', 'pulishpress');
        $messages['post'][9]  = sprintf(
            __('Notification workflow scheduled for: %s.'),
            '<strong>' . $scheduled_date . '</strong>'
        );
        $messages['post'][10] = __('Notification workflow draft updated.', 'pulishpress');

        return $messages;
    }

    /**
     * Customize the post messages for the notification workflows when the
     * content is bulk edited.
     *
     * @param string $bulk_messages
     * @param int $bulk_contents
     *
     * @return array
     */
    public function filter_bulk_post_updated_messages($bulk_messages, $bulk_counts)
    {
        global $current_screen;

        if (PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW !== $current_screen->post_type) {
            return $bulk_messages;
        }

        $bulk_messages['post']['updated']   = _n(
            '%s notification workflow updated.',
            '%s notification workflows updated.',
            $bulk_counts['updated']
        );
        $bulk_messages['post']['locked']    = (1 == $bulk_counts['locked']) ? __(
            '1 notification workflow not updated, somebody is editing it.'
        ) :
            _n(
                '%s notification workflow not updated, somebody is editing it.',
                '%s notification workflows not updated, somebody is editing them.',
                $bulk_counts['locked']
            );
        $bulk_messages['post']['deleted']   = _n(
            '%s notification workflow permanently deleted.',
            '%s notification workflows permanently deleted.',
            $bulk_counts['deleted']
        );
        $bulk_messages['post']['trashed']   = _n(
            '%s notification workflow moved to the Trash.',
            '%s notification workflows moved to the Trash.',
            $bulk_counts['trashed']
        );
        $bulk_messages['post']['untrashed'] = _n(
            '%s notification workflow restored from the Trash.',
            '%s notification workflows restored from the Trash.',
            $bulk_counts['untrashed']
        );

        return $bulk_messages;
    }

    /**
     * Print the column for the events
     *
     * @param int $post_id
     */
    protected function print_column_events($post_id)
    {
        /**
         * Get the event metakeys
         *
         * @param array $metakeys
         */
        $metakeys = apply_filters('psppno_events_metakeys', []);
        $events   = [];

        foreach ($metakeys as $metakey => $label) {
            $selected = get_post_meta($post_id, $metakey, true);

            if ($selected) {
                $events[] = $label;
            }
        }

        if (empty($events)) {
            echo '<span class="psppno_no_events_warning">' . __(
                    'Please select at least one event',
                    'publishpress'
                ) . '</span>';
        } else {
            echo implode(', ', $events);
        }
    }

    /**
     * Print the column for the filters
     *
     * @param int $post_id
     */
    protected function print_column_filter($post_id)
    {
        /**
         * Get the event metakeys
         *
         * @param array $metakeys
         */
        $metakeys = apply_filters('psppno_filter_metakeys', []);
        $filters  = [];

        foreach ($metakeys as $metakey => $label) {
            $selected = get_post_meta($post_id, $metakey, true);

            if ($selected) {
                $filters[] = $label;
            }
        }

        if (empty($filters)) {
            echo '<span class="psppno_no_filter_warning">' . __('Not filtered', 'publishpress') . '</span>';
        } else {
            echo implode(', ', $filters);
        }
    }

    /**
     * Print the column for the receivers
     *
     * @param int $post_id
     */
    protected function print_column_receivers($post_id)
    {
        /**
         * Get the values to display in the column
         *
         * @param array $values
         * @param int $post_id
         */
        $values = apply_filters('psppno_receivers_column_value', [], $post_id);

        if (empty($values)) {
            echo '-';
        } else {
            echo implode(', ', $values);
        }
    }
}
