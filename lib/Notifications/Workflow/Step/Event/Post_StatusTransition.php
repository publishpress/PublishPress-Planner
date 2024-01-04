<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event;

use PublishPress\Notifications\Workflow\Step\Event\Filter;

class Post_StatusTransition extends Base
{
    const META_KEY_SELECTED = '_psppno_evtpostsave';

    const META_VALUE_SELECTED = 'post_save';

    const EVENT_NAME = 'transition_post_status';

    /**
     * The constructorPost_StatusTransition'
     */
    public function __construct()
    {
        $this->name  = static::META_VALUE_SELECTED;
        $this->label = __('When the content is moved to a new status', 'publishpress');

        parent::__construct();

        // Add filter to return the metakey representing if it is selected or not
        add_filter('psppno_events_metakeys', [$this, 'filter_events_metakeys']);
        add_filter('publishpress_notifications_workflow_events', [$this, 'filter_workflow_actions']);
        add_filter('publishpress_notifications_action_params_for_log', [$this, 'filter_action_params_for_log'], 10, 2);
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

        if (static::EVENT_NAME === $event_args['event']) {
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

    /**
     * Method to return a list of fields to display in the filter area
     *
     * @param array
     *
     * @return array
     */
    protected function get_filters($filters = [])
    {
        if (!empty($this->cache_filters)) {
            return $this->cache_filters;
        }

        $step_name = $this->attr_prefix . '_' . $this->name;

        $filters[] = new Filter\Post_Status($step_name);

        return parent::get_filters($filters);
    }

    public function filter_workflow_actions($actions)
    {
        if (!is_array($actions) || empty($actions)) {
            $actions = [];
        }

        $actions[] = static::EVENT_NAME;

        return $actions;
    }

    public function filter_action_params_for_log($paramsString, $log)
    {
        if ($log->event === static::EVENT_NAME) {
            global $publishpress;

            $oldStatus = $publishpress->getPostStatusBy('slug', $log->oldStatus);
            $newStatus = $publishpress->getPostStatusBy('slug', $log->newStatus);

            $paramsString = '';
            if (is_object($oldStatus)) {
                $paramsString .= '<div>' . sprintf(__('Old post status: %s', 'publishpress'), $oldStatus->label) . '</div>';
            }

            if (is_object($newStatus)) {
                $paramsString .= '<div>' . sprintf(__('New post status: %s', 'publishpress'), $newStatus->label) . '</div>';
            }
        }

        return $paramsString;
    }

    /**
     * @param string $label
     * @param string $event
     * @return string|void
     */
    public function filter_event_label($label, $event)
    {
        if ($event === static::EVENT_NAME) {
            $label = $this->label;
        }

        return $label;
    }
}
