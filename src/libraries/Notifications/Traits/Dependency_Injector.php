<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Traits;

use PublishPress\Notifications\Pimple_Container;

trait Dependency_Injector {
	/**
	 * Instance of the Pimple container
	 */
	protected $pimple_container;

	protected function init_pimple() {
		$this->pimple_container = Pimple_Container::get_instance();
	}

	/**
	 * Returns the required service or dependency from the container.
	 * Throws an exception if the service is not defined.
	 *
	 * @param string $service_name
	 *
	 * @throws \Exception
	 */
	public function get_service( $service_name ) {
		if ( empty( $this->pimple_container ) ) {
			$this->init_pimple();
		}

		if ( ! isset( $this->pimple_container[ $service_name ] ) ) {
			throw new \Exception( "Service \"{$service_name}\" not found in the container" );
		}

		return $this->pimple_container[ $service_name ];
	}
}
