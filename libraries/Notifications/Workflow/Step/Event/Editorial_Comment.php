<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event;

use PublishPress\Notifications\Workflow\Filter;

class Editorial_Comment extends Base
{
    const META_KEY_SELECTED = '_psppno_evtedcomment';

    const META_VALUE_SELECTED = 'editorial_comment';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name  = 'editorial_comment';
        $this->label = __('When an editorial comment is added', 'publishpress');

        parent::__construct();

        // Add filter to return the metakey representing if it is selected or not
        add_filter('psppno_events_metakeys', [$this, 'filter_events_metakeys']);
        add_filter('publishpress_notif_workflow_actions', [$this, 'filter_workflow_actions']);
        add_filter('publishpress_notifications_action_params_for_log', [$this, 'filter_action_params_for_log'], 10, 2);
    }

    /**
     * Filters and returns the arguments for the query which locates
     * workflows that should be executed.
     *
     * @param array $query_args
     * @param array $event_args
     *
     * @return array
     */
    public function filter_running_workflow_query_args($query_args, $event_args)
    {
        if ($this->should_ignore_event_on_query($event_args)) {
            return $query_args;
        }

        if ('editorial_comment' === $event_args['event']) {
            $query_args['meta_query'][] = [
                'key'     => static::META_KEY_SELECTED,
                'value'   => 1,
                'type'    => 'BOOL',
                'compare' => '=',
            ];

            // Check the filters
            $filters = $this->get_filters();

            foreach ($filters as $filter) {
                $query_args = $filter->get_run_workflow_query_args($query_args, $event_args);
            }
        }

        return $query_args;
    }

    public function filter_workflow_actions($actions)
    {
        if (!is_array($actions) || empty($actions)) {
            $actions = [];
        }

        $actions[] = 'editorial_comment';

        return $actions;
    }

    public function filter_action_params_for_log($paramsString, $log)
    {
        if ($log->event === self::META_VALUE_SELECTED) {
            $paramsString = sprintf(
                __('Comment: %d', 'publishpress'),
                $log->commentId
            );
        }

        return $paramsString;
    }
}
