<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Traits;

trait PublishPress_Module
{
    /**
     * Returns a list of custom statuses found in the Custom Status module.
     * If the module is disabled, we display only the native statuses.
     *
     * @return array
     */
    public function get_post_statuses()
    {
        $publishpress = $this->get_service('publishpress');

        return $publishpress->getPostStatuses();
    }

    /**
     * Returns true if the module is loaded and enabled.
     *
     * @return bool
     */
    public function is_module_enabled($slug)
    {
        if ('custom_status' == $slug) {
            return class_exists('PublishPress_Statuses');
        }

        $publishpress = $this->get_service('publishpress');

        if (isset($publishpress->$slug)) {
            return 'on' === $publishpress->$slug->module->options->enabled;
        }

        // Try getting the setting from the db
        $options = get_option("publishpress_{$slug}_options");
        if (isset($options->enabled)) {
            return 'on' === $options->enabled;
        }

        return false;
    }

    /**
     * Get core's 'draft' and 'pending' post statuses, but include our special attributes
     *
     * @return array
     */
    protected function get_core_post_statuses()
    {
        $publishpress = $this->get_service('publishpress');

        return $publishpress->getCorePostStatuses();
    }

    /**
     * Returns a list of post types the notifications support.
     *
     * @return array
     */
    public function get_post_types()
    {
        $publishpress = $this->get_service('publishpress');

        $module = null;
        if (isset($publishpress->notifications)) {
            $module = $publishpress->notifications->module;
        }

        return $publishpress->improved_notifications->get_all_post_types($module);
    }
}
