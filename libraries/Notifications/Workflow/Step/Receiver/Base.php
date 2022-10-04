<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

use PublishPress\Notifications\Workflow\Step\Base as Base_Step;

class Base extends Base_Step
{
    const META_KEY_SELECTED = '_psppno_toundefined';

    protected $view_name;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->attr_prefix = 'receiver';

        parent::__construct();

        // Add the event filters to the metabox template
        add_filter(
            "publishpress_notif_workflow_metabox_context_{$this->attr_prefix}_{$this->name}",
            [$this, 'filter_workflow_metabox_context']
        );

        // Add the filter for the list of receivers in the workflow
        add_filter("publishpress_notif_run_workflow_receivers", [$this, 'filter_workflow_receivers'], 10, 3);

        // Add filter to return the value for the column in the workflow list
        add_filter('psppno_receivers_column_value', [$this, 'filter_receivers_column_value'], 10, 2);

        add_filter('publishpress_notifications_receiver_group_label', [$this, 'filter_receiver_group_label']);
    }

    /**
     * Add the respective value to the column in the workflow list
     *
     * @param array $values
     * @param int $post_id
     *
     * @return array
     */
    public function filter_receivers_column_value($values, $post_id)
    {
        return $values;
    }

    public function filter_receiver_group_label($group_name)
    {
        if ($group_name === static::META_VALUE) {
            $group_name = $this->label;
        }

        return $group_name;
    }
}
