<?php
/**
 * @package     PublishPress\Notifications
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event_Content;

use PublishPress\Notifications\Workflow\Step\Event\Base as Base_Step;

class Base extends Base_Step {

	/**
	 * The constructor
	 */
	public function __construct() {
		$this->attr_prefix  = 'event_content';
		
		parent::__construct();
	}
}
