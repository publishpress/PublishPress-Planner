<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event_Content;

use PublishPress\Notifications\Workflow\Step\Event\Base as Base_Step;

class Base extends Base_Step
{

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->attr_prefix = 'event_content';

        parent::__construct();

        // Add filter to return the metakey representing if it is selected or not
        add_filter('psppno_filter_metakeys', [$this, 'filter_events_metakeys']);
    }
}
