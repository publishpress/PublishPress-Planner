<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

class ParentAuthor extends Simple_Checkbox implements Receiver_Interface
{
    const META_KEY = '_psppno_toauthor';

    const META_VALUE = 'parent_author';

    protected $option = 'receiver_parent_author';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->name        = 'parent_author';
        $this->label       = __('Authors of the parent page', 'publishpress');
        $this->option_name = 'receiver_parent_author';

        parent::__construct();
    }

    /**
     * Filters the list of receivers for the workflow. Returns the list of IDs.
     *
     * @param array $receivers
     * @param WP_Post $workflow
     * @param array $args
     *
     * @return array
     */
    public function filter_workflow_receivers($receivers, $workflow, $args)
    {
        // If checked, add the authors to the list of receivers
        if ($this->is_selected($workflow->ID)) {
            $post = get_post($args['params']['post_id']);

            if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post->ID)) {
                $parent_post_id = rvy_post_id($post->ID);
            } else {
                $parent_post_id = $post->post_parent;
            }

            if ($parent_post_id) {
                $parent_post = get_post($parent_post_id);
            }

            if (empty($parent_post) || empty($posparent_postt_id)) {
                return;
            }

            /**
             * @param int $post_author
             * @param int $post_id
             *
             * @return int|array
             */
            $post_authors = apply_filters(
                'publishpress_notifications_receiver_post_parent_authors',
                [$post->post_author],
                $workflow->ID,
                $args
            );

            if (!is_array($post_authors)) {
                $post_authors = [$post_authors];
            }

            foreach ($post_authors as $post_author) {
                $receiverData = [
                    'receiver' => $post_author,
                    'group'    => self::META_VALUE
                ];

                if (!is_numeric($post_author) && substr_count($post_author, '@') > 0) {
                    $receiverData['channel'] = 'email';
                }

                $receivers[] = $receiverData;
            }
        }

        return $receivers;
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
        if ($this->is_selected($post_id)) {
            $values[] = __('Parent Page Authors', 'publishpress');
        }

        return $values;
    }
}
