<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event\Filter;

use Exception;
use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\Metadata;
use PublishPress\Notifications\Traits\PublishPress_Module;

class Base implements Filter_Interface
{
    use Dependency_Injector, PublishPress_Module, Metadata;

    /**
     * The name of the filter and field
     */
    protected $step_name;

    /**
     * The constructor.
     *
     * @param string $step_name
     */
    public function __construct($step_name)
    {
        $this->step_name = $step_name;

        // Add the action to save the metabox data
        add_action('publishpress_notif_save_workflow_metadata', [$this, 'save_metabox_data'], 11, 2);
    }

    /**
     * Function to render and returnt the HTML markup for the
     * Field in the form.
     *
     * @return string
     *
     * @throws Exception
     */
    public function render()
    {
        throw new Exception('The method ' . __CLASS__ . '::render have to be defined in the child class');
    }

    /**
     * Function to save the metadata from the metabox
     *
     * @param int $id
     * @param WP_Post $post
     */
    public function save_metabox_data($id, $post)
    {
        return;
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
    public function get_run_workflow_query_args($query_args, $event_args)
    {
        return $query_args;
    }
}
