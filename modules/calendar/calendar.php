<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2018 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use PublishPress\Notifications\Traits\Dependency_Injector;

if (!class_exists('PP_Calendar')) {
    /**
     * class PP_Calendar
     * Threaded commenting in the admin for discussion between writers and editors
     *
     * @author batmoo
     */
    class PP_Calendar extends PP_Module
    {
        use Dependency_Injector;

        /**
         * Settings slug
         */
        const SETTINGS_SLUG = 'pp-calendar';

        /**
         * Usermeta key prefix
         */
        const USERMETA_KEY_PREFIX = 'pp_calendar_';

        /**
         * Default number of weeks to display in the calendar
         */
        const DEFAULT_NUM_WEEKS = 5;

        /**
         * Name of the transient option to flag the warning for selecting
         * at least one post type
         */
        const TRANSIENT_SHOW_ONE_POST_TYPE_WARNING = 'show_one_post_type_warning';

        /**
         * The menu slug.
         */
        const MENU_SLUG = 'pp-calendar';

        /**
         * Time 12h-format without leading zeroes.
         */
        const TIME_FORMAT_12H_WO_LEADING_ZEROES = 'ga';

        /**
         * Time 12h-format with leading zeroes.
         */
        const TIME_FORMAT_12H_WITH_LEADING_ZEROES = 'ha';

        /**
         * Time 24h-format with leading zeroes.
         */
        const TIME_FORMAT_24H = 'H';

        /**
         * [$module description]
         *
         * @var [type]
         */
        public $module;

        /**
         * [$start_date description]
         *
         * @var string
         */
        public $start_date = '';

        /**
         * [$current_week description]
         *
         * @var integer
         */
        public $current_week = 1;

        /**
         * Default number of weeks to show per screen
         *
         * @var integer
         */
        public $total_weeks = 6;

        /**
         * Counter of hidden posts per date square
         *
         * @var integer
         */
        public $hidden = 0;

        /**
         * Total number of posts to be shown per square before 'more' link
         *
         * @var integer
         */
        public $default_max_visible_posts_per_date = 4;

        /**
         * [$post_date_cache description]
         *
         * @var array
         */
        private $post_date_cache = [];

        /**
         * [$post_li_html_cache_key description]
         *
         * @var string
         */
        private static $post_li_html_cache_key = 'pp_calendar_post_li_html';

        /**
         * Store default WordPress Date and Time formats for caching purposes.
         *
         * @var string
         */
        private $default_date_time_format = 'ha';

        /**
         * Construct the PP_Calendar class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                 => __('Calendar', 'publishpress'),
                'short_description'     => false,
                'extended_description'  => false,
                'module_url'            => $this->module_url,
                'icon_class'            => 'dashicons dashicons-calendar-alt',
                'slug'                  => 'calendar',
                'post_type_support'     => 'pp_calendar',
                'default_options'       => [
                    'enabled'                 => 'on',
                    'post_types'              => $this->pre_select_all_post_types(),
                    'ics_subscription'        => 'on',
                    'ics_secret_key'          => wp_generate_password(),
                    'show_posts_publish_time' => ['publish' => 'on', 'future' => 'on'],
                    'default_publish_time'    => '',
                ],
                'messages'              => [
                    'post-date-updated'   => __('Post date updated.', 'publishpress'),
                    'update-error'        => __(
                        'There was an error updating the post. Please try again.',
                        'publishpress'
                    ),
                    'published-post-ajax' => __(
                        "Updating the post date dynamically doesn't work for published content. Please <a href='%s'>edit the post</a>.",
                        'publishpress'
                    ),
                    'key-regenerated'     => __(
                        'iCal secret key regenerated. Please inform all users they will need to resubscribe.',
                        'publishpress'
                    ),
                ],
                'configure_page_cb'     => 'print_configure_view',
                'settings_help_tab'     => [
                    'id'      => 'pp-calendar-overview',
                    'title'   => __('Overview', 'publishpress'),
                    'content' => __(
                        '<p>The calendar is a convenient week-by-week or month-by-month view into your content. Quickly see which stories are on track to being published on time, and which will need extra effort.</p>',
                        'publishpress'
                    ),
                ],
                'settings_help_sidebar' => __(
                    '<p><strong>For more information:</strong></p><p><a href="https://publishpress.com/features/calendar/">Calendar Documentation</a></p><p><a href="https://github.com/ostraining/PublishPress">PublishPress on Github</a></p>',
                    'publishpress'
                ),
                'show_configure_btn'    => false,
                'options_page'          => true,
                'page_link'             => admin_url('admin.php?page=pp-calendar'),
            ];

            $this->module = PublishPress()->register_module('calendar', $args);
        }

        /**
         * Initialize all of our methods and such. Only runs if the module is active
         *
         * @uses add_action()
         */
        public function init()
        {
            add_action('template_include', [$this, 'handle_public_calendar_feed']);

            $this->setDefaultCapabilities();

            // Can view the calendar?
            if (!$this->check_capability()) {
                return false;
            }

            // Menu
            add_filter('publishpress_admin_menu_slug', [$this, 'filter_admin_menu_slug']);
            add_action('publishpress_admin_menu_page', [$this, 'action_admin_menu_page']);
            add_action('publishpress_admin_submenu', [$this, 'action_admin_submenu']);

            // .ics calendar subscriptions
            add_action('wp_ajax_pp_calendar_ics_subscription', [$this, 'handle_ics_subscription']);
            add_action('wp_ajax_nopriv_pp_calendar_ics_subscription', [$this, 'handle_ics_subscription']);

            // Define the create-post capability
            $this->create_post_cap = apply_filters('pp_calendar_create_post_cap', 'edit_posts');

            require_once PUBLISHPRESS_BASE_PATH . '/common/php/' . 'screen-options.php';

            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_print_styles', [$this, 'add_admin_styles']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

            // Ajax manipulation for the calendar
            add_action('wp_ajax_pp_calendar_drag_and_drop', [$this, 'handle_ajax_drag_and_drop']);

            // Ajax insert post placeholder for a specific date
            add_action('wp_ajax_pp_insert_post', [$this, 'handle_ajax_insert_post']);

            // Update metadata
            add_action('wp_ajax_pp_calendar_update_metadata', [$this, 'handle_ajax_update_metadata']);

            add_action('wp_ajax_publishpress_calendar_search_authors', [$this, 'searchAuthors']);
            add_action('wp_ajax_publishpress_calendar_search_categories', [$this, 'searchCategories']);
            add_action('wp_ajax_publishpress_calendar_search_tags', [$this, 'searchTags']);

            // Clear li cache for a post when post cache is cleared
            add_action('clean_post_cache', [$this, 'action_clean_li_html_cache']);

            // Action to regenerate the calendar feed secret
            add_action('admin_init', [$this, 'handle_regenerate_calendar_feed_secret']);

            add_filter('post_date_column_status', [$this, 'filter_post_date_column_status'], 12, 4);

            // Cache WordPress default date/time formats.
            $this->default_date_time_format = get_option('date_format') . ' ' . get_option('time_format');

            add_filter('pp_calendar_after_form_submission_sanitize_title', [$this, 'sanitize_text_input'], 10, 1);
            add_filter('pp_calendar_after_form_submission_sanitize_content', [$this, 'sanitize_text_input'], 10, 1);
            add_filter('pp_calendar_after_form_submission_sanitize_author', [$this, 'sanitize_author_input'], 10, 1);
            add_filter('pp_calendar_after_form_submission_validate_author', [$this, 'validate_author_input'], 10, 1);
        }

        /**
         * @param $original_template
         *
         * @return mixed
         */
        public function handle_public_calendar_feed($original_template)
        {
            // Only do .ics subscriptions when the option is active
            if ('on' != $this->module->options->ics_subscription) {
                return $original_template;
            }

            // Confirm all of the arguments are present
            if (!isset($_GET['user'], $_GET['user_key'], $_GET['pp_action'])) {
                return $original_template;
            }

            // Confirm the action
            if ('pp_calendar_ics_feed' !== $_GET['pp_action']) {
                return $original_template;
            }

            add_filter('pp_calendar_total_weeks', [$this, 'filter_calendar_total_weeks_public_feed'], 10, 3);
            add_filter(
                'pp_calendar_ics_subscription_start_date',
                [$this, 'filter_calendar_start_date_public_feed'],
                10
            );

            $this->handle_ics_subscription();

            remove_filter('pp_calendar_total_weeks', [$this, 'filter_calendar_total_weeks_public_feed']);
            remove_filter('pp_calendar_ics_subscription_start_date', [$this, 'filter_calendar_start_date_public_feed']);
        }

        /**
         * @param $weeks
         * @param $startDate
         * @param $context
         *
         * @return float|int
         */
        public function filter_calendar_total_weeks_public_feed($weeks, $startDate, $context)
        {
            if (!isset($_GET['end'])) {
                $end = 'm2';
            } else {
                $end = preg_replace('/[^wm0-9]/', '', sanitize_text_field($_GET['end']));
            }

            if (preg_match('/m[0-9]*/', $end)) {
                $weeks = (int)str_replace('m', '', $end) * 4;
            } else {
                $weeks = (int)str_replace('w', '', $end);
            }

            // Calculate the diff in weeks from start date until now
            $today = date('Y-m-d');

            $first  = DateTime::createFromFormat('Y-m-d', $startDate);
            $second = DateTime::createFromFormat('Y-m-d', $today);

            $diff = floor($first->diff($second)->days / 7);

            $weeks += $diff;

            return $weeks;
        }

        /**
         * @param $startDate
         *
         * @return false|string
         */
        public function filter_calendar_start_date_public_feed($startDate)
        {
            if (!isset($_GET['start'])) {
                // Current week
                $start = 0;
            } else {
                $start = (int)$_GET['start'];
            }

            if ($start > 0) {
                $startDate = date('Y-m-d', strtotime('-' . $start . ' months', strtotime($startDate)));
            }

            return $startDate;
        }

        /**
         * Filters the menu slug.
         *
         * @param $menu_slug
         *
         * @return string
         */
        public function filter_admin_menu_slug($menu_slug)
        {
            if (empty($menu_slug) && $this->module_enabled('calendar')) {
                $menu_slug = self::MENU_SLUG;
            }

            return $menu_slug;
        }

        /**
         * Creates the admin menu if there is no menu set.
         */
        public function action_admin_menu_page()
        {
            $publishpress = $this->get_service('publishpress');

            if ($publishpress->get_menu_slug() !== self::MENU_SLUG) {
                return;
            }

            $publishpress->add_menu_page(
                esc_html__('Calendar', 'publishpress'),
                'pp_view_calendar',
                self::MENU_SLUG,
                [$this, 'render_admin_page']
            );
        }

        /**
         * Add necessary things to the admin menu
         */
        public function action_admin_submenu()
        {
            $publishpress = $this->get_service('publishpress');

            // Main Menu
            add_submenu_page(
                $publishpress->get_menu_slug(),
                esc_html__('Calendar', 'publishpress'),
                esc_html__('Calendar', 'publishpress'),
                apply_filters('pp_view_calendar_cap', 'pp_view_calendar'),
                self::MENU_SLUG,
                [$this, 'render_admin_page'],
                5
            );
        }

        /**
         * Load the capabilities onto users the first time the module is run
         *
         * @since 0.7
         */
        public function install()
        {
        }

        /**
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previous_version)
        {
            global $publishpress;

            // Upgrade path to v0.7
            if (version_compare($previous_version, '0.7', '<')) {
                // Migrate whether the calendar was enabled or not and clean up old option
                if ($enabled = get_option('publishpress_calendar_enabled')) {
                    $enabled = 'on';
                } else {
                    $enabled = 'off';
                }
                $publishpress->update_module_option($this->module->name, 'enabled', $enabled);
                delete_option('publishpress_calendar_enabled');

                // Technically we've run this code before so we don't want to auto-install new data
                $publishpress->update_module_option($this->module->name, 'loaded_once', true);
            }
        }

        /**
         * Add any necessary CSS to the WordPress admin
         *
         * @uses wp_enqueue_style()
         */
        public function add_admin_styles()
        {
            global $pagenow;

            // Only load calendar styles on the calendar page
            if ('admin.php' === $pagenow && isset($_GET['page']) && $_GET['page'] === 'pp-calendar') {
                wp_enqueue_style(
                    'publishpress-calendar-css',
                    $this->module_url . 'lib/calendar.css',
                    ['publishpress-select2'],
                    PUBLISHPRESS_VERSION
                );

                wp_enqueue_style(
                    'publishpress-select2',
                    PUBLISHPRESS_URL . 'common/libs/select2-v4.0.13.1/css/select2.min.css',
                    false,
                    PUBLISHPRESS_VERSION,
                    'screen'
                );
            }
        }

        /**
         * Check whether the user should have the ability to view the calendar.
         * Returns true if the user can view.
         *
         * @return bool
         */
        protected function check_capability()
        {
            $view_calendar_cap = 'pp_view_calendar';
            $view_calendar_cap = apply_filters('pp_view_calendar_cap', $view_calendar_cap);

            return current_user_can($view_calendar_cap);
        }

        /**
         * Add any necessary JS to the WordPress admin
         *
         * @since 0.7
         * @uses  wp_enqueue_script()
         */
        public function enqueue_admin_scripts()
        {
            if ($this->is_whitelisted_functional_view()) {
                $this->enqueue_datepicker_resources();

                $js_libraries = [
                    'jquery',
                    'jquery-ui-core',
                    'jquery-ui-sortable',
                    'jquery-ui-draggable',
                    'jquery-ui-droppable',
                    'clipboard-js',
                    'publishpress-select2'
                ];
                foreach ($js_libraries as $js_library) {
                    wp_enqueue_script($js_library);
                }
                wp_enqueue_script(
                    'clipboard-js',
                    $this->module_url . 'lib/clipboard.min.js',
                    ['jquery'],
                    PUBLISHPRESS_VERSION,
                    true
                );
                wp_enqueue_script(
                    'publishpress-calendar-js',
                    $this->module_url . 'lib/calendar.js',
                    $js_libraries,
                    PUBLISHPRESS_VERSION,
                    true
                );

                wp_enqueue_script(
                    'publishpress-select2',
                    PUBLISHPRESS_URL . 'common/libs/select2-v4.0.13.1/js/select2.min.js',
                    ['jquery'],
                    PUBLISHPRESS_VERSION
                );

                $pp_cal_js_params = [
                    'can_add_posts' => current_user_can($this->create_post_cap) ? 'true' : 'false',
                    'nonce' => wp_create_nonce('calendar_filter_nonce'),
                ];
                wp_localize_script('publishpress-calendar-js', 'pp_calendar_params', $pp_cal_js_params);
            }
        }

        /**
         * Handle an AJAX request from the calendar to update a post's timestamp.
         * Notes:
         * - For Post Time, if the post is unpublished, the change sets the publication timestamp
         * - If the post was published or scheduled for the future, the change will change the timestamp. 'publish' posts
         * will become scheduled if moved past today and 'future' posts will be published if moved before today
         * - Need to respect user permissions. Editors can move all, authors can move their own, and contributors can't move at all
         *
         * @since 0.7
         */
        public function handle_ajax_drag_and_drop()
        {
            global $wpdb;

            if (!isset($_POST['nonce'])
                || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'pp-calendar-modify')
            ) {
                $this->print_ajax_response('error', $this->module->messages['nonce-failed']);
            }

            // Check that we got a proper post
            $post_id = (int)$_POST['post_id'];
            $post    = get_post($post_id);
            if (!$post) {
                $this->print_ajax_response('error', $this->module->messages['missing-post']);
            }

            // Check that the user can modify the post
            if (!$this->current_user_can_modify_post($post)) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            // Check that the new date passed is a valid one
            $next_date_full = strtotime(sanitize_text_field($_POST['next_date']));
            if (!$next_date_full) {
                $this->print_ajax_response(
                    'error',
                    esc_html__('Something is wrong with the format for the new date.', 'publishpress')
                );
            }

            // Persist the old timestamp because we can't manipulate the exact time on the calendar
            // Bump the last modified timestamps too
            $existing_time     = date('H:i:s', strtotime($post->post_date));
            $existing_time_gmt = date('H:i:s', strtotime($post->post_date_gmt));
            $new_values        = [
                'post_date'         => date('Y-m-d', $next_date_full) . ' ' . $existing_time,
                'post_modified'     => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
            ];

            // By default, adding a post to the calendar will set the timestamp.
            // If the user don't desires that to be the behavior, they can set the result of this filter to 'false'
            // With how WordPress works internally, setting 'post_date_gmt' will set the timestamp
            if (apply_filters('pp_calendar_allow_ajax_to_set_timestamp', true)) {
                $new_values['post_date_gmt'] = date('Y-m-d', $next_date_full) . ' ' . $existing_time_gmt;
            }

            // Check that it's already published, and adjust the status.
            // If is in the past or today, set as published. If future, as scheduled.
            $new_values['post_status'] = $post->post_status;
            if (in_array($post->post_status, $this->published_statuses) || $post->post_status === 'future') {
                if ($next_date_full <= time()) {
                    $new_values['post_status'] = 'publish';
                } else {
                    $new_values['post_status'] = 'future';
                }
            }

            // We have to do SQL unfortunately because of core bugginess
            // Note to those reading this: bug Nacin to allow us to finish the custom status API
            // See http://core.trac.wordpress.org/ticket/18362
            $response = $wpdb->update(
                $wpdb->posts,
                $new_values,
                [
                    'ID' => $post->ID,
                ]
            );
            clean_post_cache($post->ID);
            if (!$response) {
                $this->print_ajax_response('error', $this->module->messages['update-error']);
            }

            $data = [
                'post_status' => $new_values['post_status'],
            ];
            $this->print_ajax_response('success', $this->module->messages['post-date-updated'], $data);
            exit;
        }

        private function getTimezoneString()
        {
            $timezoneString = get_option('timezone_string');

            if (empty($timezoneString)) {
                $offset = get_option('gmt_offset');

                if ($offset > 0) {
                    $offset = '+' . $offset;
                }

                if (2 === strlen($offset)) {
                    $offset .= ':00';
                }

                $timezoneString = new DateTimeZone($offset);
                $timezoneString = $timezoneString->getName();
            }

            return $timezoneString;
        }

        /**
         * Returns a VTIMEZONE component for a Olson timezone identifier
         * with daylight transitions covering the given date range.
         *
         * @param string Timezone ID as used in PHP's Date functions
         * @param integer Unix timestamp with first date/time in this timezone
         * @param integer Unix timestap with last date/time in this timezone
         *
         * @return mixed A Sabre\VObject\Component object representing a VTIMEZONE definition
         *               or false if no timezone information is available
         */
        private function generateVTimeZone(&$calendar, $tzid, $from = 0, $to = 0)
        {
            if (!$from) {
                $from = time();
            }
            if (!$to) {
                $to = $from;
            }

            try {
                $tz = new DateTimeZone($tzid);
            } catch (Exception $e) {
                return false;
            }

            // get all transitions for one year back/ahead
            $year        = 86400 * 360;
            $transitions = $tz->getTransitions($from - $year, $to + $year);

            $vTimeZone = $calendar->add(
                'VTIMEZONE',
                [
                    'TZID' => $tz->getName(),
                ]
            );

            $standard = null;
            $daylight = null;
            $t_std    = null;
            $t_dst    = null;

            foreach ($transitions as $i => $trans) {
                $cmp = null;

                // daylight saving time definition
                if ($trans['isdst']) {
                    $t_dst = $trans['ts'];
                    $dt     = new DateTime($trans['time']);
                    $offset = $trans['offset'] / 3600;

                    $daylight = $vTimeZone->add(
                        'DAYLIGHTe',
                        [
                            'DTSTART' =>$dt->format('Ymd\THis'),
                            'TZOFFSETFROM' => sprintf(
                                '%s%02d%02d',
                                $tzfrom >= 0 ? '+' : '',
                                floor($tzfrom),
                                ($tzfrom - floor($tzfrom)) * 60
                            ),
                            'TZOFFSETTO' => sprintf(
                                '%s%02d%02d',
                                $offset >= 0 ? '+' : '',
                                floor($offset),
                                ($offset - floor($offset)) * 60
                            ),
                        ]
                    );

                    // add abbreviated timezone name if available
                    if (!empty($trans['abbr'])) {
                        $daylight->add('TZNAME', [$trans['abbr']]);
                    }

                    $tzfrom = $offset;
                } else {
                    $t_std = $trans['ts'];
                    $dt     = new DateTime($trans['time']);
                    $offset = $trans['offset'] / 3600;

                    $standard = $vTimeZone->add(
                        'STANDARD',
                        [
                            'DTSTART' =>$dt->format('Ymd\THis'),
                            'TZOFFSETFROM' => sprintf(
                                '%s%02d%02d',
                                $tzfrom >= 0 ? '+' : '',
                                floor($tzfrom),
                                ($tzfrom - floor($tzfrom)) * 60
                            ),
                            'TZOFFSETTO' => sprintf(
                                '%s%02d%02d',
                                $offset >= 0 ? '+' : '',
                                floor($offset),
                                ($offset - floor($offset)) * 60
                            ),
                        ]
                    );

                    // add abbreviated timezone name if available
                    if (!empty($trans['abbr'])) {
                        $standard->add('TZNAME', [$trans['abbr']]);
                    }

                    $tzfrom = $offset;
                }

                // we covered the entire date range
                if ($standard && $daylight && min($t_std, $t_dst) < $from && max($t_std, $t_dst) > $to) {
                    break;
                }
            }

            // add X-MICROSOFT-CDO-TZID if available
            $microsoftExchangeMap = array_flip(Sabre\VObject\TimeZoneUtil::$microsoftExchangeMap);
            if (array_key_exists($tz->getName(), $microsoftExchangeMap)) {
                $vTimeZone->add('X-MICROSOFT-CDO-TZID', $microsoftExchangeMap[$tz->getName()]);
            }

            return $vTimeZone;
        }

        /**
         * After checking that the request is valid, do an .ics file
         *
         * @since 0.8
         */
        public function handle_ics_subscription()
        {
            // Only do .ics subscriptions when the option is active
            if ('on' != $this->module->options->ics_subscription) {
                die();
            }

            // Confirm all of the arguments are present
            if (!isset($_GET['user'], $_GET['user_key'])) {
                die();
            }

            // Confirm this is a valid request
            $user           = sanitize_user($_GET['user']);
            $user_key       = sanitize_user($_GET['user_key']);
            $ics_secret_key = $this->module->options->ics_secret_key;
            if (!$ics_secret_key || md5($user . $ics_secret_key) !== $user_key) {
                die($this->module->messages['nonce-failed']);
            }

            // Set up the post data to be printed
            $post_query_args  = [];
            $calendar_filters = $this->calendar_filters();
            foreach ($calendar_filters as $filter) {
                if (isset($_GET[$filter]) && false !== ($value = $this->sanitize_filter($filter, sanitize_text_field($_GET[$filter])))) {
                    $post_query_args[$filter] = $value;
                }
            }

            // Set the start date for the posts_where filter
            $this->start_date = apply_filters(
                'pp_calendar_ics_subscription_start_date',
                $this->get_beginning_of_week(date('Y-m-d', current_time('timestamp')))
            );

            $this->total_weeks = apply_filters(
                'pp_calendar_total_weeks',
                $this->total_weeks,
                $this->start_date,
                'ics_subscription'
            );

            $vCalendar = new Sabre\VObject\Component\VCalendar(
                [
                    'PRODID'  => '-//PublishPress//PublishPress ' . PUBLISHPRESS_VERSION . '//EN',
                ]
            );

            $timezoneString = $this->getTimezoneString();

            $this->generateVtimezone(
                $vCalendar,
                $timezoneString,
                strtotime($this->start_date),
                (int)(strtotime($this->start_date) + ($this->total_weeks * 7 * 24 * 60 * 60))
            );

            $timeZone = new DateTimeZone($timezoneString);

            for ($current_week = 1; $current_week <= $this->total_weeks; $current_week++) {
                // We need to set the object variable for our posts_where filter
                $this->current_week = $current_week;
                $week_posts         = $this->get_calendar_posts_for_week($post_query_args, 'ics_subscription');
                foreach ($week_posts as $date => $day_posts) {
                    foreach ($day_posts as $num => $post) {
                        $start_date    = new DateTime($post->post_date_gmt);
                        $start_date->setTimezone($timeZone);

                        $end_date      = new DateTime(date('Y-m-d H:i:s', strtotime($post->post_date_gmt) + (5 * 60)));
                        $end_date->setTimezone($timeZone);

                        $last_modified = new DateTime($post->post_modified_gmt);
                        $last_modified->setTimezone($timeZone);

                        // Remove the convert chars and wptexturize filters from the title
                        remove_filter('the_title', 'convert_chars');
                        remove_filter('the_title', 'wptexturize');

                        // Description should include everything visible in the calendar popup
                        $information_fields            = $this->get_post_information_fields($post);
                        $eventDescription = '';
                        if (!empty($information_fields)) {
                            foreach ($information_fields as $key => $values) {
                                $eventDescription .= $values['label'] . ': ' . $values['value'] . "\n";
                            }
                            $eventDescription = rtrim($eventDescription);
                        }

                        $vCalendar->add(
                            'VEVENT',
                            [
                                'UID'           => $post->guid,
                                'SUMMARY'       => $this->do_ics_escaping(apply_filters('the_title', $post->post_title))
                                    . ' - ' . $this->get_post_status_friendly_name(get_post_status($post->ID)),
                                'DTSTART'       => $start_date,
                                'DTEND'         => $end_date,
                                'LAST-MODIFIED' => $last_modified,
                                'URL'           => get_post_permalink($post->ID),
                                'DESCRIPTION'   => $eventDescription,
                            ]
                        );
                    }
                }
            }

            // Render the .ics template and set the content type
            header('Content-type: text/calendar; charset=utf-8');
            header('Content-Disposition: inline; filename=calendar.ics');
            echo $vCalendar->serialize();

            die();
        }

        /**
         * Perform the encoding necessary for ICS feed text.
         *
         * @param string $text The string that needs to be escaped
         *
         * @return string The string after escaping for ICS.
         * @since 0.8
         * */

        public function do_ics_escaping($text)
        {
            $text = str_replace(',', '\,', $text);
            $text = str_replace(';', '\:', $text);
            $text = str_replace('\\', '\\\\', $text);

            return $text;
        }

        /**
         * Handle a request to regenerate the calendar feed secret
         *
         * @since 0.8
         */
        public function handle_regenerate_calendar_feed_secret()
        {
            if (!isset($_GET['action']) || 'pp_calendar_regenerate_calendar_feed_secret' != $_GET['action']) {
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            if (!isset($_GET['_wpnonce'])
                || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'pp-regenerate-ics-key')
            ) {
                wp_die($this->module->messages['nonce-failed']);
            }

            PublishPress()->update_module_option($this->module->name, 'ics_secret_key', wp_generate_password());

            $args = [
                'page'   => PP_Modules_Settings::SETTINGS_SLUG,
                'module' => $this->module->settings_slug,
            ];

            wp_safe_redirect(
                add_query_arg(
                    'message',
                    'key-regenerated',
                    add_query_arg($args, admin_url('admin.php'))
                )
            );

            exit;
        }

        /**
         * Get the user's filters for calendar, either with $_GET or from saved
         *
         * @return array $filters All of the set or saved calendar filters
         * @uses get_user_meta()
         */
        public function get_filters()
        {
            $current_user = wp_get_current_user();
            $filters      = [];
            $old_filters  = $this->get_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', true);

            $default_filters = [
                'weeks'       => self::DEFAULT_NUM_WEEKS,
                'post_status' => '',
                'cpt'         => '',
                'cat'         => '',
                'tag'         => '',
                'author'      => '',
                'start_date'  => date('Y-m-d', current_time('timestamp')),
            ];
            $old_filters     = array_merge($default_filters, (array)$old_filters);

            // Sanitize and validate any newly added filters
            foreach ($old_filters as $key => $old_value) {
                if (isset($_GET[$key]) && false !== ($new_value = $this->sanitize_filter($key, sanitize_text_field($_GET[$key])))) {
                    $filters[$key] = $new_value;
                } else {
                    $filters[$key] = $old_value;
                }
            }

            // Fix start_date, if no specific date was set
            if (!isset($_GET['start_date'])) {
                $filters['start_date'] = $default_filters['start_date'];
            }

            // Set the start date as the beginning of the week, according to blog settings
            $filters['start_date'] = $this->get_beginning_of_week($filters['start_date']);

            $filters = apply_filters('pp_calendar_filter_values', $filters, $old_filters);

            $this->update_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', $filters);

            return $filters;
        }

        /**
         * Set all post types as selected, to be used as the default option.
         *
         * @return array
         */
        protected function pre_select_all_post_types()
        {
            $list = get_post_types(null, 'objects');

            foreach ($list as $type => $value) {
                $list[$type] = 'on';
            }

            return $list;
        }

        /**
         * Get an array of the selected post types.
         *
         * @return array
         */
        protected function get_selected_post_types()
        {
            $return = [];

            if (!isset($this->module->options->post_types)) {
                $this->module->options->post_types = [];
            }

            if (!empty($this->module->options->post_types)) {
                foreach ($this->module->options->post_types as $type => $value) {
                    if ('on' === $value) {
                        $return[] = $type;
                    }
                }
            }

            return $return;
        }

        /**
         * Renders the admin page
         */
        public function render_admin_page()
        {
            global $publishpress;

            $this->dropdown_taxonomies = [];

            $supported_post_types = $this->get_post_types_for_module($this->module);

            $dotw = [
                'Sat',
                'Sun',
            ];
            $dotw = apply_filters('pp_calendar_weekend_days', $dotw);

            // Get filters either from $_GET or from user settings
            $filters = $this->get_filters();

            // Total number of weeks to display on the calendar. Run it through a filter in case we want to override the
            // user's standard
            $this->total_weeks = empty($filters['weeks']) ? self::DEFAULT_NUM_WEEKS : $filters['weeks'];

            // For generating the WP Query objects later on
            $post_query_args  = [
                'post_status' => $filters['post_status'],
                'post_type'   => $filters['cpt'],
                'cat'         => $filters['cat'],
                'tag'         => $filters['tag'],
                'author'      => $filters['author'],
            ];
            $this->start_date = $filters['start_date'];

            // We use this later to label posts if they need labeling
            if (count($supported_post_types) > 1) {
                $all_post_types = get_post_types(null, 'objects');
            }
            $dates        = [];
            $heading_date = $filters['start_date'];
            for ($i = 0; $i < 7; $i++) {
                $dates[$i]    = $heading_date;
                $heading_date = date('Y-m-d', strtotime('+1 day', strtotime($heading_date)));
            }

            // we sort by post statuses....... eventually
            $post_statuses = $this->get_post_statuses();

            // Get the custom description for this page
            $description = sprintf(
                '%s <span class="time-range">%s</span>',
                esc_html__('Calendar', 'publishpress'),
                esc_html__($this->calendar_time_range())
            );

            // Should we display the subscribe button?
            if ('on' == $this->module->options->ics_subscription && $this->module->options->ics_secret_key) {
                // Prepare the download link
                $args = [
                    'action'   => 'pp_calendar_ics_subscription',
                    'user'     => wp_get_current_user()->user_login,
                    'user_key' => md5(wp_get_current_user()->user_login . $this->module->options->ics_secret_key),
                ];

                // Prepare the subscribe link for the feed
                unset($args['action']);
                $args['post_status'] = 'unpublish';
                $args['pp_action']   = 'pp_calendar_ics_feed';
                $subscription_link   = add_query_arg($args, site_url());

                $description .= '<div class="calendar-subscribe">';

                add_thickbox();

                ob_start(); ?>
                <div id="publishpress-calendar-ics-subs" style="display:none;">
                    <h3><?php echo esc_html__('PublishPress', 'publishpress'); ?>
                        - <?php echo esc_html__('Subscribe in iCal or Google Calendar', 'publishpress'); ?>
                    </h3>

                    <div>
                        <h4><?php echo esc_html__('Start date', 'publishpress'); ?></h4>
                        <select id="publishpress-start-date">
                            <option value="0"
                                    selected="selected"><?php echo esc_html__('Current week', 'publishpress'); ?></option>
                            <option value="1"><?php echo esc_html__('One month ago', 'publishpress'); ?></option>
                            <option value="2"><?php echo esc_html__('Two months ago', 'publishpress'); ?></option>
                            <option value="3"><?php echo esc_html__('Three months ago', 'publishpress'); ?></option>
                            <option value="4"><?php echo esc_html__('Four months ago', 'publishpress'); ?></option>
                            <option value="5"><?php echo esc_html__('Five months ago', 'publishpress'); ?></option>
                            <option value="6"><?php echo esc_html__('Six months ago', 'publishpress'); ?></option>
                        </select>

                        <br/>

                        <h4><?php echo esc_html__('End date', 'publishpress'); ?></h4>
                        <select id="publishpress-end-date">
                            <optgroup label="<?php echo esc_attr__('Weeks'); ?>">
                                <option value="w1"><?php echo esc_html__('One week', 'publishpress'); ?></option>
                                <option value="w2"><?php echo esc_html__('Two weeks', 'publishpress'); ?></option>
                                <option value="w3"><?php echo esc_html__('Three weeks', 'publishpress'); ?></option>
                                <option value="w4"><?php echo esc_html__('Four weeks', 'publishpress'); ?></option>
                            </optgroup>

                            <optgroup label="<?php echo esc_attr__('Months'); ?>">
                                <option value="m1"><?php echo esc_html__('One month', 'publishpress'); ?></option>
                                <option value="m2"
                                        selected="selected"><?php echo esc_html__('Two months', 'publishpress'); ?></option>
                                <option value="m3"><?php echo esc_html__('Three months', 'publishpress'); ?></option>
                                <option value="m4"><?php echo esc_html__('Four months', 'publishpress'); ?></option>
                                <option value="m5"><?php echo esc_html__('Five months', 'publishpress'); ?></option>
                                <option value="m6"><?php echo esc_html__('Six months', 'publishpress'); ?></option>
                                <option value="m7"><?php echo esc_html__('Seven months', 'publishpress'); ?></option>
                                <option value="m8"><?php echo esc_html__('Eight months', 'publishpress'); ?></option>
                                <option value="m9"><?php echo esc_html__('Nine months', 'publishpress'); ?></option>
                                <option value="m10"><?php echo esc_html__('Ten months', 'publishpress'); ?></option>
                                <option value="m11"><?php echo esc_html__('Eleven months', 'publishpress'); ?></option>
                                <option value="m12"><?php echo esc_html__('Twelve months', 'publishpress'); ?></option>
                            </optgroup>
                        </select>
                    </div>

                    <br/>

                    <a href="<?php echo esc_url($subscription_link); ?>" id="publishpress-ics-download"
                       style="margin-right: 20px;" class="button">
                        <span class="dashicons dashicons-download" style="text-decoration: none"></span>
                        <?php echo esc_html__('Download .ics file'); ?></a>

                    <button data-clipboard-text="<?php echo esc_attr($subscription_link); ?>" id="publishpress-ics-copy"
                            class="button-primary">
                        <span class="dashicons dashicons-clipboard" style="text-decoration: none"></span>
                        <?php echo esc_html__('Copy to the clipboard'); ?>
                    </button>
                </div>

                <a href="#TB_inline?width=550&height=270&inlineId=publishpress-calendar-ics-subs" class="thickbox">
                    <?php echo esc_html__('Click here to Subscribe in iCal or Google Calendar', 'publishpress'); ?>
                </a>
                <?php
                $description .= ob_get_clean();

                $description .= '</div>';
            }

            $publishpress->settings->print_default_header($publishpress->modules->calendar, $description); ?>
            <div class="wrap">
                <?php
                // Handle posts that have been trashed or untrashed
                if (isset($_GET['trashed']) || isset($_GET['untrashed'])) {
                    echo '<div id="trashed-message" class="updated"><p>';
                    if (isset($_GET['trashed']) && (int)$_GET['trashed']) {
                        printf(
                            _n(
                                'Post moved to the trash.',
                                '%d posts moved to the trash.',
                                (int)$_GET['trashed']
                            ),
                            number_format_i18n((int)$_GET['trashed'])
                        );
                        $ids       = isset($_GET['ids']) ? sanitize_text_field($_GET['ids']) : 0;
                        $pid       = explode(',', $ids);
                        $post_type = get_post_type($pid[0]);
                        echo ' <a href="' . esc_url(
                                wp_nonce_url(
                                    esc_url_raw("edit.php?post_type=$post_type&doaction=undo&action=untrash&ids=$ids"),
                                    'bulk-posts'
                                )
                            ) . '">' . esc_html__(
                                'Undo',
                                'publishpress'
                            ) . ' <span class="dashicons dashicons-undo"></span></a><br />';
                        unset($_GET['trashed']);
                    }

                    $untrashed = (int)$_GET['untrashed'];
                    if (isset($_GET['untrashed']) && $untrashed) {
                        printf(
                            _n(
                                'Post restored from the Trash.',
                                '%d posts restored from the Trash.',
                                $untrashed
                            ),
                            number_format_i18n($untrashed)
                        );
                        unset($_GET['undeleted']);
                    }
                    echo '</p></div>';
                } ?>

                <div id="pp-calendar-wrap"><!-- Calendar Wrapper -->

                    <?php $this->print_top_navigation($filters, $dates); ?>

                    <?php
                    $table_classes = [];
                    // CSS don't like our classes to start with numbers
                    if ($this->total_weeks == 1) {
                        $table_classes[] = 'one-week-showing';
                    } elseif ($this->total_weeks == 2) {
                        $table_classes[] = 'two-weeks-showing';
                    } elseif ($this->total_weeks == 3) {
                        $table_classes[] = 'three-weeks-showing';
                    }

                    $table_classes = apply_filters('pp_calendar_table_classes', $table_classes); ?>
                    <table id="pp-calendar-view" class="<?php echo esc_attr(implode(' ', $table_classes)); ?>">
                        <thead>
                        <tr class="calendar-heading">
                            <?php echo $this->get_time_period_header($dates); ?>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                        $default_post_status = PP_Custom_Status::DEFAULT_STATUS;

                        $current_month        = date_i18n('F', strtotime($filters['start_date']));
                        for ($current_week = 1; $current_week <= $this->total_weeks; $current_week++) :
                            // We need to set the object variable for our posts_where filter
                            $this->current_week = $current_week;
                            $week_posts       = $this->get_calendar_posts_for_week($post_query_args);
                            $date_format      = 'Y-m-d';
                            $week_single_date = $this->get_beginning_of_week(
                                $filters['start_date'],
                                $date_format,
                                $current_week
                            );
                            $week_dates       = [];
                            $split_month      = false;
                            for ($i = 0; $i < 7; $i++) {
                                $week_dates[$i]    = $week_single_date;
                                $single_date_month = date_i18n('F', strtotime($week_single_date));
                                if ($single_date_month != $current_month) {
                                    $split_month   = $single_date_month;
                                    $current_month = $single_date_month;
                                }
                                $week_single_date = date('Y-m-d', strtotime('+1 day', strtotime($week_single_date)));
                            } ?>
                            <?php if ($split_month) : ?>
                            <tr class="month-marker">
                                <?php foreach ($week_dates as $key => $week_single_date) {
                                    if (date_i18n('F', strtotime($week_single_date)) != $split_month && date_i18n(
                                            'F',
                                            strtotime('+1 day', strtotime($week_single_date))
                                        ) == $split_month) {
                                        $previous_month = date_i18n('F', strtotime($week_single_date));
                                        echo '<td class="month-marker-previous">' . esc_html($previous_month) . '</td>';
                                    } elseif (date_i18n(
                                            'F',
                                            strtotime($week_single_date)
                                        ) == $split_month && date_i18n(
                                            'F',
                                            strtotime('-1 day', strtotime($week_single_date))
                                        ) != $split_month) {
                                        echo '<td class="month-marker-current">' . esc_html($split_month) . '</td>';
                                    } else {
                                        echo '<td class="month-marker-empty"></td>';
                                    }
                                } ?>
                            </tr>
                        <?php endif; ?>

                            <tr class="week-unit noselect">
                                <?php foreach ($week_dates as $day_num => $week_single_date) : ?>
                                    <?php
                                    // Somewhat ghetto way of sorting all of the day's posts by post status order
                                    if (isset($this->module->options->sort_by) && $this->module->options->sort_by === 'status') {
                                        if (!empty($week_posts[$week_single_date])) {
                                            $week_posts_by_status = [];
                                            foreach ($post_statuses as $post_status) {
                                                $week_posts_by_status[$post_status->slug] = [];
                                            }
                                            // These statuses aren't handled by custom statuses or post statuses
                                            $week_posts_by_status['private'] = [];
                                            $week_posts_by_status['publish'] = [];
                                            $week_posts_by_status['future']  = [];
                                            foreach ($week_posts[$week_single_date] as $num => $post) {
                                                $week_posts_by_status[$post->post_status][$num] = $post;
                                            }
                                            unset($week_posts[$week_single_date]);
                                            foreach ($week_posts_by_status as $status) {
                                                foreach ($status as $num => $post) {
                                                    $week_posts[$week_single_date][] = $post;
                                                }
                                            }
                                        }
                                    }

                                    $td_classes = [
                                        'day-unit',
                                    ];
                                    $day_name   = date('D', strtotime($week_single_date));

                                    if (in_array($day_name, $dotw)) {
                                        $td_classes[] = 'weekend-day';
                                    }

                                    if ($week_single_date == date('Y-m-d', current_time('timestamp'))) {
                                        $td_classes[] = 'today';
                                    }

                                    // Last day of the week
                                    if ($day_num == 6) {
                                        $td_classes[] = 'last-day';
                                    }

                                    $td_classes = apply_filters(
                                        'pp_calendar_table_td_classes',
                                        $td_classes,
                                        $week_single_date
                                    ); ?>
                                    <td class="<?php echo esc_attr(implode(' ', $td_classes)); ?>"
                                        id="<?php echo esc_attr($week_single_date); ?>">
                                        <div class="day-wrapper">
                                            <div class='schedule-new-post-label'>
                                                <?php echo esc_html__('Click to create', 'publishpress'); ?>
                                            </div>
                                            <?php $class = ($week_single_date == date(
                                                    'Y-m-d',
                                                    current_time('timestamp')
                                                )) ? 'calendar-today' : ''; ?>
                                            <div
                                                class="day-unit-label <?php echo esc_attr(
                                                    $class
                                                ); ?>"><?php echo esc_html(
                                                    date(
                                                        'j',
                                                        strtotime($week_single_date)
                                                    )
                                                ); ?></div>
                                            <ul class="post-list">
                                                <?php
                                                $this->hidden = 0;
                                                if (!empty($week_posts[$week_single_date])) {
                                                    $week_posts[$week_single_date] = apply_filters(
                                                        'pp_calendar_posts_for_week',
                                                        $week_posts[$week_single_date]
                                                    );

                                                    foreach ($week_posts[$week_single_date] as $num => $post) {
                                                        echo $this->generate_post_li_html(
                                                            $post,
                                                            $week_single_date,
                                                            $num
                                                        );
                                                    }
                                                } ?>
                                            </ul>
                                            <?php if ($this->hidden) : ?>
                                                <a class="show-more"
                                                   href="#"><?php printf(
                                                        esc_html__('Show %d more', 'publishpress'),
                                                        $this->hidden
                                                    ); ?></a>
                                            <?php endif; ?>

                                            <?php if (current_user_can($this->create_post_cap)) :
                                                $date_formatted = date('D, M jS, Y', strtotime($week_single_date)); ?>

                                                <form method="POST" class="post-insert-dialog">
                                                    <?php /* translators: %1$s = post type name, %2$s = date */ ?>
                                                    <?php $post_types = $this->get_selected_post_types(); ?>
                                                    <?php if (count($post_types) === 1) : ?>
                                                        <h1>
                                                            <?php echo sprintf(
                                                                esc_html__(
                                                                    'Schedule a %1$s for %2$s',
                                                                    'publishpress'
                                                                ),
                                                                $this->get_quick_create_post_type_name($post_types),
                                                                $date_formatted
                                                            ); ?>
                                                            <input type="hidden" id="post-insert-dialog-post-type-<?php echo esc_attr($week_single_date); ?>"
                                                                   name="post-insert-dialog-post-type"
                                                                   class="post-insert-dialog-post-type"
                                                                   value="<?php echo esc_attr($post_types[0]); ?>"/>
                                                        </h1>
                                                    <?php else : ?>
                                                        <h1>
                                                            <?php echo sprintf(
                                                                esc_html__(
                                                                    'Schedule content for %1$s',
                                                                    'publishpress'
                                                                ),
                                                                $date_formatted
                                                            ); ?>
                                                        </h1>
                                                        <label for="post-insert-dialog-post-type">
                                                            <?php echo __('Type', 'publishpress'); ?>
                                                            <select id="post-insert-dialog-post-type-<?php echo esc_attr($week_single_date); ?>"
                                                                    name="post-insert-dialog-post-type"
                                                                    class="post-insert-dialog-post-type">
                                                                <?php foreach ($post_types as $type) : ?>
                                                                    <option value="<?php echo esc_attr($type); ?>">
                                                                        <?php echo $this->get_quick_create_post_type_name(
                                                                            $type
                                                                        ); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <br/>
                                                    <?php endif; ?>

                                                    <label for="post-insert-dialog-post-author">
                                                        <?php echo __('Author', 'publishpress'); ?>
                                                        <select id="post-insert-dialog-post-author-<?php echo esc_attr($week_single_date); ?>"
                                                                name="post-insert-dialog-post-author"
                                                                class="post-insert-dialog-post-author">
                                                            <option value=""></option>
                                                        </select>
                                                    </label>

                                                    <div>
                                                        <label for="post-insert-dialog-post-status"><?php esc_html_e(
                                                                'Status',
                                                                'publishpress'
                                                            ); ?></label>
                                                        <select
                                                            id="post-insert-dialog-post-status-<?php echo esc_attr($week_single_date); ?>"
                                                            name="post-insert-dialog-post-status"
                                                            class="post-insert-dialog-post-status"
                                                        >
                                                            <?php foreach ($this->get_post_statuses() as $status): ?>
                                                                <option
                                                                    value="<?php echo esc_attr($status->slug); ?>"
                                                                    <?php echo $status->slug === $default_post_status ? 'selected' : ''; ?>
                                                                >
                                                                    <?php echo esc_html($status->name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label
                                                            for="post-insert-dialog-post-publish-time"><?php esc_html_e(
                                                                'Publish Time',
                                                                'publishpress'
                                                            ); ?></label>
                                                        <input
                                                            type="time"
                                                            id="post-insert-dialog-post-publish-time-<?php echo $week_single_date; ?>"
                                                            name="post-insert-dialog-post-publish-time"
                                                            class="post-insert-dialog-post-publish-time"
                                                            value="<?php echo isset($this->module->options->default_publish_time) ? esc_attr($this->module->options->default_publish_time) : ''; ?>"
                                                        />
                                                    </div>

                                                    <?php /* translators: %s = post type name */ ?>
                                                    <input type="text" class="post-insert-dialog-post-title"
                                                           name="post-insert-dialog-post-title"
                                                           placeholder="<?php echo esc_attr(
                                                               __(
                                                                   'Title',
                                                                   'publishpress'
                                                               )
                                                           ); ?>"/>
                                                    <textarea class="post-insert-dialog-post-content"
                                                              name="post-insert-dialog-post-content"
                                                              placeholder="<?php echo esc_attr(
                                                                  __(
                                                                      'Content',
                                                                      'publishpress'
                                                                  )
                                                              ); ?>"></textarea>

                                                    <input type="hidden" class="post-insert-dialog-post-date"
                                                           name="post-insert-dialog-post-title"
                                                           value="<?php echo esc_attr($week_single_date); ?>"/>

                                                    <div class="post-insert-dialog-controls">
                                                        <input type="submit" class="button left"
                                                               value="<?php echo esc_attr(
                                                                   __(
                                                                       'Create',
                                                                       'publishpress'
                                                                   )
                                                               ); ?>"/>
                                                        &nbsp;
                                                        <a class="post-insert-dialog-edit-post-link"
                                                           href="#"><?php echo esc_html(__('Edit', 'publishpress')); ?>
                                                            &nbsp;&raquo;</a>
                                                    </div>

                                                    <div class="spinner">&nbsp;</div>
                                                </form>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                        <?php endfor; ?>

                        </tbody>
                    </table><!-- /Week Wrapper -->
                    <?php
                    // Nonce field for AJAX actions
                    wp_nonce_field('pp-calendar-modify', 'pp-calendar-modify'); ?>

                    <div class="clear"></div>
                </div><!-- /Calendar Wrapper -->

            </div>

            <?php
            $publishpress->settings->print_default_footer($publishpress->modules->calendar);
        }

        /**
         * Generates the HTML for a single post item in the calendar
         *
         * @param obj $post The WordPress post in question
         * @param str $post_date The date of the post
         * @param int $num The index of the post
         *
         * @return str HTML for a single post item
         */
        public function generate_post_li_html($post, $post_date, $num = 0)
        {
            $can_modify = $this->current_user_can_modify_post($post);
            $cache_key  = sprintf(
                '%s%s',
                $post->ID,
                $can_modify ? 'can_modify' : 'read_only'
            );
            $cache_val  = wp_cache_get($cache_key, self::$post_li_html_cache_key);
            // Because $num is pertinent to the display of the post LI, need to make sure that's what's in cache
            if (is_array($cache_val) && $cache_val['num'] == $num) {
                $this->hidden = $cache_val['hidden'];

                return $cache_val['post_li_html'];
            }

            ob_start();

            $post_id        = $post->ID;
            $edit_post_link = get_edit_post_link($post_id);

            $show_posts_publish_time = $this->showPostsPublishTime($post->post_status);

            $post_publish_date_time = '';
            if ($show_posts_publish_time) {
                $post_publish_datetime       = get_the_date('c', $post);
                $post_publish_date_timestamp = get_post_time('U', false, $post);
                $posts_publish_time_format   = isset($this->module->options->posts_publish_time_format) && !empty(
                $this->module->options->posts_publish_time_format
                )
                    ? $this->module->options->posts_publish_time_format
                    : self::TIME_FORMAT_12H_WO_LEADING_ZEROES;
            }

            $post_classes = [
                'day-item',
                'custom-status-' . $post->post_status,
            ];

            $post_type_options = $this->get_post_status_options($post->post_status);

            // Only allow the user to drag the post if they have permissions to
            // or if it's in an approved post status
            // This is checked on the ajax request too.
            if ($can_modify) {
                $post_classes[] = 'sortable';
            } else {
                $post_classes[] = 'read-only';
            }

            if (in_array($post->post_status, $this->published_statuses)) {
                $post_classes[] = 'is-published';
            }

            // Hide posts over a certain number to prevent clutter, unless user is only viewing 1 or 2 weeks
            $max_visible_posts_option = isset($this->module->options->max_visible_posts_per_date) && !empty($this->default_max_visible_posts_per_date) ?
                (int)$this->module->options->max_visible_posts_per_date : $this->default_max_visible_posts_per_date;

            if ($max_visible_posts_option < 0) {
                $max_visible_posts_option = 9999;
            }

            $max_visible_posts = apply_filters(
                'pp_calendar_max_visible_posts_per_date',
                $max_visible_posts_option
            );

            if ($num >= $max_visible_posts && $this->total_weeks > 2) {
                $post_classes[] = 'hidden';
                $this->hidden++;
            }
            $post_classes = apply_filters('pp_calendar_table_td_li_classes', $post_classes, $post_date, $post->ID); ?>

            <li
                class="<?php echo esc_attr(implode(' ', $post_classes)); ?>"
                id="post-<?php echo esc_attr($post->ID); ?>">

                <div style="clear:right;"></div>
                <div class="item-static">
                    <div class="item-default-visible" style="background:<?php echo $post_type_options['color']; ?>;">
                        <div class="inner">
                            <span class="dashicons <?php echo esc_attr($post_type_options['icon']); ?>"></span>
                            <?php $title = esc_html(_draft_or_post_title($post->ID)); ?>

                            <span class="item-headline post-title" title="<?php echo esc_attr($title); ?>">
                                <?php if ($show_posts_publish_time): ?>
                                    <time
                                        class="item-headline-time"
                                        datetime="<?php echo esc_attr($post_publish_datetime); ?>"
                                        title="<?php echo esc_attr(date_i18n(
                                            $this->default_date_time_format,
                                            $post_publish_date_timestamp
                                        )); ?>"
                                    >
                                    <?php echo esc_html(date_i18n($posts_publish_time_format, $post_publish_date_timestamp)); ?>
                                </time>
                                <?php endif; ?>
                                <strong><?php echo $title; ?></strong>
                            </span>
                        </div>
                    </div>
                    <div class="item-inner">
                        <?php $this->get_inner_information($this->get_post_information_fields($post), $post); ?>
                    </div>
                    <?php if (!$this->current_user_can_modify_post($post)) : ?>
                        <div class="item-inner read-only-notice"
                             title="<?php esc_attr_e('You can\'t edit or move this post'); ?>">
                            <?php esc_html_e('Read only'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </li>
            <?php

            $post_li_html = ob_get_contents();
            ob_end_clean();

            $post_li_cache = [
                'num'          => $num,
                'post_li_html' => $post_li_html,
                'hidden'       => $this->hidden,
            ];
            wp_cache_set($cache_key, $post_li_cache, self::$post_li_html_cache_key);

            return $post_li_html;
        }

        /**
         * Returns the CSS class name and color for the given custom status.
         * It reutrns an array with the following keys:
         *     - icon
         *     - color
         *
         * @param string $post_status
         *
         * @return array
         */
        protected function get_post_status_options($post_status)
        {
            global $publishpress;

            // Check if we have a custom icon for this post_status
            $term = $publishpress->custom_status->get_custom_status_by('slug', $post_status);

            // Icon
            $icon = null;
            if (!empty($term->icon)) {
                $icon = $term->icon;
            } else {
                // Add an icon for the items
                $default_icons = [
                    'publish'    => 'dashicons-yes',
                    'future'     => 'dashicons-calendar-alt',
                    'private'    => 'dashicons-lock',
                    'draft'      => 'dashicons-edit',
                    'pending'    => 'dashicons-edit',
                    'auto-draft' => 'dashicons-edit',
                ];

                $icon = isset($default_icons[$post_status]) ? $default_icons[$post_status] : 'dashicons-edit';
            }

            // Color
            $color = PP_Custom_Status::DEFAULT_COLOR;
            if (!empty($term->color)) {
                $color = $term->color;
            }

            return [
                'color' => $color,
                'icon'  => $icon,
            ];
        }

        /**
         * get_inner_information description
         * Functionality for generating the inner html elements on the calendar
         * has been separated out so various ajax functions can reload certain
         * parts of an inner html element.
         *
         * @param array $pp_calendar_item_information_fields
         * @param WP_Post $post
         * @param array $published_statuses
         *
         * @since 0.8
         */
        public function get_inner_information($pp_calendar_item_information_fields, $post)
        {
            ?>
            <table class="item-information">
                <?php foreach ($pp_calendar_item_information_fields as $field => $values) : ?>
                    <tr class="item-field item-information-<?php echo esc_attr($field); ?>">
                        <th class="label"><?php echo esc_html($values['label']); ?>:</th>
                        <?php if ($values['value'] && isset($values['type'])) : ?>
                            <?php if (isset($values['editable']) && $this->current_user_can_modify_post($post)) : ?>
                                <td class="value<?php if ($values['editable']) {
                                    ?> editable-value<?php
                                } ?>">
                                    <?php echo esc_html($values['value']); ?>

                                </td>
                                <?php if ($values['editable']) : ?>
                                    <td class="editable-html hidden"
                                        data-type="<?php echo esc_attr($values['type']); ?>"
                                        data-metadataterm="<?php echo esc_attr(
                                            str_replace(
                                                'editorial-metadata-',
                                                '',
                                                str_replace('tax_', '', $field)
                                            )
                                        ); ?>">

                                        <?php echo $this->get_editable_html($values['type'], $values['value']); ?>
                                    </td>
                                <?php endif; ?>
                            <?php else : ?>
                                <td class="value"><?php echo esc_html($values['value']); ?></td>
                            <?php endif; ?>
                        <?php elseif ($values['value']) : ?>
                            <td class="value"><?php echo esc_html($values['value']); ?></td>
                        <?php else : ?>
                            <td class="value">
                                <em class="none"><?php echo esc_html_e('None', 'publishpress'); ?></em>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php do_action('pp_calendar_item_additional_html', $post->ID); ?>
            </table>
            <?php
            $post_type_object = get_post_type_object($post->post_type);
            $item_actions     = [];
            if ($this->current_user_can_modify_post($post)) {
                // Edit this post
                $item_actions['edit'] = '<a href="' . esc_url(
                        get_edit_post_link(
                            $post->ID,
                            true
                        )
                    ) . '" title="' . esc_attr(__('Edit this item', 'publishpress')) . '">' . __(
                        'Edit',
                        'publishpress'
                    ) . '</a>';
                // Trash this post
                $item_actions['trash'] = '<a href="' . esc_url(
                        get_delete_post_link($post->ID)
                    ) . '" title="' . esc_attr(
                        __('Trash this item'),
                        'publishpress'
                    ) . '">' . __('Trash', 'publishpress') . '</a>';
                // Preview/view this post
                if (!in_array($post->post_status, $this->published_statuses)) {
                    $item_actions['view'] = '<a href="' . esc_url(
                            apply_filters(
                                'preview_post_link',
                                add_query_arg('preview', 'true', get_permalink($post->ID)),
                                $post
                            )
                        ) . '" title="' . esc_attr(
                            sprintf(
                                __('Preview &#8220;%s&#8221;', 'publishpress'),
                                $post->post_title
                            )
                        ) . '" rel="permalink">' . __('Preview', 'publishpress') . '</a>';
                } elseif ('trash' != $post->post_status) {
                    $item_actions['view'] = '<a href="' . esc_url(get_permalink($post->ID)) . '" title="' . esc_attr(
                            sprintf(
                                __(
                                    'View &#8220;%s&#8221;',
                                    'publishpress'
                                ),
                                $post->post_title
                            )
                        ) . '" rel="permalink">' . __(
                            'View',
                            'publishpress'
                        ) . '</a>';
                }
                // Save metadata
                $item_actions['save hidden'] = '<a href="#savemetadata" id="save-editorial-metadata" class="post-' . esc_attr($post->ID) . '" title="' . esc_attr(
                        sprintf(
                            __(
                                'Save &#8220;%s&#8221;',
                                'publishpress'
                            ),
                            $post->post_title
                        )
                    ) . '" >' . __('Save', 'publishpress') . '</a>';
            }

            // Allow other plugins to add actions
            $item_actions = apply_filters('pp_calendar_item_actions', $item_actions, $post->ID);

            if (count($item_actions)) {
                echo '<div class="item-actions">';
                $html = '';
                foreach ($item_actions as $class => $item_action) {
                    $html .= '<span class="' . esc_attr($class) . '">' . $item_action . ' | </span> ';
                }
                echo rtrim($html, '| ');
                echo '</div>';
            } ?>
            <div style="clear:right;"></div>
            <?php
        }

        public function get_editable_html($type, $value)
        {
            switch ($type) {
                case 'text':
                case 'location':
                case 'number':
                    return '<input type="text" class="metadata-edit-' . esc_attr($type) . '" value="' . esc_html($value) . '"/>';
                    break;
                case 'paragraph':
                    return '<textarea type="text" class="metadata-edit-' . esc_attr(
                            $type
                        ) . '">' . $value . '</textarea>';
                    break;
                case 'date':
                    return '<input type="text" value="' . esc_attr(
                            $value
                        ) . '" class="date-time-pick metadata-edit-' . esc_attr($type) . '"/>';
                    break;
                case 'checkbox':
                    $output = '<select class="metadata-edit">';

                    if ($value == 'No') {
                        $output .= '<option value="0">No</option><option value="1">Yes</option>';
                    } else {
                        $output .= '<option value="1">Yes</option><option value="0">No</option>';
                    }

                    $output .= '</select>';

                    return $output;
                    break;
                case 'user':
                    return wp_dropdown_users(
                        [
                            'echo' => false,
                        ]
                    );
                    break;
                case 'taxonomy':
                    return '<input type="text" class="metadata-edit-' . esc_attr($type) . '" value="' . esc_attr(
                            $value
                        ) . '" />';
                    break;
                case 'taxonomy hierarchical':
                    return wp_dropdown_categories(
                        [
                            'echo'       => 0,
                            'hide_empty' => 0,
                        ]
                    );
                    break;
            }
        }

        /**
         * Get the information fields to be presented with each post popup
         *
         * @param obj $post Post to gather information fields for
         *
         * @return array $information_fields All of the information fields to be presented
         * @since 0.8
         *
         */
        public function get_post_information_fields($post)
        {
            $information_fields = [];
            // Post author
            $authorsNames = apply_filters(
                'publishpress_post_authors_names',
                [get_the_author_meta('display_name', $post->post_author)],
                $post->ID
            );

            $information_fields['author'] = [
                'label' => _n('Author', 'Authors', count($authorsNames), 'publishpress'),
                'value' => implode(', ', $authorsNames),
                'type'  => 'author',
            ];

            // If the calendar supports more than one post type, show the post type label
            if (count($this->get_post_types_for_module($this->module)) > 1) {
                $information_fields['post_type'] = [
                    'label' => __('Post Type', 'publishpress'),
                    'value' => get_post_type_object($post->post_type)->labels->singular_name,
                ];
            }
            // Publication time for published statuses
            if (in_array($post->post_status, $this->published_statuses)) {
                if ($post->post_status == 'future') {
                    $information_fields['post_date'] = [
                        'label' => __('Scheduled', 'publishpress'),
                        'value' => get_the_time(null, $post->ID),
                    ];
                } else {
                    $information_fields['post_date'] = [
                        'label' => __('Published', 'publishpress'),
                        'value' => get_the_time(null, $post->ID),
                    ];
                }
            }
            // Taxonomies and their values
            $args       = [
                'post_type' => $post->post_type,
            ];
            $taxonomies = get_object_taxonomies($args, 'object');
            foreach ((array)$taxonomies as $taxonomy) {
                // Sometimes taxonomies skip by, so let's make sure it has a label too
                if (!$taxonomy->public || !$taxonomy->label) {
                    continue;
                }

                $terms = get_the_terms($post->ID, $taxonomy->name);
                if (!$terms || is_wp_error($terms)) {
                    continue;
                }

                $key = 'tax_' . $taxonomy->name;
                if (count($terms)) {
                    $value = '';
                    foreach ((array)$terms as $term) {
                        $value .= $term->name . ', ';
                    }
                    $value = rtrim($value, ', ');
                } else {
                    $value = '';
                }
                // Used when editing editorial metadata and post meta
                if (is_taxonomy_hierarchical($taxonomy->name)) {
                    $type = 'taxonomy hierarchical';
                } else {
                    $type = 'taxonomy';
                }

                $information_fields[$key] = [
                    'label' => $taxonomy->label,
                    'value' => $value,
                    'type'  => $type,
                ];

                if ($post->post_type == 'page') {
                    $ed_cap = 'edit_page';
                } else {
                    $ed_cap = 'edit_post';
                }

                if (current_user_can($ed_cap, $post->ID)) {
                    $information_fields[$key]['editable'] = true;
                }
            }

            $information_fields = apply_filters('pp_calendar_item_information_fields', $information_fields, $post->ID);

            foreach ($information_fields as $field => $values) {
                // Allow filters to hide empty fields or to hide any given individual field. Hide empty fields by default.
                if ((apply_filters(
                            'pp_calendar_hide_empty_item_information_fields',
                            true,
                            $post->ID
                        ) && empty($values['value']))
                    || apply_filters("pp_calendar_hide_{$field}_item_information_field", false, $post->ID)) {
                    unset($information_fields[$field]);
                }
            }

            return $information_fields;
        }

        /**
         * Generates the filtering and navigation options for the top of the calendar
         *
         * @param array $filters Any set filters
         * @param array $dates All of the days of the week. Used for generating navigation links
         */
        public function print_top_navigation($filters, $dates)
        {
            ?>
            <ul class="pp-calendar-navigation">
                <li id="calendar-filter">
                    <form method="GET" id="pp-calendar-filters">
                        <input type="hidden" name="page" value="pp-calendar"/>
                        <input type="hidden" name="start_date" value="<?php echo esc_attr($filters['start_date']); ?>"/>
                        <!-- Filter by status -->
                        <?php
                        foreach ($this->calendar_filters() as $select_id => $select_name) {
                            echo $this->calendar_filter_options($select_id, $select_name, $filters);
                        } ?>
                    </form>
                </li>
                <!-- Clear filters functionality (all of the fields, but empty) -->
                <li>
                    <form method="GET">
                        <input type="hidden" name="page" value="pp-calendar"/>
                        <input type="hidden" name="start_date" value="<?php echo esc_attr($filters['start_date']); ?>"/>
                        <?php
                        foreach ($this->calendar_filters() as $select_id => $select_name) {
                            echo '<input type="hidden" name="' . esc_attr($select_name) . '" value="" />';
                        } ?>
                        <input type="submit" id="post-query-clear" class="button-secondary button"
                               value="<?php esc_html_e('Reset', 'publishpress'); ?>"/>
                    </form>
                </li>

                <?php /** Previous and next navigation items (translatable so they can be increased if needed)**/ ?>
                <li class="date-change next-week">
                    <a title="<?php esc_attr(sprintf(__('Forward 1 week', 'publishpress'))); ?>"
                       href="<?php echo esc_url(
                           $this->get_pagination_link(
                               'next',
                               $filters,
                               1
                           )
                       ); ?>"><?php esc_html_e('&rsaquo;', 'publishpress'); ?></a>
                    <?php if ($this->total_weeks > 1) : ?>
                        <a title="<?php esc_attr(
                            printf(
                                __('Forward %d weeks', 'publishpress'),
                                $this->total_weeks
                            )
                        ); ?>"
                           href="<?php echo esc_url(
                               $this->get_pagination_link(
                                   'next',
                                   $filters
                               )
                           ); ?>"><?php esc_html_e('&raquo;', 'publishpress'); ?></a>
                    <?php endif; ?>
                </li>
                <li class="date-change today">
                    <a title="<?php esc_attr(
                        printf(
                            __('Today is %s', 'publishpress'),
                            date(get_option('date_format'), current_time('timestamp'))
                        )
                    ); ?>"
                       href="<?php echo esc_url($this->get_pagination_link('next', $filters, 0)); ?>"><?php esc_html_e(
                            'Today',
                            'publishpress'
                        ); ?></a>
                </li>
                <li class="date-change previous-week">
                    <?php if ($this->total_weeks > 1) : ?>
                        <a title="<?php esc_attr(sprintf(__('Back %d weeks', 'publishpress'), $this->total_weeks)); ?>"
                           href="<?php echo esc_url(
                               $this->get_pagination_link(
                                   'previous',
                                   $filters
                               )
                           ); ?>"><?php esc_html_e('&laquo;', 'publishpress'); ?></a>
                    <?php endif; ?>
                    <a title="<?php esc_attr(sprintf(__('Back 1 week', 'publishpress'))); ?>"
                       href="<?php echo esc_url(
                           $this->get_pagination_link(
                               'previous',
                               $filters,
                               1
                           )
                       ); ?>"><?php esc_html_e('&lsaquo;', 'publishpress'); ?></a>
                </li>
                <li class="ajax-actions">
                    <img class="waiting" style="display:none;"
                         src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" alt=""/>
                </li>
            </ul>
            <?php
        }

        /**
         * Generate the calendar header for a given range of dates
         *
         * @param array $dates Date range for the header
         *
         * @return string $html Generated HTML for the header
         */
        public function get_time_period_header($dates)
        {
            $html = '';
            foreach ($dates as $date) {
                $html .= '<th class="column-heading" >';
                $html .= esc_html(date_i18n('l', strtotime($date)));
                $html .= '</th>';
            }

            return $html;
        }

        /**
         * Query to get all of the calendar posts for a given day
         *
         * @param array $args Any filter arguments we want to pass
         * @param string $request_context Where the query is coming from, to distinguish dashboard and subscriptions
         *
         * @return array $posts All of the posts as an array sorted by date
         */
        public function get_calendar_posts_for_week($args = [], $context = 'dashboard')
        {
            global $wpdb;

            $supported_post_types = $this->get_post_types_for_module($this->module);
            $defaults             = [
                'post_status'    => null,
                'cat'            => null,
                'tag'            => null,
                'author'         => null,
                'post_type'      => $supported_post_types,
                'posts_per_page' => -1,
                'order'          => 'ASC',
            ];

            $args = array_merge($defaults, $args);

            // Unpublished as a status is just an array of everything but 'publish'
            if ($args['post_status'] == 'unpublish') {
                $args['post_status'] = '';
                $post_statuses       = $this->get_post_statuses();
                foreach ($post_statuses as $post_status) {
                    $args['post_status'] .= $post_status->slug . ', ';
                }
                $args['post_status'] = rtrim($args['post_status'], ', ');
                // Optional filter to include scheduled content as unpublished
                if (apply_filters('pp_show_scheduled_as_unpublished', false)) {
                    $args['post_status'] .= ', future';
                }
            }
            // The WP functions for printing the category and author assign a value of 0 to the default
            // options, but passing this to the query is bad (trashed and auto-draft posts appear!), so
            // unset those arguments.
            if ($args['cat'] === '0') {
                unset($args['cat']);
            }
            if ($args['tag'] === '0') {
                unset($args['tag']);
            } else {
                $args['tag_id'] = $args['tag'];
                unset($args['tag']);
            }

            if ($args['author'] === '0') {
                unset($args['author']);
            }

            if (empty($args['post_type']) || !in_array($args['post_type'], $supported_post_types)) {
                $args['post_type'] = $supported_post_types;
            }

            // Filter for an end user to implement any of their own query args
            $args = apply_filters('pp_calendar_posts_query_args', $args, $context);

            add_filter('posts_where', [$this, 'posts_where_week_range']);
            $post_results = new WP_Query($args);
            remove_filter('posts_where', [$this, 'posts_where_week_range']);

            $posts = [];
            while ($post_results->have_posts()) {
                $post_results->the_post();
                global $post;
                $key_date           = date('Y-m-d', strtotime($post->post_date));
                $posts[$key_date][] = $post;
            }

            return $posts;
        }

        /**
         * Filter the WP_Query so we can get a week range of posts
         *
         * @param string $where The original WHERE SQL query string
         *
         * @return string $where Our modified WHERE query string
         */
        public function posts_where_week_range($where = '')
        {
            global $wpdb;

            $beginning_date = $this->get_beginning_of_week($this->start_date, 'Y-m-d', $this->current_week);
            $ending_date    = $this->get_ending_of_week($this->start_date, 'Y-m-d', $this->current_week);
            // Adjust the ending date to account for the entire day of the last day of the week
            $ending_date = date('Y-m-d', strtotime('+1 day', strtotime($ending_date)));
            $where       = $where . $wpdb->prepare(
                    " AND ($wpdb->posts.post_date >= %s AND $wpdb->posts.post_date < %s)",
                    $beginning_date,
                    $ending_date
                );

            return $where;
        }

        /**
         * Gets the link for the next time period
         *
         * @param string $direction 'previous' or 'next', direction to go in time
         * @param array $filters Any filters that need to be applied
         * @param int $weeks_offset Number of weeks we're offsetting the range
         *
         * @return string $url The URL for the next page
         */
        public function get_pagination_link($direction = 'next', $filters = [], $weeks_offset = null)
        {
            $supported_post_types = $this->get_post_types_for_module($this->module);

            if (!isset($weeks_offset)) {
                $weeks_offset = $this->total_weeks;
            } elseif ($weeks_offset == 0) {
                $filters['start_date'] = $this->get_beginning_of_week(date('Y-m-d', current_time('timestamp')));
            }

            if ($direction === 'previous') {
                $weeks_offset = '-' . $weeks_offset;
            }

            $filters['start_date'] = date(
                'Y-m-d',
                strtotime($weeks_offset . ' weeks', strtotime($filters['start_date']))
            );
            $url                   = add_query_arg($filters, menu_page_url('pp-calendar', false));

            if (count($supported_post_types) > 1) {
                $url = add_query_arg('cpt', $filters['cpt'], $url);
            }

            return $url;
        }

        /**
         * Given a day in string format, returns the day at the beginning of that week, which can be the given date.
         * The end of the week is determined by the blog option, 'start_of_week'.
         *
         * @see http://www.php.net/manual/en/datetime.formats.date.php for valid date formats
         *
         * @param string $date String representing a date
         * @param string $format Date format in which the end of the week should be returned
         * @param int $week Number of weeks we're offsetting the range
         *
         * @return string $formatted_start_of_week End of the week
         */
        public function get_beginning_of_week($date, $format = 'Y-m-d', $week = 1)
        {
            $date                    = strtotime($date);
            $start_of_week           = get_option('start_of_week');
            $day_of_week             = date('w', $date);
            $date                    += (($start_of_week - $day_of_week - 7) % 7) * 60 * 60 * 24 * $week;
            $additional              = 3600 * 24 * 7 * ($week - 1);
            $formatted_start_of_week = date($format, $date + $additional);

            return $formatted_start_of_week;
        }

        /**
         * Given a day in string format, returns the day at the end of that week, which can be the given date.
         * The end of the week is determined by the blog option, 'start_of_week'.
         *
         * @see http://www.php.net/manual/en/datetime.formats.date.php for valid date formats
         *
         * @param string $date String representing a date
         * @param string $format Date format in which the end of the week should be returned
         * @param int $week Number of weeks we're offsetting the range
         *
         * @return string $formatted_end_of_week End of the week
         */
        public function get_ending_of_week($date, $format = 'Y-m-d', $week = 1)
        {
            $date                  = strtotime($date);
            $end_of_week           = get_option('start_of_week') - 1;
            $day_of_week           = date('w', $date);
            $date                  += (($end_of_week - $day_of_week + 7) % 7) * 60 * 60 * 24;
            $additional            = 3600 * 24 * 7 * ($week - 1);
            $formatted_end_of_week = date($format, $date + $additional);

            return $formatted_end_of_week;
        }

        /**
         * Human-readable time range for the calendar
         * Shows something like "for October 30th through November 26th" for a four-week period
         *
         * @since 0.7
         */
        public function calendar_time_range()
        {
            $first_datetime = strtotime($this->start_date);
            $first_date     = date_i18n(get_option('date_format'), $first_datetime);
            $total_days     = ($this->total_weeks * 7) - 1;
            $last_datetime  = strtotime('+' . $total_days . ' days', date('U', strtotime($this->start_date)));
            $last_date      = date_i18n(get_option('date_format'), $last_datetime);

            return sprintf(__('for %1$s through %2$s', 'publishpress'), $first_date, $last_date);
        }

        /**
         * Check whether the current user should have the ability to modify the post
         *
         * @param object $post The post object we're checking
         *
         * @return bool $can Whether or not the current user can modify the post
         * @since 0.7
         *
         */
        public function current_user_can_modify_post($post)
        {
            if (!$post) {
                return false;
            }

            $post_type_object = get_post_type_object($post->post_type);

            // Is the current user an author of the post?
            $userId                  = (int)wp_get_current_user()->ID;
            $isAuthor                = apply_filters(
                'publishpress_is_author_of_post',
                $userId === (int)$post->post_author,
                $userId,
                $post->ID
            );
            $isPublished             = in_array($post->post_status, $this->published_statuses);
            $canPublish              = current_user_can($post_type_object->cap->publish_posts, $post->ID);
            $passedPublishedPostRule = (!$isPublished || ($isPublished && $canPublish));

            // Published posts only can be updated by those who can publish posts.
            // Is the user an author for the content?
            if ($isAuthor && $passedPublishedPostRule) {
                return true;
            }

            // If the user can edit others_posts he can edits the posts depending on the status.
            if (current_user_can($post_type_object->cap->edit_others_posts, $post->ID) && $passedPublishedPostRule) {
                return true;
            }

            return false;
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * We use the Settings API for form generation, but not saving because we have our
         * own way of handling the data.
         *
         * @since 0.7
         */
        public function register_settings()
        {
            add_settings_section(
                $this->module->options_group_name . '_general',
                false,
                '__return_false',
                $this->module->options_group_name
            );

            add_settings_field(
                'post_types',
                __('Post types to show', 'publishpress'),
                [$this, 'settings_post_types_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'ics_subscription',
                __('Subscription in iCal or Google Calendar', 'publishpress'),
                [$this, 'settings_ics_subscription_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'show_posts_publish_time',
                __('Statuses to display publish time', 'publishpress'),
                [$this, 'settings_show_posts_publish_time_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'posts_publish_time_format',
                __('Posts publish time format', 'publishpress'),
                [$this, 'settings_posts_publish_time_format_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'default_publish_time',
                __('Default publish time for items created in the calendar', 'publishpress'),
                [$this, 'settings_default_publish_time_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'sort_by',
                __('Field used for sorting the calendar items in a day cell', 'publishpress'),
                [$this, 'settings_sort_by_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'max_visible_posts_per_date',
                __('Max visible posts per date', 'publishpress'),
                [$this, 'settings_max_visible_posts_per_date'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );
        }

        /**
         * Choose the post types that should be displayed on the calendar
         *
         * @since 0.7
         */
        public function settings_post_types_option()
        {
            global $publishpress;
            $publishpress->settings->helper_option_custom_post_type($this->module);

            // Check if we need to display the message about selecting at lest one post type
            if (get_transient(static::TRANSIENT_SHOW_ONE_POST_TYPE_WARNING)) {
                echo '<p class="psppca_field_warning">' . __(
                        'At least one post type must be selected',
                        'publishpress'
                    ) . '</p>';

                delete_transient(static::TRANSIENT_SHOW_ONE_POST_TYPE_WARNING);
            }
        }

        /**
         * Give a bit of helper text to indicate the user can change
         * number of weeks in the screen options
         *
         * @since 0.7
         */
        public function settings_number_weeks_option()
        {
            echo '<span class="description">' . __(
                    'The number of weeks shown on the calendar can be changed on a user-by-user basis using the calendar\'s screen options.',
                    'publishpress'
                ) . '</span>';
        }

        /**
         * Enable calendar subscriptions via .ics in iCal or Google Calendar
         *
         * @since 0.8
         */
        public function settings_ics_subscription_option()
        {
            $options = [
                'off' => __('Disabled', 'publishpress'),
                'on'  => __('Enabled', 'publishpress'),
            ];
            echo '<select id="ics_subscription" name="' . esc_attr(
                    $this->module->options_group_name
                ) . '[ics_subscription]">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"';
                echo selected($this->module->options->ics_subscription, $value);
                echo '>' . esc_html($label) . '</option>';
            }
            echo '</select>';

            $regenerate_url = add_query_arg(
                'action',
                'pp_calendar_regenerate_calendar_feed_secret',
                admin_url('admin.php?page=pp-calendar')
            );
            $regenerate_url = wp_nonce_url($regenerate_url, 'pp-regenerate-ics-key');
            echo '&nbsp;&nbsp;&nbsp;<a href="' . esc_url($regenerate_url) . '">' . __(
                    'Regenerate calendar feed secret',
                    'publishpress'
                ) . '</a>';

            // If our secret key doesn't exist, create a new one
            if (empty($this->module->options->ics_secret_key)) {
                PublishPress()->update_module_option($this->module->name, 'ics_secret_key', wp_generate_password());
            }
        }

        /**
         * Option that define either Posts publish times are displayed or not.
         *
         * @since 1.20.0
         */
        public function settings_show_posts_publish_time_option()
        {
            $field_name = esc_attr($this->module->options_group_name) . '[show_posts_publish_time]';

            $customStatuses = PP_Custom_Status::getCustomStatuses();

            if (empty($customStatuses)) {
                $statuses = [
                    'publish' => __('Publish'),
                    'future'  => __('Scheduled'),
                ];
            } else {
                $statuses = [];

                foreach ($customStatuses as $status) {
                    $statuses[$status->slug] = $status->name;
                }
            }

            // Add support to the legacy value for this setting, where "on" means post and page selected.
            if ($this->module->options->show_posts_publish_time === 'on') {
                $this->module->options->show_posts_publish_time = [
                    'publish' => 'on',
                    'future'  => 'on',
                ];
            }

            foreach ($statuses as $status => $title) {
                $id = esc_attr($status) . '-display-publish-time';

                echo '<label for="' . $id . '">';
                echo '<input id="' . $id . '" name="'
                    . $field_name . '[' . esc_attr($status) . ']"';

                if (isset($this->module->options->show_posts_publish_time[$status])) {
                    checked($this->module->options->show_posts_publish_time[$status], 'on');
                }

                // Defining post_type_supports in the functions.php file or similar should disable the checkbox
                disabled(post_type_supports($status, $this->module->post_type_support), true);
                echo ' type="checkbox" value="on" />&nbsp;&nbsp;&nbsp;' . esc_html($title) . '</label>';
                echo '<br />';
            }
        }

        /**
         * Define the time format for Posts publish date.
         *
         * @since 1.20.0
         */
        public function settings_posts_publish_time_format_option()
        {
            $timeFormats = [
                self::TIME_FORMAT_12H_WO_LEADING_ZEROES   => '1-12 am/pm',
                self::TIME_FORMAT_12H_WITH_LEADING_ZEROES => '01-12 am/pm',
                self::TIME_FORMAT_24H                     => '00-24',
            ];

            $posts_publish_time_format = !isset($this->module->options->posts_publish_time_format) || is_null(
                $this->module->options->posts_publish_time_format
            )
                ? self::TIME_FORMAT_12H_WO_LEADING_ZEROES
                : $this->module->options->posts_publish_time_format;

            echo '<div class="c-input-group c-pp-calendar-options-posts_publish_time_format">';

            foreach ($timeFormats as $timeFormat => $timeMockValue) {
                printf(
                    '
                    <div style="max-width: 175px; display: flex; flex-direction: row; justify-content: space-between; margin-bottom: 5px;">
                        <label>
                            <input
                                class="o-radio"
                                type="radio"
                                name="%s"
                                value="%s"
                                %s
                            />
                            <span>%s</span>
                        </label>
                        <code>%2$s</code>
                    </div>',
                    esc_attr($this->module->options_group_name) . '[posts_publish_time_format]',
                    $timeFormat,
                    $posts_publish_time_format === $timeFormat ? 'checked' : '',
                    $timeMockValue
                );
            }

            echo '</div>';
        }

        /**
         * @since 2.0.7
         */
        public function settings_default_publish_time_option()
        {
            echo '<div class="c-input-group">';

            echo sprintf(
                '<input type="time" name="%s" value="%s">',
                esc_attr($this->module->options_group_name) . '[default_publish_time]',
                $this->module->options->default_publish_time
            );

            echo '</div>';
        }

        public function settings_sort_by_option()
        {
            $fields = [
                'time'   => __('Publishing Time', 'publishpress'),
                'status' => __('Post Status', 'publishpress'),
            ];

            $sortByOptionValue = !isset($this->module->options->sort_by) || is_null(
                $this->module->options->sort_by
            )
                ? 'time'
                : $this->module->options->sort_by;

            echo '<div class="c-input-group c-pp-calendar-options-sort_by">';

            foreach ($fields as $key => $label) {
                printf(
                    '
                    <div style="max-width: 175px; display: flex; flex-direction: row; justify-content: space-between; margin-bottom: 5px;">
                        <label>
                            <input
                                class="o-radio"
                                type="radio"
                                name="%s"
                                value="%s"
                                %s
                            />
                            <span>%s</span>
                        </label>
                    </div>',
                    esc_attr($this->module->options_group_name) . '[sort_by]',
                    $key,
                    $key === $sortByOptionValue ? 'checked' : '',
                    $label
                );
            }

            echo '</div>';
        }

        public function settings_max_visible_posts_per_date()
        {
            $maxVisiblePostsPerDate = !isset($this->module->options->max_visible_posts_per_date) || is_null(
                $this->module->options->max_visible_posts_per_date
            )
                ? (int)$this->default_max_visible_posts_per_date
                : (int)$this->module->options->max_visible_posts_per_date;

            echo '<div class="c-input-group c-pp-calendar-options-max_visible_posts_per_date">';

            echo sprintf(
                    '<select name="%s" id="%d">',
                    esc_attr($this->module->options_group_name) . '[max_visible_posts_per_date]',
                    'max_visible_posts_per_date'
            );

            echo sprintf(
                    '<option value="-1" %s>%s</option>',
                    selected($maxVisiblePostsPerDate, -1, false),
                    __('All posts', 'publishpress')
            );

            for ($i = 4; $i <= 30; $i++) {
                echo sprintf(
                    '<option value="%2$d" %s>%2$d</option>',
                    selected($maxVisiblePostsPerDate, $i, false),
                    $i
                );
            }

            echo '</select></div>';
        }

        /**
         * Validate the data submitted by the user in calendar settings
         *
         * @since 0.7
         */
        public function settings_validate($new_options)
        {
            $options = (array)$this->module->options;

            if (isset($new_options['post_types'])) {
                // Set post as default
                $empty = true;
                foreach ($options['post_types'] as $value) {
                    if ('on' === $value) {
                        $empty = false;
                        break;
                    }
                }

                if ($empty) {
                    // Check post by default
                    $options['post_types'] = ['post' => 'on'];

                    // Add flag to display a warning to the user
                    set_transient(static::TRANSIENT_SHOW_ONE_POST_TYPE_WARNING, 1, 300);
                } else {
                    $options['post_types'] = $this->clean_post_type_options(
                        $new_options['post_types'],
                        $this->module->post_type_support
                    );
                }
            } else {
                // Check post by default
                $options['post_types'] = ['post' => 'on'];

                // Add flag to display a warning to the user
                set_transient(static::TRANSIENT_SHOW_ONE_POST_TYPE_WARNING, 1, 300);
            }

            if ('on' != $new_options['ics_subscription']) {
                $options['ics_subscription'] = 'off';
            } else {
                $options['ics_subscription'] = 'on';
            }

            if (isset($new_options['show_posts_publish_time'])) {
                if ($new_options['show_posts_publish_time'] == 'on') {
                    $options['show_posts_publish_time'] = [
                        'publish' => 'on',
                        'future'  => 'on',
                    ];
                } else {
                    $options['show_posts_publish_time'] = $new_options['show_posts_publish_time'];
                }
            } else {
                $options['show_posts_publish_time'] = [];
            }

            $options['posts_publish_time_format'] = isset($new_options['posts_publish_time_format'])
                ? $new_options['posts_publish_time_format']
                : self::TIME_FORMAT_12H_WO_LEADING_ZEROES;

            // Default publish time
            if (isset($new_options['default_publish_time'])) {
                $options['default_publish_time'] = sanitize_text_field($new_options['default_publish_time']);
            }

            // Sort by
            $options['sort_by'] = isset($new_options['sort_by'])
                ? sanitize_text_field($new_options['sort_by'])
                : 'time';

            // Max visible posts per date
            $options['max_visible_posts_per_date'] = isset($new_options['max_visible_posts_per_date'])
                ? (int)$new_options['max_visible_posts_per_date']
                : $this->default_max_visible_posts_per_date;

            return $options;
        }

        /**
         * Settings page for calendar
         */
        public function print_configure_view()
        {
            global $publishpress; ?>
            <form class="basic-settings"
                  action="<?php echo esc_url(menu_page_url($this->module->settings_slug, false)); ?>" method="post">
                <?php settings_fields($this->module->options_group_name); ?>
                <?php do_settings_sections($this->module->options_group_name); ?>
                <?php
                echo '<input id="publishpress_module_name" name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
                        $this->module->name
                    ) . '" />'; ?>
                <p class="submit"><?php submit_button(null, 'primary', 'submit', false); ?></p>
                <?php echo '<input name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
                        $this->module->name
                    ) . '" />'; ?>
                <?php wp_nonce_field('edit-publishpress-settings'); ?>
            </form>
            <?php
        }

        /**
         * Ajax callback to insert a post placeholder for a particular date
         *
         * @since 0.8
         */
        public function handle_ajax_insert_post()
        {
            // Nonce check!
            if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'pp-calendar-modify')) {
                $this->print_ajax_response('error', $this->module->messages['nonce-failed']);
            }

            // Check that the user has the right capabilities to add posts to the calendar (defaults to 'edit_posts')
            if (!current_user_can($this->create_post_cap)) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            if (empty($_POST['pp_insert_date'])) {
                $this->print_ajax_response('error', __('No date supplied.', 'publishpress'));
            }

            // Post type has to be visible on the calendar to create a placeholder
            $post_type = sanitize_text_field($_POST['pp_insert_type']);
            if (empty($post_type)) {
                $post_type = 'post';
            }

            if (!in_array($post_type, $this->get_post_types_for_module($this->module))) {
                $this->print_ajax_response(
                    'error',
                    __('Please change Quick Create to  use a post type viewable on the calendar.', 'publishpress')
                );
            }

            // Sanitize post values
            $post_title = apply_filters(
                'pp_calendar_after_form_submission_sanitize_title',
                sanitize_text_field($_POST['pp_insert_title'])
            );
            if (empty($post_title)) {
                $post_title = __('Untitled', 'publishpress');
            }

            $post_content = apply_filters(
                'pp_calendar_after_form_submission_sanitize_content',
                wp_filter_post_kses($_POST['pp_insert_content'])
            );
            if (!$post_content) {
                $post_content = '';
            }

            $post_author = apply_filters(
                'pp_calendar_after_form_submission_sanitize_author',
                (int)$_POST['pp_insert_author']
            );
            try {
                $post_author = apply_filters('pp_calendar_after_form_submission_validate_author', $post_author);
            } catch (Exception $e) {
                $this->print_ajax_response('error', $e->getMessage());
            }

            if (empty($post_author)) {
                $post_author = apply_filters('publishpress_calendar_default_author', get_current_user_id());
            }

            $post_status = sanitize_text_field($_POST['pp_insert_status']);
            if (!$this->isPostStatusValid($post_status)) {
                $this->print_ajax_response('error', __('Invalid Status supplied.', 'publishpress'));
            }

            $post_date           = sanitize_text_field($_POST['pp_insert_date']);
            $post_date_timestamp = strtotime($post_date);

            $post_publish_time = trim(sanitize_text_field($_POST['pp_insert_publish_time']));

            if (empty($post_publish_time)) {
                $post_publish_time = $this->module->options->default_publish_time;
            }

            if (!empty($post_publish_time)) {
                $post_date = sprintf(
                    '%s %s',
                    $post_date,
                    ((function_exists('mb_strlen') ? mb_strlen($post_publish_time) : strlen($post_publish_time)) === 5)
                        ? "{$post_publish_time}:" . date('s', $post_date_timestamp)
                        : date('H:i:s', $post_date_timestamp)
                );
            }

            $post_publish_date_time_instance = new DateTime($post_date);
            if ($post_publish_date_time_instance === false) {
                $this->print_ajax_response('error', __('Invalid Publish Date supplied.', 'publishpress'));
            }
            unset($post_publish_date_time_instance);

            // Set new post parameters
            $post_placeholder = [
                'post_author'       => $post_author,
                'post_title'        => $post_title,
                'post_content'      => $post_content,
                'post_type'         => $post_type,
                'post_status'       => $post_status,
                'post_date'         => $post_date,
                'post_modified'     => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
            ];

            /*
             * By default, adding a post to the calendar will set the timestamp.
             * If the user don't desires that to be the behavior, they can set the result of this filter to 'false'
             * With how WordPress works internally, setting 'post_date_gmt' will set the timestamp.
             * But check the Custom Status module and the hook to "wp_insert_post_data". It will reset the date if not
             * publishing or scheduling.
             */

            if (apply_filters('pp_calendar_allow_ajax_to_set_timestamp', true)) {
                $post_placeholder['post_date_gmt'] = get_gmt_from_date($post_date);
            }

            // Create the post
            add_filter('wp_insert_post_data', [$this, 'alter_post_modification_time'], 99, 2);
            $post_id = wp_insert_post($post_placeholder);
            remove_filter('wp_insert_post_data', [$this, 'alter_post_modification_time'], 99, 2);

            do_action('publishpress_calendar_after_create_post', $post_id, $post_author);

            if ($post_id) {
                $post = get_post($post_id);

                // Generate the HTML for the post item so it can be injected
                $post_li_html = $this->generate_post_li_html($post, $post_date, 0);

                // announce success and send back the html to inject
                $this->print_ajax_response('success', $post_li_html);
            } else {
                $this->print_ajax_response('error', __('Post could not be created', 'publishpress'));
            }
        }

        private function isPostStatusValid($subject)
        {
            foreach ($this->get_post_statuses() as $post_status) {
                $is_status_valid = $subject === $post_status->slug;
                if ($is_status_valid) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Allow to alter modified date when creating posts. WordPress by default
         * doesn't allow that. We need it to fix an issue where the post_modified
         * field is saved with the post_date value. That is a ptoblem when you save
         * a post with post_date to the future. For scheduled posts.
         *
         * @param array $data
         * @param array $postarr
         *
         * @return array
         */
        public function alter_post_modification_time($data, $postarr)
        {
            if (!empty($postarr['post_modified']) && !empty($postarr['post_modified_gmt'])) {
                $data['post_modified']     = $postarr['post_modified'];
                $data['post_modified_gmt'] = $postarr['post_modified_gmt'];
            }

            return $data;
        }

        /**
         * Returns the singular label for the posts that are
         * quick-created on the calendar
         *
         * @param mix $post_type_slug
         *
         * @return str Singular label for a post-type
         */
        public function get_quick_create_post_type_name($post_type_slug)
        {
            if (is_array($post_type_slug)) {
                $post_type_slug = $post_type_slug[0];
            }

            $post_type_obj = get_post_type_object($post_type_slug);

            return $post_type_obj->labels->singular_name ? $post_type_obj->labels->singular_name : $post_type_slug;
        }

        /**
         * ajax_pp_calendar_update_metadata
         * Update the metadata from the calendar.
         *
         * @return string representing the overlay
         *
         * @since 0.8
         */
        public function handle_ajax_update_metadata()
        {
            global $wpdb;

            if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'pp-calendar-modify')) {
                $this->print_ajax_response('error', $this->module->messages['nonce-failed']);
            }

            // Check that we got a proper post
            $post_id = (int)$_POST['post_id'];
            $post    = get_post($post_id);

            if (!$post) {
                $this->print_ajax_response('error', $this->module->messages['missing-post']);
            }

            if ($post->post_type == 'page') {
                $edit_check = 'edit_page';
            } else {
                $edit_check = 'edit_post';
            }

            if (!current_user_can($edit_check, $post->ID)) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            // Check that the user can modify the post
            if (!$this->current_user_can_modify_post($post)) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            $default_types = [
                'author',
                'taxonomy',
            ];

            $metadata_types = [];

            if (!$this->module_enabled('editorial_metadata')) {
                $this->print_ajax_response('error', $this->module->messages['update-error']);
            }

            $metadata_types = array_keys(PublishPress()->editorial_metadata->get_supported_metadata_types());

            // Update an editorial metadata field
            if (isset($_POST['metadata_type']) && in_array($_POST['metadata_type'], $metadata_types)) {
                $post_meta_key = sanitize_text_field(
                    '_pp_editorial_meta_' . $_POST['metadata_type'] . '_' . $_POST['metadata_term']
                );

                // Javascript date parsing is terrible, so use strtotime in php
                if ($_POST['metadata_type'] == 'date') {
                    $metadata_value = strtotime(sanitize_text_field($_POST['metadata_value']));
                } else {
                    $metadata_value = sanitize_text_field($_POST['metadata_value']);
                }

                update_post_meta($post->ID, $post_meta_key, $metadata_value);
                $response = 'success';
            } else {
                switch ($_POST['metadata_type']) {
                    case 'taxonomy':
                    case 'taxonomy hierarchical':
                        $response = wp_set_post_terms($post->ID, sanitize_text_field($_POST['metadata_value']), sanitize_text_field($_POST['metadata_term']));
                        break;
                    default:
                        $response = new WP_Error('invalid-type', __('Invalid metadata type', 'publishpress'));
                        break;
                }
            }

            // Assuming we've got to this point, just regurgitate the value
            if (!is_wp_error($response)) {
                $this->print_ajax_response('success', sanitize_text_field($_POST['metadata_value']));
            } else {
                $this->print_ajax_response('error', esc_html__('Metadata could not be updated.', 'publishpress'));
            }
        }

        public function calendar_filters()
        {
            $select_filter_names = [];

            $select_filter_names['post_status'] = 'post_status';
            $select_filter_names['cat']         = 'cat';
            $select_filter_names['tag']         = 'tag';
            $select_filter_names['author']      = 'author';
            $select_filter_names['type']        = 'cpt';
            $select_filter_names['weeks']       = 'weeks';

            return apply_filters('pp_calendar_filter_names', $select_filter_names);
        }

        public function searchAuthors()
        {
            header('Content-type: application/json;');

            if (!wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'calendar_filter_nonce')) {
                return '[]';
            }

            $queryText = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

            /**
             * @param array $results
             * @param string $searchText
             */
            $results = apply_filters('publishpress_search_authors_results_pre_search', [], $queryText);

            if (!empty($results)) {
                echo wp_json_encode($results);
                exit;
            }

            $user_args = [
                'number'   => 20,
                'orderby' => 'display_name',
            ];

            if (!empty($queryText)) {
                $user_args['search'] = '*' . $queryText . '*';
            }

            $users = get_users($user_args);

            foreach ($users as $user) {
                $results[] = [
                    'id'   => $user->ID,
                    'text' => $user->display_name,
                ];
            }
            echo wp_json_encode($results);

            exit;
        }

        public function searchCategories()
        {
            header('Content-type: application/json;');

            if (!wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'calendar_filter_nonce')) {
                return '[]';
            }

            $queryText = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
            global $wpdb;

            $queryResult = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT t.term_id AS id, t.name AS text
                FROM {$wpdb->term_taxonomy} as tt
                INNER JOIN {$wpdb->terms} as t ON (tt.term_id = t.term_id)
                WHERE taxonomy = 'category' AND t.name LIKE %s
                ORDER BY 2
                LIMIT 20",
                    '%' . $wpdb->esc_like($queryText) . '%'
                )
            );

            echo wp_json_encode($queryResult);
            exit;
        }

        public function searchTags()
        {
            header('Content-type: application/json;');

            if (!wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'calendar_filter_nonce')) {
                return '[]';
            }

            $queryText = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
            global $wpdb;

            $queryResult = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT t.term_id AS id, t.name AS text
                FROM {$wpdb->term_taxonomy} as tt
                INNER JOIN {$wpdb->terms} as t ON (tt.term_id = t.term_id)
                WHERE taxonomy = 'post_tag' AND t.name LIKE %s
                ORDER BY 2
                LIMIT 20",
                    '%' . $wpdb->esc_like($queryText) . '%'
                )
            );

            echo wp_json_encode($queryResult);
            exit;
        }

        /**
         * Sanitize a $_GET or similar filter being used on the calendar
         *
         * @param string $key Filter being sanitized
         * @param string $dirty_value Value to be sanitized
         *
         * @return string $sanitized_value Safe to use value
         * @since 0.8
         *
         */
        public function sanitize_filter($key, $dirty_value)
        {
            switch ($key) {
                case 'post_status':
                    // Whitelist-based validation for this parameter
                    $valid_statuses   = wp_list_pluck($this->get_post_statuses(), 'slug');
                    $valid_statuses[] = 'future';
                    $valid_statuses[] = 'unpublish';
                    $valid_statuses[] = 'publish';
                    $valid_statuses[] = 'trash';
                    if (in_array($dirty_value, $valid_statuses)) {
                        return $dirty_value;
                    } else {
                        return '';
                    }
                    break;
                case 'cpt':
                    $cpt                  = sanitize_key($dirty_value);
                    $supported_post_types = $this->get_post_types_for_module($this->module);
                    if ($cpt && in_array($cpt, $supported_post_types)) {
                        return $cpt;
                    } else {
                        return '';
                    }
                    break;
                case 'start_date':
                    return date('Y-m-d', strtotime($dirty_value));
                    break;
                case 'cat':
                case 'tag':
                case 'author':
                    return intval($dirty_value);
                    break;
                case 'weeks':
                    $weeks = intval($dirty_value);

                    return empty($weeks) ? self::DEFAULT_NUM_WEEKS : $weeks;
                    break;
                default:
                    return false;
                    break;
            }
        }

        public function calendar_filter_options($select_id, $select_name, $filters)
        {
            switch ($select_id) {
                case 'post_status':
                    $post_statuses = $this->get_post_statuses();
                    ?>
                    <select id="<?php echo esc_attr($select_id); ?>" name="<?php echo esc_attr($select_name); ?>">
                        <option value=""><?php esc_html_e('All statuses', 'publishpress'); ?></option>
                        <?php
                        foreach ($post_statuses as $post_status) {
                            echo "<option value='" . esc_attr($post_status->slug) . "' " . selected(
                                    $post_status->slug,
                                    $filters['post_status']
                                ) . '>' . esc_html($post_status->name) . '</option>';
                        }
                        ?>
                    </select>
                    <?php
                    break;
                case 'cat':
                    $categoryId = isset($filters['cat']) ? (int)$filters['cat'] : 0;
                    ?>
                    <select id="filter_category" name="cat">
                        <option value=""><?php esc_html_e('View all categories', 'publishpress'); ?></option>
                        <?php
                        if (!empty($categoryId)) {
                            $category = get_term($categoryId, 'category');

                            echo "<option value='" . esc_attr($categoryId) . "' selected='selected'>" . esc_html($category->name) . "</option>";
                        }
                        ?>
                    </select>
                    <?php
                    break;
                case 'tag':
                    $tagId = isset($filters['tag']) ? (int)$filters['tag'] : 0;
                    ?>
                    <select id="filter_tag" name="tag">
                        <option value=""><?php esc_html_e('All tags', 'publishpress'); ?></option>
                        <?php
                        if (!empty($tagId)) {
                            $tag = get_term($tagId, 'post_tag');

                            echo "<option value='" . esc_attr($tagId) . "' selected='selected'>" . esc_html($tag->name) . "</option>";
                        }
                        ?>
                    </select>
                    <?php
                    break;
                case 'author':
                    $authorId = isset($filters['author']) ? (int)$filters['author'] : 0;
                    $selectedOptionAll = empty($authorId) ? 'selected="selected"' : '';
                    ?>
                    <select id="filter_author" name="author">
                        <option value="" <?php echo $selectedOptionAll; ?>>
                            <?php esc_html_e('All authors', 'publishpress'); ?>
                        </option>
                        <?php
                        if (!empty($authorId)) {
                            $author = get_user_by('id', $authorId);
                            $option = '';

                            if (!empty($author)) {
                                $option = '<option value="' . esc_attr($authorId) . '" selected="selected">' . esc_html($author->display_name) . '</option>';
                            }

                            $option = apply_filters('publishpress_author_filter_selected_option', $option, $authorId);

                            echo $option;
                        }
                        ?>
                    </select>
                    <?php
                    break;
                case 'type':
                    $supported_post_types = $this->get_post_types_for_module($this->module);
                    if (count($supported_post_types) > 1) {
                        ?>
                        <select id="type" name="cpt">
                            <option value=""><?php esc_html_e('All types', 'publishpress'); ?></option>
                            <?php
                            foreach ($supported_post_types as $key => $post_type_name) {
                                $all_post_types = get_post_types(null, 'objects');
                                echo '<option value="' . esc_attr($post_type_name) . '"' . selected(
                                        $post_type_name,
                                        $filters['cpt']
                                    ) . '>' . esc_html($all_post_types[$post_type_name]->labels->name) . '</option>';
                            } ?>
                        </select>
                        <?php
                    }
                    break;
                case 'weeks':
                    if (!isset($filters['weeks'])) {
                        $filters['weeks'] = self::DEFAULT_NUM_WEEKS;
                    }

                    $output = '<select id="weeks" name="weeks">';
                    for ($i = 1; $i <= 12; $i++) {
                        $output .= '<option value="' . esc_attr($i) . '" ' . selected(
                                $i,
                                $filters['weeks'],
                                false
                            ) . '>' . sprintf(
                                esc_html(_n('%s week', '%s weeks', $i, 'publishpress')),
                                esc_attr($i)
                            ) . '</option>';
                    }
                    $output .= '</select>';
                    echo $output;
                    break;
                default:
                    do_action('pp_calendar_filter_display', $select_id, $select_name, $filters);
                    break;
            }
        }

        /**
         * When a post is updated, clean the <li> html post cache for it
         */
        public function action_clean_li_html_cache($post_id)
        {
            wp_cache_delete($post_id . 'can_modify', self::$post_li_html_cache_key);
            wp_cache_delete($post_id . 'read_only', self::$post_li_html_cache_key);
        }

        /**
         * Filters the status text of the post. Fixing the text for future and past dates.
         *
         * @param string $status The status text.
         * @param WP_Post $post Post object.
         * @param string $column_name The column name.
         * @param string $mode The list display mode ('excerpt' or 'list').
         */
        public function filter_post_date_column_status($status, $post, $column_name, $mode)
        {
            if ('date' === $column_name) {
                if ('0000-00-00 00:00:00' === $post->post_date) {
                    $time_diff = 0;
                } else {
                    $time = get_post_time('G', true, $post);

                    $time_diff = time() - $time;
                }

                if ('future' === $post->post_status) {
                    if ($time_diff > 0) {
                        return '<strong class="error-message">' . esc_html__('Missed schedule') . '</strong>';
                    } else {
                        return esc_html__('Scheduled');
                    }
                }

                if ('publish' === $post->post_status) {
                    return esc_html__('Published');
                }

                return esc_html__('Publish on');
            }

            return $status;
        }

        /**
         * @param $status
         * @return  bool
         *
         * @access  private
         */
        private function showPostsPublishTime($status)
        {
            if ($this->module->options->show_posts_publish_time === 'on') {
                $this->module->options->show_posts_publish_time = [
                    'publish' => 'on',
                    'future'  => 'on',
                ];
            }

            return isset($this->module->options->show_posts_publish_time[$status])
                && $this->module->options->show_posts_publish_time[$status] == 'on';
        }

        /**
         * Sanitizes a given string input.
         *
         * @param string $input_value
         *
         * @return  string
         */
        public static function sanitize_text_input($input_value = '')
        {
            return sanitize_text_field($input_value);
        }

        /**
         * Sanitizes a given author id.
         *
         * @param string $author_id
         *
         * @return  int
         */
        public static function sanitize_author_input($author_id = '')
        {
            return (int)sanitize_text_field($author_id);
        }

        /**
         * @param string $post_author_id
         *
         * @return  int|null
         *
         * @throws  Exception
         */
        public static function validate_author_input($post_author_id = '')
        {
            if (empty($post_author_id)) {
                return null;
            }

            $user = get_user_by('id', $post_author_id);

            $is_valid = (is_object($user) && !is_wp_error($user)) ? $user->has_cap('edit_posts') : false;

            if (!apply_filters('publishpress_author_can_edit_posts', $is_valid, $post_author_id)) {
                throw new Exception(
                    esc_html__(
                        "The selected user doesn't have enough permissions to be set as the post author.",
                        'publishpress'
                    )
                );
            }

            return (int)$post_author_id;
        }

        public function setDefaultCapabilities()
        {
            $role = get_role('administrator');

            $view_calendar_cap = 'pp_view_calendar';
            $view_calendar_cap = apply_filters('pp_view_calendar_cap', $view_calendar_cap);

            if (!$role->has_cap($view_calendar_cap)) {
                $role->add_cap($view_calendar_cap);
            }
        }
    }
}
