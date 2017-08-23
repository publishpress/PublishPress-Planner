<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Receiver;

use PublishPress\Notifications\Workflow\Step\Base as Base_Step;

class Author extends Base implements Receiver_Interface {

	const META_KEY_SELECTED = '_psppno_toauthor';
	/**
	 * The constructor
	 */
	public function __construct() {
		$this->twig_template = 'workflow_receiver_author_field.twig';
		$this->name          = 'author';
		$this->label         = __( 'Authors of the content', 'publishpress-notifications' );

		parent::__construct();
	}

	/**
	 * Filters the context sent to the twig template in the metabox
	 *
	 * @param array $template_context
	 */
	public function filter_workflow_metabox_context( $template_context ) {
		// Metadata
		$meta = $this->get_metadata( static::META_KEY_SELECTED, true );

		$template_context['meta'] = [
			'selected' => (bool) $meta,
		];

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
			|| ! isset( $_POST['publishpress_notif']['receiver_author'] ) ) {
			// Assume it is disabled
			update_post_meta( $id, static::META_KEY_SELECTED, false );
		}

		$params = $_POST['publishpress_notif'];

		// Is selected in the events?
		$selected = isset( $params['receiver_author'] ) ? $params['receiver_author'] : false;
		update_post_meta( $id, static::META_KEY_SELECTED, $selected === 'author' );
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

		$checked = get_post_meta( $workflow->ID, static::META_KEY_SELECTED, true );

		// If checked, add the authors to the list of receivers
		if ( $checked ) {
			$receivers[] = (int) $args['post']->post_author;

			/**
			 * Filters the list of receivers, but triggers only when the authors are selected.
			 *
			 * @param array   $receivers
			 * @param WP_Post $workflow
			 * @param array   $args
			 */
			$receivers = apply_filters( 'publishpress_notif_workflow_receiver_post_authors', $receivers, $workflow, $args );
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
		$selected = get_post_meta( $post_id, static::META_KEY_SELECTED, true );

		if ( $selected ) {
			$values[] = __( 'Authors', 'publishpress-notifications' );
		}

		return $values;
	}
}
