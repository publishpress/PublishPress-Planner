<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

use PublishPress\Notifications\Traits\PublishPress_Module;

class User_Group extends Base implements Receiver_Interface {
	use PublishPress_Module;

	const META_KEY_SELECTED = '_psppno_togroup';

	/**
	 * The constructor
	 */
	public function __construct() {
		// Check if the user groups module is enabled before use this step
		if ( ! $this->is_module_enabled( 'user_groups' ) ) {
			return;
		}

		$this->twig_template = 'workflow_receiver_user_group_field.twig';
		$this->name          = 'user_group';
		$this->label         = __( 'User Groups', 'publishpress-notifications' );

		parent::__construct();
	}

	/**
	 * Filters the context sent to the twig template in the metabox
	 *
	 * @param array $template_context
	 */
	public function filter_workflow_metabox_context( $template_context ) {
		$user_groups = $this->get_service( 'publishpress' )->user_groups->get_usergroups();

		$selected_groups = (array) $this->get_metadata( static::META_KEY_SELECTED );
		foreach ( $user_groups as $group ) {
			if ( in_array( $group->term_id, $selected_groups ) ) {
				$group->selected = true;
			}
		}

		$template_context['user_groups'] = $user_groups;
		$template_context['list_class']  = 'publishpress_notif_user_group_list';
		$template_context['input_name']  = 'publishpress_notif[receiver_user_groups]';
		$template_context['input_id']    = 'publishpress_notification_user_group_';

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
			|| ! isset( $_POST['publishpress_notif']['receiver_user_groups'] ) ) {
			// Assume it is disabled
			$values = [];
		} else {
			$values = $_POST['publishpress_notif']['receiver_user_groups'];
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
		$groups = get_post_meta( $workflow->ID, static::META_KEY_SELECTED );
		$receivers = array_merge( $receivers, $this->get_users_from_user_groups( $groups ) );

		// Get the groups following the post
		$groups = $this->get_service( 'publishpress' )->notifications->get_following_usergroups( $args['post']->ID, 'ids' );
		$receivers = array_merge( $receivers, $this->get_users_from_user_groups( $groups ) );

		return $receivers;
	}

	/**
	 * Returns an array with a list of users' ids from the given user groups.
	 *
	 * @param array $user_groups
	 * @return array
	 */
	protected function get_users_from_user_groups( $user_groups ) {
		$users = [];

		if ( ! empty( $user_groups ) ) {
			foreach ( (array) $user_groups as $user_group_id ) {
	            $user_group = $this->get_service( 'publishpress' )->user_groups->get_usergroup_by( 'id', $user_group_id );

	            $users = array_merge( $users, (array) $user_group->user_ids );
	        }
		}

		return $users;
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
		$groups = get_post_meta( $post_id, static::META_KEY_SELECTED );

		if ( ! empty( $groups ) ) {
			$values[] = sprintf(
				_n( '%d Group', '%d Groups', count( $groups ), 'publishpress-notifications' ),
				count( $groups )
			);
		}

		return $values;
	}
}
