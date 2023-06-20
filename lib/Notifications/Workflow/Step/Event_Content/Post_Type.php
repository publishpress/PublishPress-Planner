<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event_Content;

class Post_Type extends Base
{
    const META_KEY_SELECTED = '_psppno_evtcontposttype';

    const META_VALUE_SELECTED = 'post_type';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name  = 'post_type';
        $this->label = __('Post type', 'publishpress');

        parent::__construct();
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
        // Check the filters
        $filters = $this->get_filters();

        foreach ($filters as $filter) {
            $query_args = $filter->get_run_workflow_query_args($query_args, $event_args);
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

        $filters[] = new Filter\Post_Type($step_name);

        return parent::get_filters($filters);
    }
}
