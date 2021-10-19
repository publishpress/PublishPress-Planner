<?php
/**
 * This class can be customized to quickly add a review request system.
 *
 * It includes:
 * - Multiple trigger groups which can be ordered by priority.
 * - Multiple triggers per group.
 * - Customizable messaging per trigger.
 * - Link to review page.
 * - Request reviews on a per user basis rather than per site.
 * - Allows each user to dismiss it until later or permanently seamlessly via AJAX.
 * - Integrates with attached tracking server to keep anonymous records of each triggers effectiveness.
 *   - Tracking Server API: https://gist.github.com/danieliser/0d997532e023c46d38e1bdfd50f38801
 *
 * Original Author: danieliser
 * Original Author URL: https://danieliser.com
 * URL: https://github.com/danieliser/WP-Product-In-Dash-Review-Requests
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class PP_Reviews
 *
 * This class adds a review request system for your plugin or theme to the WP dashboard.
 */
class PP_Reviews
{
    /**
     * Tracking API Endpoint.
     *
     * @var string
     */
    public static $api_url = '';

    /**
     *
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'hooks'));
        add_action('wp_ajax_ppch_review_action', array(__CLASS__, 'ajax_handler'));
    }

    /**
     * Hook into relevant WP actions.
     */
    public static function hooks()
    {
        if (is_admin() && current_user_can('edit_posts')) {
            self::installed_on();
            add_action('admin_notices', array(__CLASS__, 'admin_notices'));
            add_action('network_admin_notices', array(__CLASS__, 'admin_notices'));
            add_action('user_admin_notices', array(__CLASS__, 'admin_notices'));
        }
    }

    /**
     * Get the install date for comparisons. Sets the date to now if none is found.
     *
     * @return false|string
     */
    public static function installed_on()
    {
        $installed_on = get_option('ppch_reviews_installed_on', false);

        if (! $installed_on) {
            $installed_on = current_time('mysql');
            update_option('ppch_reviews_installed_on', $installed_on);
        }

        return $installed_on;
    }

    /**
     *
     */
    public static function ajax_handler()
    {
        $args = wp_parse_args($_REQUEST, array(
            'group' => self::get_trigger_group(),
            'code' => self::get_trigger_code(),
            'pri' => self::get_current_trigger('pri'),
            'reason' => 'maybe_later',
        ));

        if (! wp_verify_nonce($_REQUEST['nonce'], 'ppch_review_action')) {
            wp_send_json_error();
        }

        try {
            $user_id = get_current_user_id();

            $dismissed_triggers = self::dismissed_triggers();
            $dismissed_triggers[$args['group']] = $args['pri'];
            update_user_meta($user_id, '_ppch_reviews_dismissed_triggers', $dismissed_triggers);
            update_user_meta($user_id, '_ppch_reviews_last_dismissed', current_time('mysql'));

            switch ($args['reason']) {
                case 'maybe_later':
                    update_user_meta($user_id, '_ppch_reviews_last_dismissed', current_time('mysql'));
                    break;
                case 'am_now':
                case 'already_did':
                    self::already_did(true);
                    break;
            }

            wp_send_json_success();

        } catch (Exception $e) {
            wp_send_json_error($e);
        }
    }

    /**
     * @return int|string
     */
    public static function get_trigger_group()
    {
        static $selected;

        if (! isset($selected)) {

            $dismissed_triggers = self::dismissed_triggers();

            $triggers = self::triggers();

            foreach ($triggers as $g => $group) {
                foreach ($group['triggers'] as $t => $trigger) {
                    if (! in_array(false, $trigger['conditions']) && (empty($dismissed_triggers[$g]) || $dismissed_triggers[$g] < $trigger['pri'])) {
                        $selected = $g;
                        break;
                    }
                }

                if (isset($selected)) {
                    break;
                }
            }
        }

        return $selected;
    }

    /**
     * Returns an array of dismissed trigger groups.
     *
     * Array contains the group key and highest priority trigger that has been shown previously for each group.
     *
     * $return = array(
     *   'group1' => 20
     * );
     *
     * @return array|mixed
     */
    public static function dismissed_triggers()
    {
        $user_id = get_current_user_id();

        $dismissed_triggers = get_user_meta($user_id, '_ppch_reviews_dismissed_triggers', true);

        if (! $dismissed_triggers) {
            $dismissed_triggers = array();
        }

        return $dismissed_triggers;
    }

