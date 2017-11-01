<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow;

class Controller {

	/**
	 * List of receivers by channel
	 *
	 * @var array
	 */
	protected $receivers_by_channel = [];

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action( 'publishpress_notif_run_workflows', [ $this, 'run_workflows' ], 10 );
	}

	/**
	 * Look for enabled workflows, filtering and running according to each
	 * settings.
	 *
	 * $args = [
	 *     'action',
	 *     'post',
	 *     'new_stauts',
	 *     'old_status',
	 * ]
	 *
	 * @param array $args
	 */
	public function run_workflows( $args ) {
		$workflows = $this->get_filtered_workflows( $args );

		foreach ( $workflows as $workflow ) {
			$workflow->run( $args );
		}
	}

	/**
	 * Returns a list of published workflows which passed all filters.
	 *
	 * $args = [
	 *     'post',
	 *     'new_stauts',
	 *     'old_status',
	 * ]
	 *
	 * @param array $args
	 * @return array
	 */
	protected function get_filtered_workflows( $args ) {
		$workflows = [];

		// Build the query
		$query_args = [
			'nopaging'    => true,
			'post_type'   => PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
			'post_status' => 'publish',
			'no_found_rows' => true,
			'cache_results' => true,
			'meta_query'  => [],
		];

		/**
		 * Filters the arguments sent to the query to get workflows and
		 * each step's filters.
		 *
		 * @param array  $query_args
		 * @param array  $args
		 */
		$query_args = apply_filters( 'publishpress_notif_run_workflow_meta_query', $query_args, $args );

		$query = new \WP_Query( $query_args );

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post ) {
				$workflows[] = new Workflow( $post );
			}
		}

		return $workflows;
	}

	/**
	 * Loads instantiating the classes for the workflow steps.
	 */
	public function load_workflow_steps() {
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
		$classes_event = apply_filters( 'publishpress_notif_workflow_steps_event', $classes_event );

		// Which Content
		$classes_event_content = [
			'\\PublishPress\\Notifications\\Workflow\\Step\\Event_Content\\Post_Type',
			'\\PublishPress\\Notifications\\Workflow\\Step\\Event_Content\\Category',
		];
		/**
		 * Filters the list of classes to define workflow "when - which content" steps.
		 *
		 * @param array $classes The list of classes to be loaded
		 */
		$classes_event_content = apply_filters( 'publishpress_notif_workflow_steps_event_content', $classes_event_content );

		// Who
		$classes_receiver = [
			'\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\Site_Admin',
			'\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\Author',
			'\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\User',
			'\\PublishPress\\Notifications\\Workflow\\Step\\Receiver\\User_Group',
		];
		/**
		 * Filters the list of classes to define workflow "who" steps.
		 *
		 * @param array $classes The list of classes to be loaded
		 */
		$classes_receiver = apply_filters( 'publishpress_notif_workflow_steps_receiver', $classes_receiver );

		// Where
		$classes_channel = [
			'\\PublishPress\\Notifications\\Workflow\\Step\\Channel\\Email',
		];
		/**
		 * Filters the list of classes to define workflow "where" steps.
		 *
		 * @param array $classes The list of classes to be loaded
		 */
		$classes_channel = apply_filters( 'publishpress_notif_workflow_steps_channel', $classes_channel );

		// What
		$classes_content = [
			'\\PublishPress\\Notifications\\Workflow\\Step\\Content\\Main',
		];
		/**
		 * Filters the list of classes to define workflow "what" steps.
		 *
		 * @param array $classes The list of classes to be loaded
		 */
		$classes_content = apply_filters( 'publishpress_notif_workflow_steps_content', $classes_content );

		$classes = array_merge( $classes_event, $classes_event_content, $classes_receiver, $classes_channel, $classes_content );

		// Instantiate each class
		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				new $class;
			}
		}
	}

	/**
	 * Split receivers by type: IDs, Email, etc... separated by :
	 * If is a number, take as the user ID. If has the separator,
	 * take it as data with the channel as the first data, like:
	 * email:test@sdafsa.com
	 *
	 * @param array  $receivers
	 *
	 * @param array
	 */
	protected function split_receivers_into_types( $receivers ) {
		$by_type = [
			'id' => []
		];

		foreach ( $receivers as $receiver ) {
			// Is a user ID?
			if ( is_numeric( $receiver ) ) {
				$by_type['id'][] = (int) $receiver;
				continue;
			}

			// Is of an specific type?
			$data = explode( ':', $receiver );
			if ( count( $data ) === 2 ) {
				$by_type[ $data[0] ][] = $data[1];
			}
		}

		return $by_type;
	}

	/**
	 * Process a list of receivers with user IDS, identifying the notification
	 * channel set for each user (or the default one, if none).
	 *
	 * @param int    $workflow_id
	 * @param array  $receivers
	 */
	protected function process_receivers_of_type_user_id( $workflow_id, $receivers ) {
		global $wpdb;

		$receivers_ids = implode(',', $receivers);

		$results = $wpdb->get_results(
			"
			SELECT *
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'psppno_workflow_channel_{$workflow_id}'
				AND user_id IN ({$receivers_ids})
			",
			OBJECT
		);

		if ( ! empty( $results ) ) {
			foreach ( $results as $meta ) {
				// Ignore if muted
				if ( 'mute' === $meta->meta_value ) {
					continue;
				}

				// Mark the user as already processed
				$index = array_search( $meta->user_id, $receivers );
				unset( $receivers[ $index ] );

				// Make sure we have at least an empty array as value
				if ( ! isset( $this->receivers_by_channel[ $meta->meta_value ] ) ) {
					$this->receivers_by_channel[ $meta->meta_value ] = [];
				}

				$this->receivers_by_channel[ $meta->meta_value ][] = $meta->user_id;
			}
		}

		// Make sure to use the default notification channel if the user doesn't have any one set
		if ( ! empty( $receivers ) ) {
			foreach ( $receivers as $receiver_id ) {
				/**
				 * Filters the default notification channel.
				 *
				 * @param string $default_channel
				 *
				 * @return string
				 */
				$default_channel = apply_filters( 'psppno_filter_default_notification_channel', 'email' );

				$this->receivers_by_channel[ $default_channel ][] = $receiver_id;
			}
		}
	}

	/**
	 * Return the list of receivers, filtered by channel
	 *
	 * @param int    $workflow_id
	 * @param array  $receivers
	 * @param string $channel
	 *
	 * @return array
	 */
	public function get_receivers_by_channel( $workflow_id, $receivers, $channel ) {
		if ( empty( $this->receivers_by_channel ) ) {
			$by_type = $this->split_receivers_into_types( $receivers );

			if ( ! empty( $by_type['id'] ) ) {
				$this->process_receivers_of_type_user_id( $workflow_id, $by_type['id'] );
				unset( $by_type['id'] );
			}

			// Do we have receivers set for specific channels (specific type)?
			if ( ! empty( $by_type ) ) {
				foreach ( $by_type as $type => $receivers ) {
					foreach ( $receivers as $receiver ) {
						$this->receivers_by_channel[ $type ][] = $receiver;
					}
				}
			}
		}

		// Make sure we have at least an empty array as value
		if ( ! isset( $this->receivers_by_channel[ $channel ] ) ) {
			$this->receivers_by_channel[ $channel ] = [];
		}

		return $this->receivers_by_channel[ $channel ];
	}
}
