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

class Plugin {
	use Dependency_Injector;

	/**
	 * The method which runs the plugin
	 */
	public function init() {
		add_action( 'init', array( $this, 'create_post_type' ) );

		add_action( 'load-edit.php', [ $this, 'add_load_edit_hooks' ] );
	}

	/**
	 * Creates the custom post types for the notifications
	 */
	public function create_post_type() {
		// Notification Workflows
		register_post_type(
			PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
			array(
				'labels' => array(
					'name'           => __( 'Notification Workflows', 'publishpress-notifications' ),
					'singular_name'  => __( 'Notification Workflow', 'publishpress-notifications' ),
					'add_new_item'   => __( 'Add New Notification Workflow', 'publishpress-notifications' ),
					'edit_item'      => __( 'Edit Notification Workflow', 'publishpress-notifications' ),
					'search_items'   => __( 'Search Workflows', 'publishpress-notifications' ),
					'menu_name'      => __( 'Notifications', 'publishpress-notifications' ),
					'name_admin_bar' => __( 'Notification Workflow', 'publishpress-notifications' ),
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
					'revisions',
				)
			)
		);

		// Notifications
		register_post_type(
			PUBLISHPRESS_NOTIF_POST_TYPE_MESSAGE,
			array(
				'labels' => array(
					'name'          => __( 'Notifications', 'publishpress-notifications' ),
					'singular_name' => __( 'Notification', 'publishpress-notifications' )
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

		$post_columns['events']    = 'Events';
		$post_columns['receivers'] = 'Receivers';
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
			echo __( '-', 'publishpress-notifications' );
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
			echo __( '-', 'publishpress-notifications' );
		} else {
			echo implode( ', ', $values );
		}
	}
}
