<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

class Follower extends Simple_Checkbox implements Receiver_Interface {

	const META_KEY   = '_psppno_tofollower';
	const META_VALUE = 'follower';

	protected $option = 'receiver_follower';

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->name          = 'follower';
		$this->label         = __( 'Followers of the content', 'publishpress' );
		$this->option_name   = 'receiver_follower';

		parent::__construct();
	}

	/**
	 * Filters the list of receivers for the workflow. Returns the list of IDs.
	 *
	 * @param array   $receivers
	 * @param WP_Post $workflow
	 * @param array   $args
	 * @return array
	 */
	public function filter_workflow_receivers( $receivers, $workflow, $args ) {
		global $publishpress;

		// If checked, add the authors to the list of receivers
		if ( $this->is_selected( $workflow->ID ) ) {

			$post_id = $args['post']->ID;

			if ( empty( $post_id ) ) {
                return $receivers;
            }

			$followers = array();

			if ( $publishpress->improved_notifications->module_enabled( 'user_groups') ) {
                // Get following users and usergroups
                $usergroups = $publishpress->notifications->get_following_usergroups( $post_id, 'ids' );

                foreach ( (array) $usergroups as $usergroup_id ) {
                    $usergroup = $publishpress->user_groups->get_usergroup_by( 'id', $usergroup_id );

                    foreach ( (array) $usergroup->user_ids as $user_id ) {
                        $usergroup_user = get_user_by( 'id', $user_id );

                        if ( $usergroup_user && is_user_member_of_blog( $user_id ) ) {
                            $followers[] = $usergroup_user;
                        }
                    }
                }
			}

            $users = $publishpress->notifications->get_following_users( $post_id, 'object' );

            // Merge usergroup users and users
            $followers = array_merge( $followers, $users );

            // Process the recipients for this email to be sent
            foreach ( $followers as $key => $user ) {

                // Don't send the email to the current user unless we've explicitly indicated they should receive it
                if ( false === apply_filters( 'pp_notification_email_current_user', false ) && wp_get_current_user()->user_email == $user->user_email ) {
                    unset( $followers[ $key ] );
                }
            }

			/**
			 * Filters the list of followers.
			 *
			 * @param array   $followers
			 * @param WP_Post $workflow
			 * @param array   $args
			 */
			$followers = apply_filters( 'publishpress_notif_workflow_receiver_post_followers', $followers, $workflow, $args );

			// Add the user ids for the receivers list
			if ( ! empty( $followers ) ) {
				foreach ( $followers as $user ) {
					$receivers[] = $user->ID;
				}
			}
		}

		return $receivers;
	}

	/**
	 * Add the respective value to the column in the workflow list
	 *
	 * @param array $values
	 * @param int   $post_id
	 *
	 * @return array
	 */
	public function filter_receivers_column_value( $values, $post_id ) {
		if ( $this->is_selected( $post_id ) ) {
			$values[] = __( 'Authors', 'publishpress' );
		}

		return $values;
	}
}
