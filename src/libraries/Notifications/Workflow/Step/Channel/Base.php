<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Channel;

use PublishPress\Notifications\Workflow\Step\Base as Base_Step;

class Base extends Base_Step {

	const META_KEY_EMAIL = '_psppno_chnbase';

	/**
	 * @var string
	 */
	protected $icon;

	/**
	 * The constructor
	 */
	public function __construct() {
		if ( empty( $this->attr_prefix ) ) {
			$this->attr_prefix   = 'channel';
		}

		if ( empty( $this->twig_template ) ) {
			$this->twig_template   = 'workflow_channel_field.twig';
		}

		if ( empty( $this->name ) ) {
			throw new \Exception("Channel name not defined");

		}

		if ( empty( $this->label ) ) {
			throw new \Exception("Channel label not defined");
		}

		parent::__construct();

		// Add filter to display the channel in the user's profile
		add_filter( 'psppno_filter_channels_user_profile', [ $this, 'filter_channel_user_profile' ] );

		// Hook to the notification action
		add_action( 'publishpress_notif_notify', [ $this, 'action_notify' ], 10, 4 );

		// Check if we can hook to the psppno_save_user_profile action
		add_action( 'psppno_save_user_profile', [ $this, 'action_save_user_profile' ] );
	}

	/**
	 * Returns the user's data, by the user id.
	 *
	 * @param int $user_id
	 * @return WP_User
	 */
	protected function get_user_data( $user_id ) {
		return get_userdata( $user_id );
	}

	/**
	 * Returns a list of option fields to display in the user profile.
	 *
	 * 'options': [
	 *     [
	 *         'name'
	 *         'html'
	 *     ]
	 *  ]
	 *
	 * @return array
	 */
	protected function get_user_profile_option_fields() {
		return [];
	}

	/**
	 * Filters the list of notification channels to display in the
	 * user profile.
	 *
	 * [
	 *    'name': string
	 *    'label': string
	 *    'options': [
	 *        'name'
	 *        'html'
	 *    ]
	 * ]
	 *
	 * @param array $channels
	 *
	 * @return array
	 */
	public function filter_channel_user_profile( $channels ) {
		$channels[] = (object) array(
			'name'    => $this->name,
			'label'   => $this->label,
			'options' => $this->get_user_profile_option_fields(),
			'icon'    => $this->icon,
		);

		return $channels;
	}

	/**
	 * Renders the field in the metabox. On this case, we do not print
	 * anything for now.
	 *
	 * @param string $html
	 */
	public function render_metabox_section( $html ) {
		return $html;
	}

	/**
	 * Action hooked when the user profile is saved
	 *
	 * @param int $user_id
	 */
	public function action_save_user_profile( $user_id ) {
		return;
	}
}
