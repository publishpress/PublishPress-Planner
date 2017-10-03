<?php
/**
 * File responsible for defining basic addon class
 *
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications;

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;

class Plugin {
	use Dependency_Injector, PublishPress_Module;

	/**
	 * The method which runs the plugin
	 */
	public function init() {
		add_action( 'init', array( $this, 'create_post_type' ) );

		add_action( 'load-edit.php', [ $this, 'add_load_edit_hooks' ] );

		add_filter( 'post_updated_messages', [ $this, 'filter_post_updated_messages' ] );
		add_filter( 'bulk_post_updated_messages', [ $this, 'filter_bulk_post_updated_messages' ], 10, 2 );
	}

	/**
	 * Creates the custom post types for the notifications
	 */
	public function create_post_type() {
		if ( $this->is_module_enabled( 'notifications' )
			&& $this->is_module_enabled( 'improved_notifications' ) ) {
			// Notification Workflows
			register_post_type(
				PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
				array(
					'labels' => array(
						'name'               => __( 'Notification Workflows', 'publishpress' ),
						'singular_name'      => __( 'Notification Workflow', 'publishpress' ),
						'add_new_item'       => __( 'Add New Notification Workflow', 'publishpress' ),
						'edit_item'          => __( 'Edit Notification Workflow', 'publishpress' ),
						'search_items'       => __( 'Search Workflows', 'publishpress' ),
						'menu_name'          => __( 'Notifications', 'publishpress' ),
						'name_admin_bar'     => __( 'Notification Workflow', 'publishpress' ),
						'not_found'          => __( 'No Workflow found', 'publishpress' ),
						'not_found_in_trash' => __( 'No Workflow found', 'publishpress' ),
					),
					'public'              => false,
					'publicly_queryable'  => false,
					'has_archive'         => false,
					'rewrite'             => array( 'slug' => 'notification-workflows' ),
					'show_ui'             => true,
					'query_var'           => true,
					'capability_type'     => 'post',
					'hierarchical'        => false,
					'can_export'          => true,
					'show_in_admin_bar'   => true,
					'exclude_from_search' => true,
					'show_in_menu'        => 'pp-calendar',
					'menu_position'       => '20',
					'supports'            => array(
						'title',
					)
				)
			);

			// Notifications
			register_post_type(
				PUBLISHPRESS_NOTIF_POST_TYPE_MESSAGE,
				array(
					'labels' => array(
						'name'          => __( 'Notifications', 'publishpress' ),
						'singular_name' => __( 'Notification', 'publishpress' )
					),
					'public'              => false,
					'publicly_queryable'  => false,
					'has_archive'         => false,
					'rewrite'             => array( 'slug' => 'notifications' ),
					'show_ui'             => false,
					'query_var'           => true,
					'hierarchical'        => false,
					'can_export'          => false,
					'show_in_admin_bar'   => false,
					'exclude_from_search' => true,
					'supports' => array(
						'title',
						'editor',
					)
				)
			);
		}
	}

	public function add_load_edit_hooks() {
		$post_type   = 'psppnotif_workflow';
		$screen      = get_current_screen();

		if ( ! isset ( $screen->id ) ) {
			return;
		}

		if ( "edit-$post_type" !== $screen->id ) {
			return;
		}

		add_filter( "manage_{$post_type}_posts_columns", [ $this, 'filter_manage_post_columns' ] );

		add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'action_manage_post_custom_column' ], 10, 2 );
	}

	public function filter_manage_post_columns( $post_columns ) {
		$date_column = $post_columns['date'];
		unset( $post_columns['date'] );

		$post_columns['events']    = __( 'When to notify?', 'publishpress' );
		$post_columns['receivers'] = __( 'Who to notify?', 'publishpress' );
		$post_columns['date']      = $date_column;

		return $post_columns;
	}

	public function action_manage_post_custom_column( $column_name, $post_id ) {
		$columns = [
			'events',
			'receivers',
		];
		// Ignore other columns
		if ( ! in_array( $column_name, $columns ) ) {
			return;
		}

		$method_name = 'print_column_' . $column_name;
		$this->$method_name( $post_id );
	}

	/**
	 * Print the column for the events
	 *
	 * @param int $post_id
	 */
	protected function print_column_events( $post_id ) {
		/**
		 * Get the event metakeys
		 *
		 * @param array $metakeys
		 */
		$metakeys = apply_filters( 'psppno_events_metakeys', [] );
		$events   = [];

		foreach ( $metakeys as $metakey => $label ) {
			$selected = get_post_meta( $post_id, $metakey, true );

			if ( $selected ) {
				$events[] = $label;
			}
		}

		if ( empty( $events ) ) {
			echo '<span class="psppno_no_events_warning">' . __( 'Please select at least one event' , 'publishpress' ) . '</span>';
		} else {
			echo implode( ', ', $events );
		}
	}

	/**
	 * Print the column for the receivers
	 *
	 * @param int $post_id
	 */
	protected function print_column_receivers( $post_id ) {
		/**
		 * Get the values to display in the column
		 *
		 * @param array $values
		 * @param int   $post_id
		 */
		$values = apply_filters( 'psppno_receivers_column_value', [], $post_id );

		if ( empty( $values ) ) {
			echo '-';
		} else {
			echo implode( ', ', $values );
		}
	}

	/**
	 * Customize the post messages for the notification workflows when the
	 * content is saved.
	 *
	 * @param string $messages
	 *
	 * @return array
	 */
	public function filter_post_updated_messages( $messages ) {
		global $current_screen;

		if ( PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW !== $current_screen->post_type ) {
			return $messages;
		}

		$post = get_post();

		/* translators: Publish box date format, see https://secure.php.net/date */
		$scheduled_date = date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );

		$messages['post'][1]  = __( 'Notification workflow updated.', 'pulishpress' );
		$messages['post'][4]  = __( 'Notification workflow updated.', 'pulishpress' );
		$messages['post'][6]  = __( 'Notification workflow published.', 'pulishpress' );
		$messages['post'][7]  = __( 'Notification workflow saved.', 'pulishpress' );
		$messages['post'][8]  = __( 'Notification workflow submitted.', 'pulishpress' );
		$messages['post'][9]  = sprintf( __( 'Notification workflow scheduled for: %s.' ), '<strong>' . $scheduled_date . '</strong>' );
		$messages['post'][10] = __( 'Notification workflow draft updated.', 'pulishpress' );

		return $messages;
	}

	/**
	 * Customize the post messages for the notification workflows when the
	 * content is bulk edited.
	 *
	 * @param string $bulk_messages
	 * @param int    $bulk_contents
	 *
	 * @return array
	 */
	public function filter_bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		global $current_screen;

		if ( PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW !== $current_screen->post_type ) {
			return $bulk_messages;
		}

		$bulk_messages['post']['updated']   = _n( '%s notification workflow updated.', '%s notification workflows updated.', $bulk_counts['updated'] );
		$bulk_messages['post']['locked']    = ( 1 == $bulk_counts['locked'] ) ? __( '1 notification workflow not updated, somebody is editing it.' ) :
			_n( '%s notification workflow not updated, somebody is editing it.', '%s notification workflows not updated, somebody is editing them.', $bulk_counts['locked'] );
		$bulk_messages['post']['deleted']   = _n( '%s notification workflow permanently deleted.', '%s notification workflows permanently deleted.', $bulk_counts['deleted'] );
		$bulk_messages['post']['trashed']   = _n( '%s notification workflow moved to the Trash.', '%s notification workflows moved to the Trash.', $bulk_counts['trashed'] );
		$bulk_messages['post']['untrashed'] = _n( '%s notification workflow restored from the Trash.', '%s notification workflows restored from the Trash.', $bulk_counts['untrashed'] );

		return $bulk_messages;
	}
}
