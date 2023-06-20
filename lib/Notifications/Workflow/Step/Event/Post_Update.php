<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event;

class Post_Update extends Base
{
    const META_KEY_SELECTED = '_psppno_evtpostupdate';

    const META_VALUE_SELECTED = 'post_update';

    const EVENT_NAME = 'post_update';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name  = static::EVENT_NAME;
        $this->label = __('When the content is updated', 'publishpress');

        parent::__construct();

        // Add filter to return the metakey representing if it is selected or not
        add_filter('psppno_events_metakeys', [$this, 'filter_events_metakeys']);
        add_filter('publishpress_notifications_workflow_events', [$this, 'filter_workflow_actions']);
        add_filter('publishpress_notifications_event_label', [$this, 'filter_event_label'], 10, 2);
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

        if (self::EVENT_NAME === $event_args['event']) {
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

        $actions[] = self::EVENT_NAME;

        return $actions;
    }

    /**
     * @param string $label
     * @param string $event
     * @return string|void
     */
    public function filter_event_label($label, $event)
    {
        if ($event === self::EVENT_NAME) {
            $label = $this->label;
        }

        return $label;
    }
}