    /**
     * Gets a list of triggers.
     *
     * @param null $group
     * @param null $code
     *
     * @return bool|mixed|void
     */
    public static function triggers($group = null, $code = null)
    {
        static $triggers;

        if (! isset($triggers)) {

            $time_message = __("Hey, you've been using PublishPress Checklists for %s on your site - I hope that its been helpful. I would very much appreciate if you could quickly give it a 5-star rating on WordPress, just to help us spread the word.", 'publishpress-checklists');

            $triggers = apply_filters('ppch_reviews_triggers', array(
                'time_installed' => array(
                    'triggers' => array(
                        'one_week' => array(
                            'message' => sprintf($time_message, __('1 week', 'publishpress-checklists')),
                            'conditions' => array(
                                strtotime(self::installed_on() . ' +1 week') < time(),
                            ),
                            'link' => 'https://wordpress.org/support/plugin/publishpress-checklists/reviews/?rate=5#rate-response',
                            'pri' => 10,
                        ),
                        'one_month' => array(
                            'message' => sprintf($time_message, __('1 month', 'publishpress-checklists')),
                            'conditions' => array(
                                strtotime(self::installed_on() . ' +1 month') < time(),
                            ),
                            'link' => 'https://wordpress.org/support/plugin/publishpress-checklists/reviews/?rate=5#rate-response',
                            'pri' => 20,
                        ),
                        'three_months' => array(
                            'message' => sprintf($time_message, __('3 months', 'publishpress-checklists')),
                            'conditions' => array(
                                strtotime(self::installed_on() . ' +3 months') < time(),
                            ),
                            'link' => 'https://wordpress.org/support/plugin/publishpress-checklists/reviews/?rate=5#rate-response',
                            'pri' => 30,
                        ),

                    ),
                    'pri' => 10,
                ),
            ));

            // Sort Groups
            uasort($triggers, array(__CLASS__, 'rsort_by_priority'));

            // Sort each groups triggers.
            foreach ($triggers as $k => $v) {
                uasort($triggers[$k]['triggers'], array(__CLASS__, 'rsort_by_priority'));
            }
        }

        if (isset($group)) {
            if (! isset($triggers[$group])) {
                return false;
            }

            if (! isset($code)) {
                $return = $triggers[$group];
            } elseif (isset($triggers[$group]['triggers'][$code])) {
                $return = $triggers[$group]['triggers'][$code];
            } else {
                $return = false;
            }

            return $return;
        }

        return $triggers;
    }

    /**
     * @return int|string
     */
    public static function get_trigger_code()
    {
        static $selected;

        if (! isset($selected)) {

            $dismissed_triggers = self::dismissed_triggers();

            foreach (self::triggers() as $g => $group) {
                foreach ($group['triggers'] as $t => $trigger) {
                    if (! in_array(false, $trigger['conditions']) && (empty($dismissed_triggers[$g]) || $dismissed_triggers[$g] < $trigger['pri'])) {
                        $selected = $t;
                        break;
                    }
                }

                if (isset($selected)) {
                    break;
                }
            }
        }

        return $selected;
    }

    /**
     * @param null $key
     *
     * @return bool|mixed|void
     */
    public static function get_current_trigger($key = null)
    {
        $group = self::get_trigger_group();
        $code = self::get_trigger_code();

        if (! $group || ! $code) {
            return false;
        }

        $trigger = self::triggers($group, $code);

        if (empty($key)) {
            $return = $trigger;
        } elseif (isset($trigger[$key])) {
            $return = $trigger[$key];
        } else {
            $return = false;
        }

        return $return;
    }

    /**
     * Returns true if the user has opted to never see this again. Or sets the option.
     *
     * @param bool $set If set this will mark the user as having opted to never see this again.
     *
     * @return bool
     */
    public static function already_did($set = false)
    {
        $user_id = get_current_user_id();

        if ($set) {
            update_user_meta($user_id, '_ppch_reviews_already_did', true);

            return true;
        }

        return (bool)get_user_meta($user_id, '_ppch_reviews_already_did', true);
    }

