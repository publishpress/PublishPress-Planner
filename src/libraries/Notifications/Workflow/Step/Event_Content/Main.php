<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event_Content;

use PublishPress\Notifications\Workflow\Step\Base as Base_Step;

class Main extends Base_Step {

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->attr_prefix   = 'event_content';
		$this->twig_template = 'workflow_event_content_main_field.twig';
		$this->name          = 'main';
		$this->label         = __( 'Which Content', 'publishpress' );

		parent::__construct();

		// Add the event filters to the metabox template
		add_filter(
			"publishpress_notif_workflow_metabox_context_{$this->attr_prefix}_{$this->name}",
			[ $this, 'filter_workflow_metabox_context' ]
		);

		// Add the fitler to the run workflow query args
		add_filter( 'publishpress_notif_run_workflow_meta_query', [ $this, 'filter_run_workflow_query_args' ], 10, 2 );
	}

	/**
	 * Filters the context sent to the twig template in the metabox
	 *
	 * @param array $template_context
	 */
	public function filter_workflow_metabox_context( $template_context ) {
		$template_context['event_filters'] = $this->get_filters();

		return $template_context;
	}

	/**
	 * Method called when a notification workflow is saved.
	 *
	 * @param int      $id
	 * @param WP_Post  $post
	 */
	public function save_metabox_data( $id, $post ) {
		if ( ! isset( $_POST['publishpress_notif'] ) ) {
			return;
		}

		// Process the filters
		$filters = $this->get_filters();
		if ( ! empty( $filters ) ) {
			foreach ( $filters as $filter ) {
				$filter->save_metabox_data( $id, $post );
			}
		}
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

		$filters[] = new Filter\Post_Type( $step_name );
        $filters[] = new Filter\Category( $step_name );

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
			// Check the filters
			$filters = $this->get_filters();

			foreach ( $filters as $filter ) {
				$query_args = $filter->get_run_workflow_query_args( $query_args, $action_args );
			}
		}

		return $query_args;
	}
}
