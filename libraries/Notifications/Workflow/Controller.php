<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow;

class Controller
{
    /**
     * Store the signatures of sent notifications, usually to avoid duplicated notifications
     * to the same channel.
     *
     * @var array
     */
    protected $sent_notification_signatures = [];

    /**
     * List of receivers by channel
     *
     * @var array
     */
    protected $receivers_by_channel = [];

    /**
     * The constructor
     */
    public function __construct()
    {
        add_action('publishpress_notif_run_workflows', [$this, 'run_workflows'], 10);
    }

    /**
     * Look for enabled workflows, filtering and running according to each settings.
     *
     * $args = [
     *     'action',
     *     'post',
     *     'new_status',
     *     'old_status',
     * ]
     *
     * @param array $args
     */
    public function run_workflows($args)
    {
        $workflows = $this->get_filtered_workflows($args);

        foreach ($workflows as $workflow) {
            $workflow->run($args);
        }
    }

    /**
     * Returns a list of published workflows which passed all filters.
     *
     * $args = [
     *     'post',
     *     'new_status',
     *     'old_status',
     * ]
     *
     * @param array $args
     *
     * @return array
     */
    public function get_filtered_workflows($args)
    {
        $workflows = [];

        // Build the query
        $query_args = [
            'nopaging'      => true,
            'post_type'     => PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
            'post_status'   => 'publish',
            'no_found_rows' => true,
            'cache_results' => true,
            'meta_query'    => [],
        ];

        /**
         * Filters the arguments sent to the query to get workflows and
         * each step's filters.
         *
         * @param array $query_args
         * @param array $args
         */
        $query_args = apply_filters('publishpress_notif_run_workflow_meta_query', $query_args, $args);

        $query = new \WP_Query($query_args);

        if ( ! empty($query->posts)) {
            foreach ($query->posts as $post) {
                $workflows[] = new Workflow($post);
            }
        }

        return $workflows;
    }

    /**
     * Loads instantiating the classes for the workflow steps.
     */
    public function load_workflow_steps()
    {
        // When
        $classes_event = [
            '\\PublishPress\\Notifications\\Workflow\\Step\\Event\\Editorial_Comment',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Event\\Post_Save',
        ];
        /**
         * Filters the list of classes to define workflow "when" steps.
         *
         * @param array $classes The list of classes to be loaded
         */
        $classes_event = apply_filters('publishpress_notif_workflow_steps_event', $classes_event);

        // Which Content
        $classes_event_content = [
            '\\PublishPress\\Notifications\\Workflow\\Step\\Event_Content\\Post_Type',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Event_Content\\Category',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Event_Content\\Taxonomy',
        ];
        /**
         * Filters the list of classes to define workflow "when - which content" steps.
         *
         * @param array $classes The list of classes to be loaded
         */
        $classes_event_content = apply_filters('publishpress_notif_workflow_steps_event_content',
            $classes_event_content);

        // Who
        $classes_receiver = [
            '\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\Site_Admin',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\Author',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\User',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\Role',
            '\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\Follower',
        ];
        /**
         * Filters the list of classes to define workflow "who" steps.
         *
         * @param array $classes The list of classes to be loaded
         */
        $classes_receiver = apply_filters('publishpress_notif_workflow_steps_receiver', $classes_receiver);

        // Where
        $classes_channel = [
            '\\PublishPress\\Notifications\\Workflow\\Step\\Channel\\Email',
        ];
        /**
         * Filters the list of classes to define workflow "where" steps.
         *
         * @param array $classes The list of classes to be loaded
         */
        $classes_channel = apply_filters('publishpress_notif_workflow_steps_channel', $classes_channel);

        // What
        $classes_content = [
            '\\PublishPress\\Notifications\\Workflow\\Step\\Content\\Main',
        ];
        /**
         * Filters the list of classes to define workflow "what" steps.
         *
         * @param array $classes The list of classes to be loaded
         */
        $classes_content = apply_filters('publishpress_notif_workflow_steps_content', $classes_content);

        $classes = array_merge($classes_event, $classes_event_content, $classes_receiver, $classes_channel,
            $classes_content);

        // Instantiate each class
        foreach ($classes as $class) {
            if (class_exists($class)) {
                new $class;
            }
        }
    }

    /**
     * @param $signature
     */
    public function register_notification_signature($signature)
    {
        $this->sent_notification_signatures[$signature] = true;
    }

    /**
     * @param $signature
     *
     * @return bool
     */
    public function is_notification_signature_registered($signature)
    {
        $found = array_key_exists($signature, $this->sent_notification_signatures);

        return $found;
    }
}