    /**
     * Render admin notices if available.
     */
    public static function admin_notices()
    {
        if (self::hide_notices()) {
            return;
        }

        $group = self::get_trigger_group();
        $code = self::get_trigger_code();
        $pri = self::get_current_trigger('pri');
        $tigger = self::get_current_trigger();

        // Used to anonymously distinguish unique site+user combinations in terms of effectiveness of each trigger.
        $uuid = wp_hash(home_url() . '-' . get_current_user_id());

        ?>

        <script type="text/javascript">
            (function ($) {
                var trigger = {
                    group: '<?php echo $group; ?>',
                    code: '<?php echo $code; ?>',
                    pri: '<?php echo $pri; ?>'
                };

                function dismiss(reason) {
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        url: ajaxurl,
                        data: {
                            action: 'ppch_review_action',
                            nonce: '<?php echo wp_create_nonce('ppch_review_action'); ?>',
                            group: trigger.group,
                            code: trigger.code,
                            pri: trigger.pri,
                            reason: reason
                        }
                    });

                    <?php if ( ! empty(self::$api_url) ) : ?>
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        url: '<?php echo self::$api_url; ?>',
                        data: {
                            trigger_group: trigger.group,
                            trigger_code: trigger.code,
                            reason: reason,
                            uuid: '<?php echo $uuid; ?>'
                        }
                    });
                    <?php endif; ?>
                }

                $(document)
                    .on('click', '.ppch-notice .ppch-dismiss', function (event) {
                        var $this = $(this),
                            reason = $this.data('reason'),
                            notice = $this.parents('.ppch-notice');

                        notice.fadeTo(100, 0, function () {
                            notice.slideUp(100, function () {
                                notice.remove();
                            });
                        });

                        dismiss(reason);
                    })
                    .ready(function () {
                        setTimeout(function () {
                            $('.ppch-notice button.notice-dismiss').click(function (event) {
                                dismiss('maybe_later');
                            });
                        }, 1000);
                    });
            }(jQuery));
        </script>

        <div class="notice notice-success is-dismissible ppch-notice">

            <p>
                <?php echo $tigger['message']; ?>
            </p>
            <p>
                <a class="button button-primary ppch-dismiss" target="_blank"
                   href="https://wordpress.org/support/plugin/publishpress-checklists/reviews/?rate=5#rate-response"
                   data-reason="am_now">
                    <strong><?php _e('Ok, you deserve it', 'publishpress-checklists'); ?></strong>
                </a> <a href="#" class="button ppch-dismiss" data-reason="maybe_later">
                    <?php _e('Nope, maybe later', 'publishpress-checklists'); ?>
                </a> <a href="#" class="button ppch-dismiss" data-reason="already_did">
                    <?php _e('I already did', 'publishpress-checklists'); ?>
                </a>
            </p>

        </div>

        <?php
    }

    /**
     * Checks if notices should be shown.
     *
     * @return bool
     */
    public static function hide_notices()
    {
        $conditions = array(
            self::already_did(),
            self::last_dismissed() && strtotime(self::last_dismissed() . ' +2 weeks') > time(),
            empty(self::get_trigger_code()),
        );

        return in_array(true, $conditions);
    }

    /**
     * Gets the last dismissed date.
     *
     * @return false|string
     */
    public static function last_dismissed()
    {
        $user_id = get_current_user_id();

        return get_user_meta($user_id, '_ppch_reviews_last_dismissed', true);
    }

    /**
     * Sort array by priority value
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    public static function sort_by_priority($a, $b)
    {
        if (! isset($a['pri']) || ! isset($b['pri']) || $a['pri'] === $b['pri']) {
            return 0;
        }

        return ($a['pri'] < $b['pri']) ? -1 : 1;
    }

    /**
     * Sort array in reverse by priority value
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    public static function rsort_by_priority($a, $b)
    {
        if (! isset($a['pri']) || ! isset($b['pri']) || $a['pri'] === $b['pri']) {
            return 0;
        }

        return ($a['pri'] < $b['pri']) ? 1 : -1;
    }

}

Ppch_Modules_Reviews::init();
