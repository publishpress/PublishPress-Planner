<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow;

use PublishPress\Notifications\Traits\Dependency_Injector;
use WP_Post;

class Workflow
{
    use Dependency_Injector;

    const NOTIFICATION_SCHEDULE_META_KEY = '_psppre_notification_scheduled';

    /**
     * The post of this workflow.
     *
     * @var WP_Post
     */
    public $workflow_post;

    /**
     * An array with arguments set by the action
     *
     * @var array
     */
    protected $action_args;

    /**
     * The constructor
     *
     * @param WP_Post $workflow_post
     */
    public function __construct($workflow_post)
    {
        $this->workflow_post = $workflow_post;
    }

    /**
     * Runs this workflow without applying any filter. We assume it was
     * already filtered in the query.
     *
     * @param array $args
     *
     * @throws \Exception
     */
    public function run($args)
    {
        $this->action_args = $args;

        // Who will receive the notification?
        $receivers = $this->get_receivers();

        // If we don't have receivers, abort the workflow.
        if (empty($receivers)) {
            return;
        }

        // Prepare the shortcodes.
        $shortcodes = $this->get_service('shortcodes');
        $shortcodes->register($this->workflow_post, $this->action_args);

        /**
         * @param WP_Post $workflow_post
         * @param array $action_args
         */
        do_action('publishpress_notif_before_run_workflow', $this->workflow_post, $this->action_args, $receivers);

        /*
         * What will the notification says?
         */
        $base_content = $this->get_content();

        // Run the action to each receiver.
        foreach ($receivers as $channel => $channel_receivers) {
            foreach ($channel_receivers as $receiver) {
                /**
                 * Filters the action to be executed. By default it will trigger the notification.
                 * But it can be changed to do another action. This allows to change the flow and
                 * catch the params to cache or queue for async notifications.
                 *
                 * @param string   $action
                 * @param Workflow $workflow
                 * @param string   $channel
                 */
                $action = apply_filters('publishpress_notif_workflow_run_action',
                    'publishpress_notif_send_notification_' . $channel, $this, $channel);

                /**
                 * Prepare the content replacing shortcodes.
                 */
                $content = $this->do_shortcodes_in_content($base_content, $receiver, $channel);

                /**
                 * Triggers the notification. This can be caught by notification channels.
                 * But can be intercepted by other plugins (cache, async, etc) to change the
                 * workflow.
                 *
                 * @param WP_Post $workflow_post
                 * @param array   $action_args
                 * @param array   $receiver
                 * @param array   $content
                 * @param string  $channel
                 */
                do_action($action, $this->workflow_post, $this->action_args, $receiver, $content, $channel);
            }
        }

        // Remove the shortcodes.
        $shortcodes->unregister();

        do_action('publishpress_notif_after_run_workflow');
    }

    /**
     * Returns a list of receivers ids for this workflow
     *
     * @return array
     */
    protected function get_receivers()
    {
        $filtered_receivers = [];

        /**
         * Filters the list of receivers for the notification workflow.
         *
         * @param WP_Post $workflow
         * @param array   $args
         */
        $receivers = apply_filters('publishpress_notif_run_workflow_receivers', [], $this->workflow_post,
            $this->action_args);

        if ( ! empty($receivers)) {
            // Remove duplicate receivers
            $receivers = array_unique($receivers, SORT_STRING);

            // Classify receivers per channel, ignoring who has muted the channel.
            foreach ($receivers as $index => $receiver) {
                // Is an user (identified by the id)?
                if (is_numeric($receiver) || is_object($receiver)) {
                    // Try to extract the ID from the object
                    if (is_object($receiver)) {
                        if (isset($receiver->ID) && ! empty($receiver->ID)) {
                            $receiver = $receiver->ID;
                        } else {
                            if (isset($receiver->id) && ! empty($receiver->id)) {
                                $receiver = $receiver->id;
                            } else {
                                // If the object doesn't have an ID, we ignore it.
                                continue;
                            }
                        }
                    }

                    $channel = get_user_meta($receiver, 'psppno_workflow_channel_' . $this->workflow_post->ID, true);

                    // If channel is empty, we set a default channel.
                    if (empty($channel)) {
                        $channel = apply_filters('psppno_default_channel', 'email', $this->workflow_post->ID);
                    }

                    // If the channel is "mute", we ignore this receiver.
                    if ('mute' === $channel) {
                        continue;
                    }

                    // Make sure the array for the channel is initialized.
                    if ( ! isset($filtered_receivers[$channel])) {
                        $filtered_receivers[$channel] = [];
                    }

                    // Add to the channel's list.
                    $filtered_receivers[$channel][] = $receiver;
                } elseif (is_string($receiver)) {
                    // Check if it is an explicit email address.
                    if (preg_match('/^email:/', $receiver)) {
                        if ( ! isset($filtered_receivers['email'])) {
                            $filtered_receivers['email'] = [];
                        }

                        // Add to the email channel, without the "email:" prefix.
                        $filtered_receivers['email'][] = str_replace('email:', '', $receiver);
                    }
                }
            }
        }

        return $filtered_receivers;
    }

