<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event;

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Workflow\Step\Event\Filter;

class Post_Save extends Base {

	const META_KEY_SELECTED = '_psppno_evtpostsave';

	const META_VALUE_SELECTED = 'post_save';

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->name  = 'post_save';
		$this->label = __( 'When the content is moved to a new status', 'publishpress' );

		parent::__construct();
	}

	/**
	 * Method to return a list of fields to display in the filter area
	 *
	 * @param array
	 *
	 * @return array
	 */
	protected function get_filters( $filters = [] ) {
		if ( ! empty( $this->cache_filters ) ) {
			return $this->cache_filters;
		}

		$step_name = 'event_' . $this->name;

		$filters[] = new Filter\Post_Status( $step_name );

		return parent::get_filters( $filters );
	}

	/**
	 * Filters and returns the arguments for the query which locates
	 * workflows that should be executed.
	 *
	 * @param array $query_args
	 * @param array $action_args
	 * @return array
	 */
	public function filter_run_workflow_query_args( $query_args, $action_args ) {

		if ( 'transition_post_status' === $action_args['action'] ) {
			$query_args['meta_query'][] = [
				'key'     => static::META_KEY_SELECTED,
				'value'   => 1,
				'type'    => 'BOOL',
				'compare' => '=',
			];

			// Check the filters
			$filters = $this->get_filters();

			foreach ( $filters as $filter ) {
				$query_args = $filter->get_run_workflow_query_args( $query_args, $action_args );
			}
		}

		return $query_args;
	}
}
