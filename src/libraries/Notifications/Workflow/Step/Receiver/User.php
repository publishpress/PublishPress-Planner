<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

class User extends Base implements Receiver_Interface {

	const META_KEY_SELECTED = '_psppno_touser';

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->twig_template = 'workflow_receiver_user_field.twig';
		$this->name          = 'user';
		$this->label         = __( 'Users', 'publishpress' );

		parent::__construct();
	}

	/**
	 * Filters the context sent to the twig template in the metabox
	 *
	 * @param array $template_context
	 */
	public function filter_workflow_metabox_context( $template_context ) {
		// Get Users
		$args = array(
			'who'     => 'authors',
			'fields'  => array(
				'ID',
				'display_name',
				'user_email',
			),
			'orderby' => 'display_name',
		);
		$args  = apply_filters( 'publishpress_notif_users_select_form_get_users_args', $args );
		$users = get_users( $args );

		$selected_users = (array) $this->get_metadata( static::META_KEY_SELECTED );
		foreach ( $users as $user ) {
			if ( in_array( $user->ID, $selected_users ) ) {
				$user->selected = true;
			}
		}

		$template_context['users']          = $users;
		$template_context['list_class']     = 'publishpress_notif_user_list';
		$template_context['input_name']     = 'publishpress_notif[receiver_users]';
		$template_context['input_id']       = 'publishpress_notification_user_';

		return $template_context;
	}

	/**
	 * Method called when a notification workflow is saved.
	 *
	 * @param int      $id
	 * @param WP_Post  $post
	 */
	public function save_metabox_data( $id, $post ) {
		if ( ! isset( $_POST['publishpress_notif'] )
			|| ! isset( $_POST['publishpress_notif']['receiver_users'] ) ) {
			// Assume it is disabled
			$values = [];
		} else {
			$values = $_POST['publishpress_notif']['receiver_users'];
		}

		$this->update_metadata_array( $id, static::META_KEY_SELECTED, $values );
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

		// Get the users selected in the workflow
		$users = get_post_meta( $workflow->ID, static::META_KEY_SELECTED );
		$receivers = array_merge( $receivers, $users );

		// Get the users following the post
		$users = $this->get_service( 'publishpress' )->notifications->get_following_users( $args['post']->ID, 'id' );
		$receivers = array_merge( $receivers, $users );

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
		$users = get_post_meta( $post_id, static::META_KEY_SELECTED );

		if ( ! empty( $users ) ) {
			$values[] = sprintf(
				_n( '%d User', '%d Users', count( $users ), 'publishpress' ),
				count( $users )
			);
		}

		return $values;
	}
}