    /**
     * Returns the content for the notification, as an associative array with
     * the following keys:
     *     - subject
     *     - body
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function get_content()
    {
        $content = ['subject' => '', 'body' => ''];
        /**
         * Filters the content for the notification workflow.
         *
         * @param WP_Post $workflow
         * @param array   $args
         */
        $content = apply_filters('publishpress_notif_run_workflow_content', $content, $this->workflow_post,
            $this->action_args);

        if ( ! array_key_exists('subject', $content)) {
            $content['subject'] = '';
        }

        if ( ! array_key_exists('body', $content)) {
            $content['body'] = '';
        }

        return $content;
    }

    /**
     * @param string $content
     * @param mixed  $receiver
     * @param string $channel
     *
     * @return string
     */
    protected function do_shortcodes_in_content($content, $receiver, $channel)
    {
        /**
         * Action triggered before do shortcodes in the content.
         *
         * @param string $content
         * @param mixed  $receiver
         * @param string $channel
         */
        do_action('publishpress_workflow_do_shortcode_in_content', $content, $receiver, $channel);

        // Replace placeholders in the subject and body
        $content['subject'] = do_shortcode($content['subject']);
        $content['body']    = do_shortcode($content['body']);

        return $content;
    }

    /**
     * Get posts related to this workflow, applying the filters, in a reverse way, not founding a workflow related
     * to the post. Used by add-ons like Reminders.
     *
     * @return array
     */
    public function get_related_posts($args = [])
    {
        $workflow_meta_key = (!empty($args['meta_key_selected'])) ? $args['meta_key_selected'] : '_psppno_evtbeforepublishing';
        
        // We need to set a distinct "notification sent" flag for each workflow and notification criteria
        if ('_psppno_evtbeforepublishing' == $workflow_meta_key) {
            $post_status = (!empty($args['post_status'])) ? $args['post_status'] : 'future';

            $notification_suffix = "_{$post_status}_{$this->workflow_post->ID}";

        } elseif (!empty($args['post_status'])) {
            $notification_suffix = "-{$workflow_meta_key}_" . $args['post_status'] . "_{$this->workflow_post->ID}";
        } else {
            // Other notification criteria may not specify a post status
            $notification_suffix ="-{$workflow_meta_key}_{$this->workflow_post->ID}";
        }

        $posts = [];

        // Build the query
        $query_args = [
            'nopaging'      => true,
            'post_status'   => $post_status,
            'no_found_rows' => true,
            'cache_results' => true,
            'meta_query'    => [
                [
                    'key'     => static::NOTIFICATION_SCHEDULE_META_KEY . $notification_suffix,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        // Check if the workflow filters by post type
        $workflowPostTypes = get_post_meta(
            $this->workflow_post->ID,
            Step\Event_Content\Filter\Post_Type::META_KEY_POST_TYPE
        );

        if ( ! empty($workflowPostTypes)) {
            $query_args['post_type'] = $workflowPostTypes;
        }

        // Check if the workflow filters by category
        $workflowCategories = get_post_meta(
            $this->workflow_post->ID,
            Step\Event_Content\Filter\Category::META_KEY_CATEGORY
        );

        if ( ! empty($workflowCategories)) {
            $query_args['category__in'] = $workflowCategories;
        }

        $query = new \WP_Query($query_args);

        if ( ! empty($query->posts)) {
            foreach ($query->posts as $post) {
                $posts[] = $post;
            }
        }

        return $posts;
    }
}
