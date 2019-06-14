<?php

namespace PublishPress\Notifications;

defined('ABSPATH') or die('No direct script access allowed.');

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;

class Reviews
{
    use Dependency_Injector, PublishPress_Module;

    const REPEAT_INTERVAL = '+2 weeks';

    const OPTION_LAST_CHECK = 'publishpress_review_last_check';

    /**
     * The method which runs the plugin
     */
    public function init()
    {
        // We only check GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        if ($this->time_to_show_notice()) {
            global $submenu;

            $redirectURL = '';

            if (isset($submenu['pp-modules-settings'])) {
                $redirectURL = $submenu['pp-modules-settings'][0][2];
            }

            if (empty($redirectURL)) {
                if (isset($submenu['pp-manage-roles'])) {
                    $redirectURL = $submenu['pp-manage-roles'][0][2];
                } else {
                    $redirectURL = 'admin.php?page=pp-modules-settings';
                }
            }

            $params = [
                'redirect_url' => $redirectURL,
                'review_link'  => 'http://wordpress.org/support/plugin/publishpress/reviews/#new-post',
                'notice_text'  => __(
                    'Hey, I noticed you have been using %sPublishPress%s for a few weeks - that\'s awesome! May I ask you to give it a %s5-star%s rating on WordPress? Just to help us spread the word and boost our motivation.',
                    'publishpress'
                ),
            ];

            // Enable the Reviews module from the Allex framework.
            do_action('allex_enable_module_reviews', $params);
        }
    }

    /**
     * @return bool
     */
    protected function time_to_show_notice()
    {
        $date  = $this->get_state_last_time();
        $date  = strtotime($date . ' ' . self::REPEAT_INTERVAL);
        $today = strtotime(date('Y-m-d'));

        return $date <= $today;
    }

    /**
     * @return false|mixed|string
     */
    protected function get_state_last_time()
    {
        $option = get_option(self::OPTION_LAST_CHECK, null);

        if (is_null($option)) {
            update_option(self::OPTION_LAST_CHECK, date('Y-m-d'));
            $option = date('Y-m-d');
        }

        return $option;
    }
}
