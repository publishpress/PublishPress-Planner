<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
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

use PublishPress\Legacy\Util;
use PublishPress\Notifications\Traits\Dependency_Injector;

if (! class_exists('PP_Calendar')) {
    /**
     * class PP_Calendar
     * Threaded commenting in the admin for discussion between writers and editors
     *
     * @author batmoo
     */
    #[\AllowDynamicProperties]
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
        const TIME_FORMAT_12H_NO_LEADING_ZEROES = 'ga';

        /**
         * Time 12h-format with leading zeroes.
         */
        const TIME_FORMAT_12H_WITH_LEADING_ZEROES = 'ha';

        /**
         * Time 24h-format with leading zeroes.
         */
        const TIME_FORMAT_24H = 'H';

        const VIEW_CAPABILITY = 'pp_view_calendar';

        /**
         * [$module description]
         *
         * @var [type]
         */
        public $module;
        
        public $module_url;

        /**
         * [$start_date description]
         *
         * @var string
         */
        public $start_date = '';

        /**
         * [$current_week description]
         *
         * @var int
         */
        public $current_week = 1;

        /**
         * Default number of weeks to show per screen
         *
         * @var int
         */
        public $total_weeks = 6;

        /**
         * Counter of hidden posts per date square
         *
         * @var int
         */
        public $hidden = 0;

        /**
         * Total number of posts to be shown per square before 'more' link
         *
         * @var int
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
         * @var array
         */
        private $postTypeObjectCache = [];

        /**
         * @var \PublishPress\Utility\Date
         */
        private $dateUtil;
    
        /**
         * @var array
         */
        public $filters;
    
        /**
         * @var array
         */
        public $form_filters = [];
    
        /**
         * @var array
         */
        public $form_filter_list = [];
    
        /**
         * [$user_filters description]
         *
         * @var [type]
         */
        public $user_filters;
    
        /**
         * Custom methods
         *
         * @var array
         */
        private $terms_options = [];
    
        /**
         * [$content_calendar_datas description]
         *
         * @var [type]
         */
        public $content_calendar_datas;

        /**
         * Construct the PP_Calendar class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title' => __('Content Calendar', 'publishpress'),
                'short_description' => false,
                'extended_description' => false,
                'module_url' => $this->module_url,
                'icon_class' => 'dashicons dashicons-calendar-alt',
                'slug' => 'calendar',
                'post_type_support' => 'pp_calendar',
                'default_options' => [
                    'enabled' => 'on',
                    'post_types' => [
                        'post' => 'on',
                        'page' => 'off',
                    ],
                    'ics_subscription' => 'on',
                    'ics_subscription_public_visibility' => 'off',
                    'ics_secret_key' => wp_generate_password(),
                    'show_posts_publish_time' => ['publish' => 'on', 'future' => 'on'],
                    'default_publish_time' => '',
                    'show_calendar_posts_full_title' => 'off',
                    // Leave default as non array to confirm if user save settings or not
                    'content_calendar_filters' => '',
                    'content_calendar_custom_filters' => '',
                ],
                'messages' => [
                    'post-date-updated' => __('Post date updated.', 'publishpress'),
                    'status-updated' => __('Post status updated.', 'publishpress'),
                    'update-error' => __(
                        'There was an error updating the post. Please try again.',
                        'publishpress'
                    ),
                    'published-post-ajax' => __(
                        "Updating the post date dynamically doesn't work for published content. Please <a href='%s'>edit the post</a>.",
                        'publishpress'
                    ),
                    'key-regenerated' => __(
                        'iCal secret key regenerated. Please inform all users they will need to resubscribe.',
                        'publishpress'
                    ),
                ],
                'configure_page_cb' => 'print_configure_view',
                'settings_help_tab' => [
                    'id' => 'pp-calendar-overview',
                    'title' => __('Overview', 'publishpress'),
                    'content' => __(
                        '<p>The calendar is a convenient week-by-week or month-by-month view into your content. Quickly see which stories are on track to being published on time, and which will need extra effort.</p>',
                        'publishpress'
                    ),
                ],
                'settings_help_sidebar' => __(
                    '<p><strong>For more information:</strong></p><p><a href="https://publishpress.com/features/calendar/">Calendar Documentation</a></p><p><a href="https://github.com/ostraining/PublishPress">PublishPress on Github</a></p>',
                    'publishpress'
                ),
                'show_configure_btn' => false,
                'options_page' => true,
                'page_link' => admin_url('admin.php?page=pp-calendar'),
            ];

            $this->dateUtil = new \PublishPress\Utility\Date();

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

            if (is_admin()) {
                $this->setDefaultCapabilities();
            }

            // Can view the calendar?
            if (! $this->currentUserCanViewCalendar()) {
                return false;
            }

            if (is_admin()) {
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

                // Ajax insert post placeholder for a specific date
                add_action('wp_ajax_pp_insert_post', [$this, 'handle_ajax_insert_post']);

                add_action('wp_ajax_publishpress_calendar_search_authors', [$this, 'searchAuthors']);
                add_action('wp_ajax_publishpress_calendar_search_terms', [$this, 'searchTerms']);
                add_action('wp_ajax_publishpress_calendar_get_data', [$this, 'fetchCalendarDataJson']);
                add_action('wp_ajax_publishpress_calendar_move_item', [$this, 'moveCalendarItemToNewDate']);
                add_action('wp_ajax_publishpress_calendar_get_post_data', [$this, 'getPostData']);
                add_action('wp_ajax_publishpress_calendar_get_post_type_fields', [$this, 'getPostTypeFields']);
                add_action('wp_ajax_publishpress_calendar_create_item', [$this, 'createItem']);

                // Action to regenerate the calendar feed secret
                add_action('admin_init', [$this, 'handle_regenerate_calendar_feed_secret']);

                add_filter('post_date_column_status', [$this, 'filter_post_date_column_status'], 12, 4);

                add_filter('pp_calendar_after_form_submission_sanitize_title', [$this, 'sanitize_text_input'], 10, 1);
                add_filter('pp_calendar_after_form_submission_sanitize_content', [$this, 'sanitize_text_input'], 10, 1);
                add_filter('pp_calendar_after_form_submission_sanitize_author', [$this, 'sanitize_author_input'], 10, 1);
                add_filter('pp_calendar_after_form_submission_validate_author', [$this, 'validateAuthorForPost'], 10, 1);
                add_filter('admin_body_class', [$this, 'add_admin_body_class']);
            }

            // Clear li cache for a post when post cache is cleared
            add_action('clean_post_cache', [$this, 'action_clean_li_html_cache']);

            // Cache WordPress default date/time formats.
            $this->default_date_time_format = get_option('date_format') . ' ' . get_option('time_format');
        }

        public function add_admin_body_class($classes) {
            global $pagenow;
            if ('admin.php' === $pagenow && isset($_GET['page']) && $_GET['page'] === self::MENU_SLUG) {
                $classes .= ' pp-content-calendar-page';
            }
            return $classes;
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
            if (! isset($_GET['user'], $_GET['user_key'], $_GET['pp_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return $original_template;
            }

            // Confirm the action
            if ('pp_calendar_ics_feed' !== $_GET['pp_action']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
            if (! isset($_GET['end'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $end = 'm2';
            } else {
                $end = preg_replace('/[^wm0-9]/', '', sanitize_text_field($_GET['end'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }

            if (preg_match('/m[0-9]*/', $end)) {
                $weeks = (int)str_replace('m', '', $end) * 4;
            } else {
                $weeks = (int)str_replace('w', '', $end);
            }

            // Calculate the diff in weeks from start date until now
            $today = date('Y-m-d'); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

            $first = DateTime::createFromFormat('Y-m-d', $startDate);
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
            if (! isset($_GET['start'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                // Current week
                $start = 0;
            } else {
                $start = (int)$_GET['start']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }

            if ($start > 0) {
                $startDate = date('Y-m-d', strtotime('-' . $start . ' months', strtotime($startDate))); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
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
                esc_html__('Content Calendar', 'publishpress'),
                $this->getViewCapability(),
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
                esc_html__('Content Calendar', 'publishpress'),
                esc_html__('Content Calendar', 'publishpress'),
                $this->getViewCapability(),
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
            
            $viewCapability = $this->getViewCapability();
            $eligible_roles = ['administrator', 'editor', 'author'];

            foreach ($eligible_roles as $eligible_role) {
                $role = get_role($eligible_role);
                if (is_object($role) && !$role->has_cap($viewCapability)) {
                    $role->add_cap($viewCapability);
                }
            }
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
            if ('admin.php' === $pagenow && isset($_GET['page']) && $_GET['page'] === 'pp-calendar') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                wp_enqueue_style(
                    'publishpress-calendar-css',
                    $this->module_url . 'lib/calendar.css',
                    ['publishpress-select2'],
                    PUBLISHPRESS_VERSION
                );

                wp_enqueue_style(
                    'publishpress-async-calendar-theme-light-css',
                    $this->module_url . 'lib/async-calendar/styles/themes/theme-light.css',
                    [],
                    PUBLISHPRESS_VERSION
                );

                wp_enqueue_style(
                    'publishpress-async-calendar-css',
                    $this->module_url . 'lib/async-calendar/styles/async-calendar.css',
                    ['publishpress-async-calendar-theme-light-css'],
                    PUBLISHPRESS_VERSION
                );

                if (isset($this->module->options->show_calendar_posts_full_title) && 'on' === $this->module->options->show_calendar_posts_full_title) {
                    $inline_style = '.publishpress-calendar .publishpress-calendar-item {
                        height: auto;
                        max-height: max-content;
                        white-space: break-spaces;
                    }';
                    wp_add_inline_style('publishpress-async-calendar-css', $inline_style);
                }

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
        private function currentUserCanViewCalendar()
        {
            return current_user_can($this->getViewCapability());
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

                if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'pp-calendar' && ! isset($_GET['stop-the-calendar'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    global $wp_scripts;

                    if (! isset($wp_scripts->queue['react'])) {
                        wp_enqueue_script(
                            'react',
                            PUBLISHPRESS_URL . 'common/js/react.min.js',
                            [],
                            PUBLISHPRESS_VERSION,
                            true
                        );
                        wp_enqueue_script(
                            'react-dom',
                            PUBLISHPRESS_URL . 'common/js/react-dom.min.js',
                            ['react'],
                            PUBLISHPRESS_VERSION,
                            true
                        );
                    }

                    wp_enqueue_script(
                        'jquery-inputmask',
                        PUBLISHPRESS_URL . 'common/js/jquery.inputmask.min.js',
                        [
                            'jquery',
                        ],
                        PUBLISHPRESS_VERSION,
                        true
                    );

                    wp_enqueue_script(
                        'date_i18n',
                        PUBLISHPRESS_URL . 'common/js/date-i18n.js',
                        [],
                        PUBLISHPRESS_VERSION,
                        true
                    );

                    // TODO: Replace react and react-dom with the wp.element dependency
                    wp_enqueue_script(
                        'publishpress-async-calendar-js',
                        $this->module_url . 'lib/async-calendar/js/index.min.js',
                        [
                            'react',
                            'react-dom',
                            'jquery',
                            'jquery-ui-core',
                            'jquery-ui-sortable',
                            'jquery-ui-draggable',
                            'jquery-ui-droppable',
                            'jquery-inputmask',
                            'wp-i18n',
                            'wp-element',
                            'date_i18n',
                        ],
                        PUBLISHPRESS_VERSION,
                        true
                    );

                    /*
                     * Filters
                     */
                    $userFilters             = $this->get_filters();
                    $calendar_request_args   = $userFilters;
                    $calendar_request_filter = $userFilters;

                    $maxVisibleItemsOption = isset($this->module->options->max_visible_posts_per_date) && ! empty($this->default_max_visible_posts_per_date) ?
                        (int)$this->module->options->max_visible_posts_per_date : $this->default_max_visible_posts_per_date;

                    $postStatuses = $this->getPostStatusOptions();

                    $postTypes = [];
                    $postTypesUserCanCreate = [];
                    foreach ($this->get_selected_post_types() as $postTypeName) {
                        $postType = get_post_type_object($postTypeName);

                        $postTypes[] = [
                            'value' => esc_attr($postTypeName),
                            'text' => esc_html($postType->label)
                        ];

                        if (current_user_can($postType->cap->edit_posts)) {
                            $postTypesUserCanCreate[] = [
                                'value' => esc_attr($postTypeName),
                                'text' => esc_html($postType->labels->singular_name)
                            ];
                        }
                    }

                    $numberOfWeeksToDisplay = isset($calendar_request_filter['weeks']) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        (int)$calendar_request_filter['weeks'] : self::DEFAULT_NUM_WEEKS; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                    $firstDateToDisplay = (isset($calendar_request_filter['start_date']) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            sanitize_text_field($calendar_request_filter['start_date']) : date('Y-m-d')) . ' 00:00:00'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.DateTime.RestrictedFunctions.date_date
                    $firstDateToDisplay = $this->get_beginning_of_week($firstDateToDisplay);
                    $endDate = $this->get_ending_of_week(
                        $firstDateToDisplay,
                        'Y-m-d',
                        $numberOfWeeksToDisplay
                    );

                    $params = [
                        'requestFilter' => $calendar_request_filter,
                        'numberOfWeeksToDisplay' => $numberOfWeeksToDisplay,
                        'firstDateToDisplay' => esc_js($firstDateToDisplay),
                        'theme' => 'light',
                        'weekStartsOnSunday' => (int)get_option('start_of_week') === 0,
                        'todayDate' => esc_js(date('Y-m-d 00:00:00')), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                        'dateFormat' => esc_js(get_option('date_format', 'Y-m-d H:i:s')),
                        'timeFormat' => esc_js($this->getCalendarTimeFormat()),
                        'maxVisibleItems' => $maxVisibleItemsOption,
                        'statuses' => $postStatuses,
                        'postTypes' => $postTypes,
                        'postTypesCanCreate' => $postTypesUserCanCreate,
                        'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
                        'nonce' => wp_create_nonce('publishpress-calendar-get-data'),
                        'userCanAddPosts' => count($postTypesUserCanCreate) > 0,
                        'items' => $this->getCalendarData($firstDateToDisplay, $endDate, $calendar_request_args),
                        'allowAddingMultipleAuthors' => (bool)apply_filters(
                            'publishpress_calendar_allow_multiple_authors',
                            false
                        ),
                        'proActive' => Util::isPlannersProActive(),
                        'strings' => [
                            'loading' => esc_js(__('Loading...', 'publishpress')),
                            'loadingItem' => esc_js(__('Loading item...', 'publishpress')),
                            'clickToAdd' => esc_js(__('Click to add', 'publishpress')),
                            'movingTheItem' => esc_js(__('Moving the item...', 'publishpress')),
                            'hideItems' => esc_js(__('Hide the %s last items', 'publishpress')),
                            'showMore' => esc_js(__('Show %s more', 'publishpress')),
                            'untitled' => esc_js(__('Untitled', 'publishpress')),
                            'close' => esc_js(__('Close', 'publishpress')),
                            'save' => esc_js(__('Save', 'publishpress')),
                            'saving' => esc_js(__('Saving...', 'publishpress')),
                            'saveAndEdit' => esc_js(__('Save and edit', 'publishpress')),
                            'addContentFor' => esc_js(__('Add content for %s', 'publishpress')),
                            'postTypeNotFound' => esc_js(__('Post type not found', 'publishpress')),
                            'postType' => esc_js(__('Post type:', 'publishpress')),
                            'pleaseWaitLoadingFormFields' => esc_js(__('Please, wait! Loading the form fields...', 'publishpress')),
                            'weekDaySun' => esc_js(__('Sun', 'publishpress')),
                            'weekDayMon' => esc_js(__('Mon', 'publishpress')),
                            'weekDayTue' => esc_js(__('Tue', 'publishpress')),
                            'weekDayWed' => esc_js(__('Wed', 'publishpress')),
                            'weekDayThu' => esc_js(__('Thu', 'publishpress')),
                            'weekDayFri' => esc_js(__('Fri', 'publishpress')),
                            'weekDaySat' => esc_js(__('Sat', 'publishpress')),
                            'monthJan' => esc_js(__('Jan', 'publishpress')),
                            'monthFeb' => esc_js(__('Feb', 'publishpress')),
                            'monthMar' => esc_js(__('Mar', 'publishpress')),
                            'monthApr' => esc_js(__('Apr', 'publishpress')),
                            'monthMay' => esc_js(__('May', 'publishpress')),
                            'monthJun' => esc_js(__('Jun', 'publishpress')),
                            'monthJul' => esc_js(__('Jul', 'publishpress')),
                            'monthAug' => esc_js(__('Aug', 'publishpress')),
                            'monthSep' => esc_js(__('Sep', 'publishpress')),
                            'monthOct' => esc_js(__('Oct', 'publishpress')),
                            'monthNov' => esc_js(__('Nov', 'publishpress')),
                            'monthDec' => esc_js(__('Dec', 'publishpress')),
                            'allStatuses' => esc_js(__('All statuses', 'publishpress')),
                            'allCategories' => esc_js(__('All categories', 'publishpress')),
                            'allTags' => esc_js(__('All tags', 'publishpress')),
                            'allAuthors' => esc_js(__('All authors', 'publishpress')),
                            'allTypes' => esc_js(__('All types', 'publishpress')),
                            'xWeek' => esc_js(__('%d week', 'publishpress')),
                            'xWeeks' => esc_js(__('%d weeks', 'publishpress')),
                            'today' => esc_js(__('Today', 'publishpress')),
                            'noTerms' => esc_js(__('No terms', 'publishpress')),
                            'post_date_label'    => esc_html__('Post Date', 'publishpress'),
                            'edit_label'         => esc_html__('Edit', 'publishpress'),
                            'delete_label'       => esc_html__('Trash', 'publishpress'),
                            'preview_label'      => esc_html__('Preview', 'publishpress'),
                            'view_label'         => esc_html__('View', 'publishpress'),
                            'prev_label'         => esc_html__('Previous Post', 'publishpress'),
                            'next_label'         => esc_html__('Next Post', 'publishpress'),
                            'post_status_label'  => esc_html__('Post Status', 'publishpress'),
                            'update_label'       => esc_html__('Save Changes', 'publishpress'),
                            'empty_term'         => esc_html__('Taxonomy not set.', 'publishpress'),
                            'post_author'        => esc_html__('Author', 'publishpress'),
                            'date_format'        => pp_convert_date_format_to_jqueryui_datepicker(get_option('date_format')),
                            'week_first_day'     => esc_js(get_option('start_of_week')),
                        ]
                    ];
                    wp_localize_script('publishpress-async-calendar-js', 'publishpressCalendarParams', $params);

                    global $wp_locale;
                    $monthNames = array_map([&$wp_locale, 'get_month'], range(1, 12));
                    $monthNamesShort = array_map([&$wp_locale, 'get_month_abbrev'], $monthNames);
                    $dayNames = array_map([&$wp_locale, 'get_weekday'], range(0, 6));
                    $dayNamesShort = array_map([&$wp_locale, 'get_weekday_abbrev'], $dayNames);
                    wp_localize_script(
                        "date_i18n",
                        "DATE_I18N",
                        array(
                            "month_names" => $monthNames,
                            "month_names_short" => $monthNamesShort,
                            "day_names" => $dayNames,
                            "day_names_short" => $dayNamesShort
                        )
                    );
                }
            }
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
         * @param \Sabre\VObject\Component\VCalendar
         * @param string $tzid Timezone ID as used in PHP's Date functions
         * @param int $from Unix timestamp with first date/time in this timezone
         * @param int $to Unix timestap with last date/time in this timezone
         *
         * @return mixed A Sabre\VObject\Component object representing a VTIMEZONE definition
         *               or false if no timezone information is available
         */
        private function generateVTimeZone(&$calendar, $tzid, $from = 0, $to = 0)
        {
            if (! $from) {
                $from = time();
            }
            if (! $to) {
                $to = $from;
            }

            try {
                $tz = new DateTimeZone($tzid);
            } catch (Exception $e) {
                return false;
            }

            // get all transitions for one year back/ahead
            $year = 86400 * 360;
            $transitions = $tz->getTransitions($from - $year, $to + $year);

            $vTimeZone = $calendar->add(
                'VTIMEZONE',
                [
                    'TZID' => $tz->getName(),
                ]
            );

            $standard = null;
            $daylight = null;
            $t_std = null;
            $t_dst = null;
            $tzfrom = 0;
            if (is_array($transitions) || is_object($transitions)) {
                foreach ($transitions as $i => $trans) {
                    if ($i == 0) {
                        $tzfrom = $trans['offset'] / 3600;
                        continue;
                    }

                    // daylight saving time definition
                    if ($trans['isdst']) {
                        $t_dst = $trans['ts'];
                        $dt = new DateTime($trans['time']);
                        $offset = $trans['offset'] / 3600;

                        $daylight = $vTimeZone->add(
                            'DAYLIGHT',
                            [
                                'DTSTART' => $dt->format('Ymd\THis'),
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
                        if (! empty($trans['abbr'])) {
                            $daylight->add('TZNAME', [$trans['abbr']]);
                        }

                        $tzfrom = $offset;
                    } else {
                        $t_std = $trans['ts'];
                        $dt = new DateTime($trans['time']);
                        $offset = $trans['offset'] / 3600;

                        $standard = $vTimeZone->add(
                            'STANDARD',
                            [
                                'DTSTART' => $dt->format('Ymd\THis'),
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
                        if (! empty($trans['abbr'])) {
                            $standard->add('TZNAME', [$trans['abbr']]);
                        }

                        $tzfrom = $offset;
                    }

                    // we covered the entire date range
                    if ($standard && $daylight && min($t_std, $t_dst) < $from && max($t_std, $t_dst) > $to) {
                        break;
                    }
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
            // phpcs:disable WordPress.Security.NonceVerification.Recommended

            // Only do .ics subscriptions when the option is active
            if ('on' != $this->module->options->ics_subscription) {
                die();
            }

            // Confirm all the arguments are present
            if (! isset($_GET['user'], $_GET['user_key'])) {
                die();
            }

            // Confirm this is a valid request
            $user = sanitize_user($_GET['user']);
            $user_key = sanitize_user($_GET['user_key']);
            $ics_secret_key = $this->module->options->ics_secret_key;
            if (! $ics_secret_key || md5($user . $ics_secret_key) !== $user_key) {
                die(esc_html($this->module->messages['nonce-failed']));
            }

            // Set up the post data to be printed
            $post_query_args = [];
            $calendar_filters = $this->calendar_filters();
            foreach ($calendar_filters as $filter) {
                if (isset($_GET[$filter]) && false !== ($value = $this->sanitize_filter(
                        $filter,
                        sanitize_text_field(
                            $_GET[$filter]
                        )
                    ))) {
                    $post_query_args[$filter] = $value;
                }
            }

            // Set the start date for the posts_where filter
            $this->start_date = sanitize_text_field(
                apply_filters(
                    'pp_calendar_ics_subscription_start_date',
                    $this->get_beginning_of_week(date('Y-m-d', current_time('timestamp'))) // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                )
            );

            $this->total_weeks = sanitize_text_field(
                apply_filters(
                    'pp_calendar_total_weeks',
                    $this->total_weeks,
                    $this->start_date,
                    'ics_subscription'
                )
            );

            $vCalendar = new Sabre\VObject\Component\VCalendar(
                [
                    'PRODID' => '-//PublishPress//PublishPress ' . PUBLISHPRESS_VERSION . '//EN',
                ]
            );

            $timezoneString = $this->dateUtil->getTimezoneString();

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
                $week_posts = $this->get_calendar_posts_for_week($post_query_args, 'ics_subscription');
                foreach ($week_posts as $date => $day_posts) {
                    foreach ($day_posts as $num => $post) {
                        if (empty($post->post_date_gmt) || $post->post_date_gmt == '0000-00-00 00:00:00') {
                            $calendar_date = get_gmt_from_date($post->post_date);
                        } else {
                            $calendar_date = $post->post_date_gmt;
                        }

                        $start_date = new DateTime($calendar_date);
                        $start_date->setTimezone($timeZone);

                        $end_date = new DateTime(date('Y-m-d H:i:s', strtotime($calendar_date) + (5 * 60))); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                        $end_date->setTimezone($timeZone);

                        if (empty($post->post_modified_gmt) || $post->post_modified_gmt == '0000-00-00 00:00:00') {
                            $calendar_modified_date = get_gmt_from_date($post->post_modified);
                        } else {
                            $calendar_modified_date = $post->post_modified_gmt;
                        }

                        $last_modified = new DateTime($calendar_modified_date);
                        $last_modified->setTimezone($timeZone);

                        // Remove the convert chars and wptexturize filters from the title
                        remove_filter('the_title', 'convert_chars');
                        remove_filter('the_title', 'wptexturize');

                        // Description should include everything visible in the calendar popup
                        $information_fields = $this->get_post_information_fields($post);
                        $eventDescription = '';
                        if (! empty($information_fields)) {
                            foreach ($information_fields as $key => $values) {
                                $eventDescription .= $values['label'] . ': ' . $values['value'] . "\n";
                            }
                            $eventDescription = rtrim($eventDescription);
                        }

                        $vCalendar->add(
                            'VEVENT',
                            [
                                'UID' => $post->guid,
                                'SUMMARY' => $this->do_ics_escaping(apply_filters('the_title', $post->post_title))
                                    . ' - ' . $this->get_post_status_friendly_name(get_post_status($post->ID)),
                                'DTSTART' => $start_date,
                                'DTEND' => $end_date,
                                'LAST-MODIFIED' => $last_modified,
                                'URL' => get_post_permalink($post->ID),
                                'DESCRIPTION' => $eventDescription,
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
            // phpcs:enable
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
            if (! isset($_GET['action']) || 'pp_calendar_regenerate_calendar_feed_secret' != $_GET['action']) {
                return;
            }

            if (! current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            if (! isset($_GET['_wpnonce'])
                || ! wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'pp-regenerate-ics-key')
            ) {
                wp_die($this->module->messages['nonce-failed']);
            }

            PublishPress()->update_module_option($this->module->name, 'ics_secret_key', wp_generate_password());

            $args = [
                'page' => PP_Modules_Settings::SETTINGS_SLUG,
                'settings_module' => $this->module->settings_slug,
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
            return $this->update_user_filters();
        }

        /**
         * Update the current user's filters for calendar display with the filters in $_GET($request_filter). The filters
         * in $_GET($request_filter) take precedence over the current users filters if they exist.
         * @param array $request_filter
         *
         * @return array $filters updated filter
         */
        public function update_user_filters($request_filter = [])
        {
            $user_filters = [
                'weeks'         => '',
                'start_date'    => '',
                'me_mode'       => '',
                's'             => '',
                'post_status'   => '',
            ];

            if (!empty($_POST['co_form_action']) && !empty($_POST['_nonce']) && $_POST['co_form_action'] == 'reset_filter' && wp_verify_nonce(sanitize_key($_POST['_nonce']), 'content_calendar_filter_rest_nonce')) {
                $user_filters['weeks'] = self::DEFAULT_NUM_WEEKS;
                $user_filters['start_date'] = date('Y-m-d', current_time('timestamp'));
                return $user_filters;
            }

            $current_user  = wp_get_current_user();

            if (empty($request_filter)) {
                $request_filter = $_GET;
            }

            // Get content calendar data
            $this->content_calendar_datas = $this->get_content_calendar_datas();
            
            $filters = $this->content_calendar_datas['content_calendar_filters'];

            /**
             * @param array $filters
             *
             * @return array
             */
            $this->filters = apply_filters('publishpress_content_calendar_filters', $filters);

            $this->filters = array_merge([
                'weeks'         => __('Weeks', 'publishpress'),
                'start_date'    => __('Start Date', 'publishpress'),
                'me_mode'       => __('Me Mode', 'publishpress'),
                's'             =>  __('Search', 'publishpress'),
            ], $this->filters);
            

            $editorial_metadata = $this->terms_options;

            foreach ($this->filters as $filter_key => $filter_label) {
                if (array_key_exists($filter_key, $editorial_metadata)) {
                    //add metadata to filter
                    $meta_term = $editorial_metadata[$filter_key];
                    $meta_term_type = $meta_term['type'];
                    if ($meta_term_type === 'checkbox') {
                        if (! isset($request_filter[$filter_key])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            $check_value = null;
                        } else {
                            $check_value = absint($this->filter_get_param($filter_key, $request_filter));
                        }
                        $user_filters[$filter_key] = $check_value;
                    } elseif ($meta_term_type === 'date') {
                        $user_filters[$filter_key]                   = $this->filter_get_param_text($filter_key, $request_filter);
                        $user_filters[$filter_key . '_start']        = $this->filter_get_param_text($filter_key . '_start', $request_filter);
                        $user_filters[$filter_key . '_end']          = $this->filter_get_param_text($filter_key . '_end', $request_filter);
                        $user_filters[$filter_key . '_start_hidden'] = $this->filter_get_param_text($filter_key . '_start_hidden', $request_filter);
                        $user_filters[$filter_key . '_end_hidden']   = $this->filter_get_param_text($filter_key . '_end_hidden', $request_filter);
                    }  elseif ($meta_term_type === 'user') {
                        if (empty($user_filters['me_mode'])) {
                            $user_filters[$filter_key] = $this->filter_get_param_text($filter_key, $request_filter);
                        }
                    } else {
                        $user_filters[$filter_key] = $this->filter_get_param_text($filter_key, $request_filter);
                    }
                } else {
                    // other filters
                    $user_filters[$filter_key] = $this->filter_get_param_text($filter_key, $request_filter);
                    if (in_array($filter_key, $this->content_calendar_datas['meta_keys']) || in_array($filter_key, ['ppch_co_yoast_seo__yoast_wpseo_linkdex', 'ppch_co_yoast_seo__yoast_wpseo_content_score'])) {
                        $user_filters[$filter_key . '_operator'] = $this->filter_get_param_text($filter_key . '_operator', $request_filter);
                    }
                }
            }

            $current_user_filters = [];
            $current_user_filters = $this->get_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', true);

            // If any of the $_GET vars are missing, then use the current user filter
            foreach ($user_filters as $key => $value) {
                if (is_null($value) && $value !== '0' && ! empty($current_user_filters[$key]) && ! is_null($current_user_filters[$key])) {
                    $user_filters[$key] = $current_user_filters[$key];
                } elseif (is_null($value) && $value !== '0' ) {
                    $user_filters[$key] = '';
                }
            }

            // Fix week, if no specific week was set
            if (empty($user_filters['weeks'])) {
                $user_filters['weeks'] = self::DEFAULT_NUM_WEEKS;
            }

            // Fix start_date, if no specific date was set
            if (empty($user_filters['start_date'])) {
                $user_filters['start_date'] = date('Y-m-d', current_time('timestamp'));
            }

            // Set the start date as the beginning of the week, according to blog settings
            $user_filters['start_date'] = $this->get_beginning_of_week($user_filters['start_date']);

            if (!empty($user_filters['me_mode'])) {
                $user_filters['author'] = $current_user->ID;
            }

            $user_filters = apply_filters('pp_content_calendar_filter_values', $user_filters, $current_user_filters);

            $this->update_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', $user_filters);

            return $user_filters;
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

            if (! isset($this->module->options->post_types)) {
                $this->module->options->post_types = [];
            }

            if (! empty($this->module->options->post_types)) {
                foreach ($this->module->options->post_types as $type => $value) {
                    if ('on' === $value) {
                        $return[] = $type;
                    }
                }
            }

            return $return;
        }

        public function content_calendar_filters()
        {
            $select_filter_names = [];
    
            $editorial_metadata = $this->terms_options;
    
            foreach ($this->filters as $filter_key => $filter_label) {
                if (array_key_exists($filter_key, $editorial_metadata) && $editorial_metadata[$filter_key]['type'] === 'date') {
                    $select_filter_names[$filter_key . '_start']        = $filter_key . '_start';
                    $select_filter_names[$filter_key . '_end']          = $filter_key . '_end';
                    $select_filter_names[$filter_key . '_start_hidden'] = $filter_key . '_start_hidden';
                    $select_filter_names[$filter_key . '_end_hidden']   = $filter_key . '_end_hidden';
                }
                $select_filter_names[$filter_key] = $filter_key;
            }
    
            return apply_filters('PP_Content_Calendar_filter_names', $select_filter_names);
        }

        public function content_calendar_filter_options($select_id, $select_name, $filters)
        {
            
            if (array_key_exists($select_id, $this->terms_options)) {
                $select_id = 'metadata_key';
            }
    
            if (array_key_exists($select_id, $this->content_calendar_datas['taxonomies']) && taxonomy_exists($select_id)) {
                $select_id = 'taxonomy';
            }
    
            $filter_label   = '';
            $selected_value = '';
    
            ob_start();
    
            switch ($select_id) {
                case 'post_status':
                    $post_statuses = $this->get_post_statuses();
                    $filter_label   = esc_html__('Post Status', 'publishpress');
                    ?>
                    <select id="post_status" name="post_status"><!-- Status selectors -->
                        <option value=""><?php
                            _e('All statuses', 'publishpress'); ?></option>
                        <?php
                        foreach ($post_statuses as $post_status) {
                            if ($post_status->slug == $filters['post_status']) {
                                $selected_value = $post_status->label;
                            }
                            echo "<option value='" . esc_attr($post_status->slug) . "' " . selected(
                                    $post_status->slug,
                                    $filters['post_status']
                                ) . ">" . esc_html($post_status->label) . "</option>";
                        }
                        ?>
                    </select>
                    <?php
                    break;
    
                case 'taxonomy':
                    $taxonomySlug = isset($filters[$select_name]) ? sanitize_key($filters[$select_name]) : '';
                    $taxonomy = get_taxonomy($select_name);
                    $filter_label   = esc_html($taxonomy->label);
                    ?>
                    <select 
                        class="filter_taxonomy" 
                        id="<?php echo esc_attr('filter_taxonomy_' . $select_name); ?>" 
                        data-taxonomy="<?php echo esc_attr($select_name); ?>" 
                        name="<?php echo esc_attr($select_name); ?>"
                        data-placeholder="<?php printf(esc_attr__('All %s', 'publishpress'), esc_html($taxonomy->label)); ?>"
                        >
                        <option value="">
                            <?php echo sprintf(esc_html__('All %s', 'publishpress'), esc_html($taxonomy->label)); ?>
                        </option>
                        <?php
                        if ($taxonomySlug) {
                            $term = get_term_by('slug', $taxonomySlug, $select_name);
                            if (is_object($term) && isset($term->name)) {
        
                                $selected_value = $term->name;
        
                                echo "<option value='" . esc_attr($taxonomySlug) . "' selected='selected'>" . esc_html(
                                        $term->name
                                    ) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <?php
                    break;
    
                case 'author':
                    $authorId = isset($filters['author']) ? (int)$filters['author'] : 0;
                    $selectedOptionAll = empty($authorId) ? 'selected="selected"' : '';
                    $filter_label   = esc_html__('Author', 'publishpress');
                    ?>
                    <select id="filter_author" name="author" data-placeholder="<?php esc_attr_e('All authors', 'publishpress'); ?>">
                        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <option value="" <?php echo $selectedOptionAll; ?>>
                            <?php esc_html_e('All authors', 'publishpress'); ?>
                        </option>
                        <?php
                        if (! empty($authorId)) {
                            $author = get_user_by('id', $authorId);
                            $option = '';
    
                            if (! empty($author)) {
                                $selected_value = $author->display_name;
                                $option = '<option value="' . esc_attr($authorId) . '" selected="selected">' . esc_html(
                                        $author->display_name
                                    ) . '</option>';
                            }
    
                            $option = apply_filters('publishpress_author_filter_selected_option', $option, $authorId);
    
                            echo $option; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                        ?>
                    </select>
                    <?php
                    break;
    
                case 'post_type':
                case 'cpt':
                    $selectedPostType = isset($filters['cpt']) ? sanitize_text_field($filters['cpt']) : '';
                    $filter_label   = esc_html__('Post Type', 'publishpress');
                    ?>
                    <select id="post_type" name="cpt">
                        <option value=""><?php
                            _e('All post types', 'publishpress'); ?></option>
                        <?php
                        $postTypes = $this->get_selected_post_types();
                        foreach ($postTypes as $postType) {
                            $postTypeObject = get_post_type_object($postType);
                            if ($selectedPostType == $postType) {
                                $selected_value = $postTypeObject->label;
                            }
                            echo '<option value="' . esc_attr($postType) . '" ' . selected(
                                    $selectedPostType,
                                    $postType
                                ) . '>' . esc_html($postTypeObject->label) . '</option>';
                        }
                        ?>
                    </select>
                    <?php
                    break;
    
                    case 'search_box':
                        ?>
                        <input type="hidden" id="<?php echo esc_attr($select_id . '-search-input'); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_attr_e('Search box', 'publishpress'); ?>" />
                        <?php
                        break;
    
                case 'metadata_key':
                    $metadata_value = isset($filters[$select_name]) ? sanitize_text_field($filters[$select_name]) : '';
                    $metadata_term  = $this->terms_options[$select_name];
    
                    $metadata_type  = $metadata_term['type'];
                    $selected_value = $metadata_value;
                    $filter_label   = $metadata_term['name'];
                    
                    ?>
                    <div class="metadata-item-filter">
                        <div class="filter-title">
                            <?php echo esc_html($metadata_term['name']); ?>
                        </div>
                        <div class="filter-content">
                        <?php
                        if (in_array($metadata_type, ['paragraph', 'location', 'text', 'number'])) { 
                            ?>
                            <input 
                                type="text" 
                                id="<?php echo esc_attr('metadata_key_' . $select_name); ?>" 
                                name="<?php echo esc_attr($select_name); ?>" 
                                value="<?php echo esc_attr($metadata_value); ?>" 
                                placeholder=""
                                />
                            <div class="filter-apply">
                                <?php submit_button(esc_html__('Apply', 'publishpress'), 'button-primary', '', false, ['id' => 'filter-submit']); ?>
                            </div>
                        <?php
                        } elseif (in_array($metadata_type, ['select'])) {
                            ?>
                            <?php if (!empty($metadata_term['select_options']) && is_array($metadata_term['select_options']) && !empty($metadata_term['select_options']['values'])) : 
                                $option_values     = $metadata_term['select_options']['values'];
                                $option_labels     = $metadata_term['select_options']['labels'];
                                ?>
                            <select 
                                id="<?php echo esc_attr('metadata_key_' . $select_name); ?>" 
                                name="<?php echo esc_attr($select_name); ?>">
                                <option value="">
                                    <?php esc_html_e('Select option...', 'publishpress'); ?>
                                </option>
                                <?php
                                foreach ($option_values as $index => $value) {
                                    echo '<option value="' . esc_attr($value) . '" '. selected($metadata_value, $value, false) .'>' . esc_html($option_labels[$index]) . '</option>';
                                }
                                ?>
                            </select>
                            <?php else : ?>
                            <input 
                                type="text" 
                                id="<?php echo esc_attr('metadata_key_' . $select_name); ?>" 
                                name="<?php echo esc_attr($select_name); ?>" 
                                value="<?php echo esc_attr($metadata_value); ?>" 
                                placeholder=""
                                />
                            <?php endif; ?>
                        <?php
                        } elseif ($metadata_type === 'date') { ?>
                            <?php
                            $metadata_start_value           = isset($filters[$select_name . '_start']) ? sanitize_text_field($filters[$select_name . '_start']) : '';
                            $metadata_end_value             = isset($filters[$select_name . '_end']) ? sanitize_text_field($filters[$select_name . '_end']) : '';
    
                            $metadata_start_value_hidden    = isset($filters[$select_name . '_start_hidden']) ? sanitize_text_field($filters[$select_name . '_start_hidden']) : '';
                            $metadata_end_value_hidden      = isset($filters[$select_name . '_end_hidden']) ? sanitize_text_field($filters[$select_name . '_end_hidden']) : '';
    
                            $metadata_start_name            = $select_name . '_start';
                            $metadata_end_name              = $select_name . '_end';
    
                            $selected_value = '';
                            if (!empty($metadata_start_value)) {
                                $selected_value .= $metadata_start_value;
                            }
                            if (!empty($metadata_start_value) && !empty($metadata_end_value)) {
                                $selected_value .= ' ' . esc_html__('to', 'publishpress') . ' ';
                            }
                            if (!empty($metadata_end_value)) {
                                $selected_value .= $metadata_end_value;
                            }
                            ?>
                            <?php 
                            printf(
                                '<input
                                    type="text"
                                    id="%s"
                                    name="%1$s"
                                    value="%2$s"
                                    class="date-time-pick"
                                    data-alt-field="%1$s_hidden"
                                    data-alt-format="%3$s"
                                    placeholder="%4$s"
                                    autocomplete="off"
                                />',
                                esc_attr($metadata_start_name),
                                esc_attr($metadata_start_value),
                                esc_attr(pp_convert_date_format_to_jqueryui_datepicker('Y-m-d')),
                                ''
                            );
                            printf(
                                '<input
                                    type="hidden"
                                    name="%s_hidden"
                                    value="%s"
                                />',
                                esc_attr($metadata_start_name),
                                esc_attr($metadata_start_value_hidden)
                            ); 
                            ?>
                            <div class="input-divider"><?php echo esc_html__('to', 'publishpress'); ?></div>
                            <?php 
                            printf(
                                '<input
                                    type="text"
                                    id="%s"
                                    name="%1$s"
                                    value="%2$s"
                                    class="date-time-pick"
                                    data-alt-field="%1$s_hidden"
                                    data-alt-format="%3$s"
                                    placeholder="%4$s"
                                    autocomplete="off"
                                />',
                                esc_attr($metadata_end_name),
                                esc_attr($metadata_end_value),
                                esc_attr(pp_convert_date_format_to_jqueryui_datepicker('Y-m-d')),
                                ''
                            );
                            printf(
                                '<input
                                    type="hidden"
                                    name="%s_hidden"
                                    value="%s"
                                />',
                                esc_attr($metadata_end_name),
                                esc_attr($metadata_end_value_hidden)
                            ); 
                            ?>
                            <div class="filter-apply">
                                <?php submit_button(esc_html__('Apply', 'publishpress'), 'button-primary', '', false, ['id' => 'filter-submit']); ?>
                            </div>
                        <?php
                        } elseif ($metadata_type === 'user') { 
                            if (!empty($metadata_value)) {
                                $user_info = get_user_by('id', $metadata_value);
                                if (! empty($user_info)) {
                                    $selected_value = $user_info->display_name;
                                }
                            }
                            $user_dropdown_args = [
                                'show_option_all' => $metadata_term['name'],
                                'name' => $select_name,
                                'selected' => $metadata_value,
                                'class' => 'pp-custom-select2'
                            ];
                            $user_dropdown_args = apply_filters('pp_editorial_metadata_user_dropdown_args', $user_dropdown_args);
                                wp_dropdown_users($user_dropdown_args);
                        } elseif ($metadata_type === 'checkbox') { 
                            if ($metadata_value == '1') {
                                $selected_value = esc_html__('Checked', 'publishpress');
                            } else {
                                $selected_value = '';
                            }
                            ?>
                            <input 
                                type="hidden" 
                                name="<?php echo esc_attr($select_name); ?>" 
                                value="0"
                                />
                            <input 
                                type="checkbox" 
                                id="<?php echo esc_attr('metadata_key_' . $select_name); ?>" 
                                name="<?php echo esc_attr($select_name); ?>" 
                                value="1"
                                <?php checked($metadata_value, 1); ?>
                                />
                            <div class="filter-apply">
                                <?php submit_button(esc_html__('Apply', 'publishpress'), 'button-primary', '', false, ['id' => 'filter-submit']); ?>
                            </div>
                        <?php
                        }
                    echo '</div></div>';
                    break;
    
                default:
                    if (array_key_exists($select_name, $this->form_filter_list)) {
                        $selected_value_meta = isset($filters[$select_name]) ? sanitize_text_field($filters[$select_name]) : '';
                        $filter_label   = $this->filters[$select_name];
                        $selected_value = $selected_value_meta;
    
                        if (strpos($select_name, "ppch_co_checklist_") === 0) {
                            ?>
                            <select id="filter_<?php echo esc_attr($select_name); ?>" name="<?php echo esc_attr($select_name); ?>">
                                <option value=""><?php
                                    _e('All status', 'publishpress'); ?></option>
                                <?php
                                $all_options = [
                                    'passed' => __('Passed', 'publishpress'),
                                    'failed' => __('Failed', 'publishpress')
                                ];
                                foreach ($all_options as $option_key => $option_label) {
                                    if ($selected_value_meta == $option_key) {
                                        $selected_value = $option_label;
                                    }
                                    echo '<option value="' . esc_attr($option_key) . '" ' . selected(
                                            $selected_value_meta,
                                            $option_key
                                        ) . '>' . esc_html($option_label) . '</option>';
                                }
                                ?>
                            </select>
                            <?php
                        } else {
                            
                            $operator_value = isset($filters[$select_name . '_operator']) ? sanitize_text_field($filters[$select_name . '_operator']) : '';
    
                            if (empty($operator_value)) {
                                $operator_value = 'equals';
                            }
    
                            if (in_array($select_name, ['ppch_co_yoast_seo__yoast_wpseo_linkdex', 'ppch_co_yoast_seo__yoast_wpseo_content_score'])) {
                                $input_type = 'number';
                            } else {
                                $input_type = 'text';
                            }
    
                            ?>
                            <div class="metadata-item-filter custom-filter">
                                <div class="filter-title">
                                    <?php echo esc_html($filter_label); ?>
                                </div>
                                <div class="filter-content">
                                <select class="non-trigger-select" id="filter_<?php echo esc_attr($select_name); ?>_operator" name="<?php echo esc_attr($select_name); ?>_operator">
                                <?php
                                $all_options = $this->meta_query_operator_label();
                                foreach ($all_options as $option_key => $option_label) {
                                    if (
                                        ($operator_value == $option_key && !empty($selected_value))
                                        || ($operator_value == $option_key && $selected_value == '0')
                                        || ($operator_value == $option_key && 'not_exists' === $option_key)
                                    ) {
                                        $selected_value = $option_label . $selected_value;
                                    }
                                    echo '<option value="' . esc_attr($option_key) . '" ' . selected(
                                            $operator_value,
                                            $option_key
                                        ) . '>' . esc_html($option_label) . '</option>';
                                }
                                ?>
                                </select>
                                <input 
                                type="<?php echo esc_attr($input_type); ?>" 
                                id="<?php echo esc_attr('custom_metadata_key_' . $select_name); ?>" 
                                name="<?php echo esc_attr($select_name); ?>" 
                                value="<?php echo esc_attr($selected_value_meta); ?>" 
                                placeholder=""
                                />
                                <div class="filter-apply">
                                    <?php submit_button(esc_html__('Apply', 'publishpress'), 'button-primary', '', false, ['id' => 'filter-submit']); ?>
                                </div>
    
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        do_action('PP_Content_Calendar_filter_display', $select_id, $select_name, $filters);
                    }
                    break;
            }
            
            return ['selected_value' => $selected_value, 'filter_label' => $filter_label, 'html' => ob_get_clean()];
        }

        /**
         * Return calendar filters
         * @return string
         */
        public function get_calendar_filters() {
            ob_start();
            ?>
            <div class="pp-content-calendar-manage">
                <div class="left-items">
                        <?php
                            $modal_id = 0;
                            $me_mode = (int) $this->user_filters['me_mode'];
                            $active_me_mode = !empty($me_mode) ? 'active-filter' : '';
                        ?>
                    <div class="item action me-mode-action <?php echo esc_attr($active_me_mode); ?>"
                        data-label="<?php esc_html_e('Me Mode', 'publishpress'); ?>">
                        <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Me Mode', 'publishpress'); ?>
                    </div>
                    <?php $modal_id++; ?>
                    <div class="item action co-filter" data-target="#content_calendar_modal_<?php echo esc_attr($modal_id); ?>">
                        <span class="dashicons dashicons-filter"></span> <?php esc_html_e('Customize Filters', 'publishpress'); ?>
                    </div>
                    <div id="content_calendar_modal_<?php echo esc_attr($modal_id); ?>" class="customize-customize-item-modal content-calendar-modal" style="display: none;">
                        <div class="content-calendar-modal-content">
                            <span class="close">&times;</span>
                            <?php echo $this->content_calendar_customize_filter_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                </div>
                <div class="right-items">
                    <div class="item">
                        <div class="search-bar">
                            <input type="search" id="co-searchbox-search-input" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_attr_e('Search box', 'publishpress'); ?>" />
                            <?php submit_button(esc_html__('Search', 'publishpress'), '', '', false, ['id' => 'co-searchbox-search-submit']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
            <form method="GET" id="pp-content-filters" class="pp-content-filters">
                <input type="hidden" name="page" value="pp-content-calendar"/>
                <input type="hidden" name="me_mode" id="content_calendar_me_mode" value="<?php echo esc_attr($me_mode); ?>" />
                <div class="pp-content-calendar-filters">
                    <?php
                    $filter_weeks = isset($this->user_filters['weeks']) ? (int)$this->user_filters['weeks'] : self::DEFAULT_NUM_WEEKS;
                    $modal_id++;
                    ?>
                    <button data-target="#content_calendar_modal_<?php echo esc_attr($modal_id); ?>" class="co-filter active-filter"
                    data-label="<?php esc_html_e('Period', 'publishpress'); ?>">
                        <?php esc_html_e('Period', 'publishpress'); ?>: <?php echo sprintf(esc_html__('%1s weeks', 'publishpress'), $filter_weeks); ?>
                    </button>
                    <div id="content_calendar_modal_<?php echo esc_attr($modal_id); ?>" class="content-calendar-modal" style="display: none;">
                        <div class="content-calendar-modal-content">
                            <span class="close">&times;</span>
                            <div>
                                <select name="weeks" id="weeks" class="calendar-weeks-filter">
                                    <?php 
                                        for ($i = 1; $i <= 12; $i++) {
                                            $selected = ($i == $filter_weeks) ? ' selected' : '';
                                            $week_text = sprintf(_n('%d week', '%d weeks', $i, 'publishpress'), $i);
                                            echo '<option value="' . $i . '"' . $selected . '>' . $week_text . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php 
                    
                    foreach ($this->content_calendar_filters() as $select_id => $select_name) {
                        $modal_id++;
                        $filter_data = $this->content_calendar_filter_options($select_id, $select_name, $this->user_filters);
                        $active_class = !empty($filter_data['selected_value']) ? 'active-filter' : '';
                        $button_label = $filter_data['filter_label'];
                        $button_label .= !empty($filter_data['selected_value']) ? ': ' . $filter_data['selected_value'] : '';
                        ?>
                        <?php if (!empty($button_label)) : ?>
                            <button 
                                data-target="#content_calendar_modal_<?php echo esc_attr($modal_id); ?>"
                                data-label="<?php echo esc_attr($filter_data['filter_label']); ?>"
                                class="co-filter <?php echo esc_attr($active_class); ?> <?php echo esc_attr($select_id); ?> me-mode-status-<?php echo esc_attr($me_mode); ?>"><?php echo esc_html($button_label); ?></button>
                            <div id="content_calendar_modal_<?php echo esc_attr($modal_id); ?>" class="content-calendar-modal" style="display: none;">
                                <div class="content-calendar-modal-content">
                                    <span class="close">&times;</span>
                                    <div><?php echo $filter_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                </div>
                            </div>
                        <?php elseif (!empty($filter_data['html'])) : ?>
                            <?php echo $filter_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php endif; ?>
                        <?php
                    } 
                    ?>
                    <button class="clear-filter">
                        <span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Reset Filters', 'publishpress'); ?>
                        <input style="display: none;" type="submit" id="post-query-clear" value="<?php echo esc_attr(__('Reset', 'publishpress')); ?>" class="button-secondary button"/>
                    </button>
                </div>
            </form>
                
            <form method="POST" id="pp-content-filters-hidden">
                <input type="hidden" name="co_form_action" value="reset_filter"/>
                <input type="hidden" name="_nonce" value="<?php echo esc_attr(wp_create_nonce('content_calendar_filter_rest_nonce')); ?>"/>
            </form>
            <?php
            return ob_get_clean();
        }

        /**
         * Update content calendar form action
         *
         * @return void
         */
        public function update_content_calendar_form_action() {
            global $publishpress;
    
            if (!empty($_POST['co_form_action']) && !empty($_POST['_nonce']) && $_POST['co_form_action'] == 'filter_form' && wp_verify_nonce(sanitize_key($_POST['_nonce']), 'content_calendar_filter_form_nonce')) {
                // Content calendar filter form
                $content_calendar_filters = !empty($_POST['content_calendar_filters']) ? array_map('sanitize_text_field', $_POST['content_calendar_filters']) : [];
                $content_calendar_filters_order = !empty($_POST['content_calendar_filters_order']) ? array_map('sanitize_text_field', $_POST['content_calendar_filters_order']) : [];
                $content_calendar_custom_filters = !empty($_POST['content_calendar_custom_filters']) ? map_deep($_POST['content_calendar_custom_filters'], 'sanitize_text_field') : [];
    
                // make sure enabled filters are saved in organized order
                $content_calendar_filters = array_intersect($content_calendar_filters_order, $content_calendar_filters);
    
                $publishpress->update_module_option($this->module->name, 'content_calendar_filters', $content_calendar_filters);
                $publishpress->update_module_option($this->module->name, 'content_calendar_custom_filters', $content_calendar_custom_filters);
                
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo pp_planner_admin_notice(esc_html__('Filter updated successfully.', 'publishpress'));
            } elseif (!empty($_POST['co_form_action']) && !empty($_POST['_nonce']) && $_POST['co_form_action'] == 'reset_filter' && wp_verify_nonce(sanitize_key($_POST['_nonce']), 'content_calendar_filter_rest_nonce')) {
                // Content calendar filter reset
                $this->update_user_meta(get_current_user_id(), self::USERMETA_KEY_PREFIX . 'filters', []);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo pp_planner_admin_notice(esc_html__('Filter reset successfully.', 'publishpress'));
            }
        }

        /**
         * Get content calendar data that's required on the 
         * content calendar page
         *
         * @return array
         */
        public function get_content_calendar_datas() {
            global $wpdb;
    
            if (is_array($this->content_calendar_datas)) {
                return $this->content_calendar_datas;
            }
            
            $datas = [];
    
            // add all meta keys
            $datas['meta_keys'] = $wpdb->get_col("SELECT DISTINCT meta_key FROM $wpdb->postmeta WHERE 1=1 ORDER BY meta_key ASC");
    
            // add editorial fields
            if (class_exists('PP_Editorial_Metadata')) {
                $additional_terms = get_terms(
                    [
                        'taxonomy' => PP_Editorial_Metadata::metadata_taxonomy,
                        'orderby' => 'name',
                        'order' => 'asc',
                        'hide_empty' => 0,
                        'parent' => 0,
                        'fields' => 'all',
                    ]
                );
    
                $metadatas = [];
                foreach ($additional_terms as $term) {
                    if (! is_object($term) || $term->taxonomy !== PP_Editorial_Metadata::metadata_taxonomy) {
                        continue;
                    }
                    $metadatas[$term->slug] = $term->name;
    
                    $term_options = $this->get_unencoded_description($term->description);
                    $term_options['name'] = $term->name;
                    $term_options['slug'] = $term->slug;
                    $this->terms_options[$term->slug] = $term_options;
                }
    
                $datas['editorial_metadata'] = $metadatas;
            }
    
            // add taxononomies
            $taxonomies = $this->get_all_taxonomies();
            $all_taxonomies = [];
            foreach ($taxonomies as $taxonomy) {
                if (in_array($taxonomy->name, ['post_status', 'post_status_core_wp_pp', 'post_visibility_pp'])) {
                    continue;
                }
                $all_taxonomies[$taxonomy->name] = $taxonomy->label;// . ' (' . $taxonomy->name . ')';
            }
            $datas['taxonomies'] = $all_taxonomies;
    
            // Add content calendar filters content
            $content_calendar_filters = $this->module->options->content_calendar_filters;
            $content_calendar_custom_filters = $this->module->options->content_calendar_custom_filters;
    
            $datas['content_calendar_filters'] = is_array($content_calendar_filters) ? $content_calendar_filters : [
                'post_status' => esc_html__('Status', 'publishpress'),
                'author' => esc_html__('Author', 'publishpress'), 
                'cpt' => esc_html__('Post Type', 'publishpress')
            ];
            $datas['content_calendar_custom_filters'] = is_array($content_calendar_custom_filters) ? $content_calendar_custom_filters : [];
    
            /**
             * @param array $datas
             *
             * @return $datas
             */
            $datas = apply_filters('publishpress_content_calendar_datas', $datas);
    
            $this->content_calendar_datas = $datas;
    
            return $datas;
        }
    
        
        /**
         * Get content calendar form filters
         * 
         * @return array
         */
        public function get_content_calendar_form_filters() {
    
            if (!empty($this->form_filters)) {
                return $this->form_filters;
            }
    
            $content_calendar_datas   = $this->content_calendar_datas;
    
            $filters = [];
            // custom filters
            $filters['custom'] = [
                'title'     => esc_html__('Custom filters', 'publishpress'),
                'message'   => esc_html__('Click the "Add New" button to create new filters.', 'publishpress'),
                'filters'   => $content_calendar_datas['content_calendar_custom_filters']
            ];
    
            // default filters
            $filters['default'] = [
                'title'     => esc_html__('Inbuilt filters', 'publishpress'),
                'filters'   => [
                    'post_status' => esc_html__('Post Status', 'publishpress'),
                    'author' => esc_html__('Author', 'publishpress'),
                    'cpt' => esc_html__('Post Type', 'publishpress')
                ]
            ];
            
            // editorial fields filters
            if (isset($content_calendar_datas['editorial_metadata'])) {
                $filters['editorial_metadata'] = [
                    'title'     => esc_html__('Editorial Fields', 'publishpress'),
                    'message'   => esc_html__('You do not have any editorial fields enabled', 'publishpress'),
                    'filters'   => $content_calendar_datas['editorial_metadata']
                ];
            }
    
            $filters['taxonomies'] = [
                'title'     => esc_html__('Taxonomies', 'publishpress'),
                'message'   => esc_html__('You do not have any public taxonomies', 'publishpress'),
                'filters'   => $content_calendar_datas['taxonomies']
            ];
    
            /**
            * @param array $filters
            * @param array $content_calendar_datas
            *
            * @return $filters
            */
            $filters = apply_filters('publishpress_content_calendar_form_filters', $filters, $content_calendar_datas);
    
            $this->form_filters = $filters;
    
            return $filters;
        }
    
        
        public function content_calendar_customize_filter_form() {
            
            ob_start();
    
            $content_calendar_datas   = $this->content_calendar_datas;
            $enabled_filters          = array_keys($content_calendar_datas['content_calendar_filters']);
            $filters                  = $this->form_filters;
            $meta_keys                = $content_calendar_datas['meta_keys'];
    
            $all_filters              = [];
            ?>
            <form method="POST" class="pp-content-calendar-customize-form filters" id="pp-content-calendar-filter-form" data-form="filters">
                <input type="hidden" name="co_form_action" value="filter_form"/>
                <input type="hidden" name="_nonce" value="<?php echo esc_attr(wp_create_nonce('content_calendar_filter_form_nonce')); ?>"/>
                <div class="co-customize-tabs">
                    <div class="customize-tab enable-tab cc-active-tab" data-tab="enable-content"><?php esc_html_e('Enable Filters', 'publishpress'); ?></div>
                    <div class="customize-tab reorder-tab" data-tab="reorder-content"> <?php esc_html_e('Reorder Filters', 'publishpress'); ?> </div>
                </div>
                <div class="co-cc-content">
                    <div class="customize-content enable-content">
                        <div class="fixed-header">
                            <p class="description"><?php esc_html_e('Enable or Disable Content calendar filter.', 'publishpress'); ?></p>
                        </div>
                        <div class="scrollable-content">
                            <?php 
                            $filter_index = 0;
                            foreach ($filters as $filter_group => $filter_datas) : 
                            $filter_index++;
                            ?>
                                <div class="customize-group-title title-index-<?php echo esc_attr($filter_index); ?> <?php echo esc_attr($filter_group); ?>">
                                    <div class="title-text"><?php echo esc_html($filter_datas['title']); ?></div>
                                    <?php if ($filter_group === 'custom') : ?>
                                        <div class="title-action new-item">
                                            <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add New', 'publishpress'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($filter_group === 'custom') : ?>
                                    <div class="entry-item enable-item form-item" style="display: none;">
                                        <div class="new-fields">
                                            <div class="field">
                                                <input class="new-item-title" type="text" placeholder="<?php esc_attr_e('Filter Title', 'publishpress'); ?>" />
                                            </div>
                                            <div class="field">
                                            <select class="new-item-metakey">
                                                <option value=""><?php esc_html_e('Select Metakey', 'publishpress'); ?></option>
                                                <?php foreach ($meta_keys as $meta_key) : ?>
                                                    <option value="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($meta_key); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            </div>
                                        </div>
                                        <div class="new-submit">
                                            <?php esc_html_e('Add Filter', 'publishpress'); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($filter_datas['filters'])) : ?>
                                    <div class="item-group-empty <?php echo esc_attr($filter_group); ?>"><?php echo esc_html($filter_datas['message']); ?></div>
                                <?php else : ?>
                                    <?php foreach ($filter_datas['filters'] as $filter_name => $filter_label) : 
                                        $active_class = (in_array($filter_name, $enabled_filters)) ? 'active-item' : '';
                                        $input_name   = (in_array($filter_name, $enabled_filters)) ? 'content_calendar_filters['. $filter_name .']' : '';
    
                                        $all_filters[$filter_name] = [
                                            'filter_label' => $filter_label,
                                            'filter_group' => $filter_group
                                        ];
                                        ?>
                                        <div class="entry-item enable-item <?php echo esc_attr($active_class); ?> customize-item-<?php echo esc_attr($filter_name); ?> <?php echo esc_attr($filter_group); ?>" data-name="<?php echo esc_attr($filter_name); ?>">
                                            <input class="customize-item-input" type="hidden" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($filter_label); ?>" />
                                            <?php if ($filter_group === 'custom') : ?>
                                                <input type="hidden" name="content_calendar_custom_filters[<?php echo esc_attr($filter_name); ?>]" value="<?php echo esc_attr($filter_label); ?>" />
                                            <?php endif; ?>
                                            <div class="items-list-item-check checked">
                                                <svg><use xlink:href="<?php echo esc_url($this->module_url . 'lib/content-calendar-icon.svg#svg-sprite-cu2-check-2-fill'); ?>"></use></svg>
                                            </div>
                                            <div class="items-list-item-check unchecked">
                                                <svg><use xlink:href="<?php echo esc_url($this->module_url . 'lib/content-calendar-icon.svg#svg-sprite-x'); ?>"></use></svg>
                                            </div>
                                            <div class="items-list-item-name">
                                                <div class="items-list-item-name-text"><?php echo esc_html($filter_label); ?> <?php if ($filter_group === 'custom') : ?><span class="customize-item-info">(<?php echo esc_html($filter_name); ?>)</span><?php endif; ?></div>
                                            </div>
                                            <?php if ($filter_group === 'custom') : ?>
                                                <div class="delete-content-calendar-item" data-meta="<?php echo esc_html($filter_name); ?>">
                                                    <svg><use xlink:href="<?php echo esc_url($this->module_url . 'lib/content-calendar-icon.svg#svg-sprite-cu2-menu-trash'); ?>"></use></svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="customize-content reorder-content" style="display: none;">
                        <div class="fixed-header">
                            <p class="description"><?php esc_html_e('Drag to change enabled filters order.', 'publishpress'); ?></p>
                        </div>
                        <div class="scrollable-content">
                            <?php 
                            // loop enabled filter first so they can stay as ordered
                            $added_filters = [];
                            foreach ($enabled_filters as $enabled_filter) {
                                $filter_name    = $enabled_filter;
                                if (!isset($all_filters[$filter_name])) {
                                    continue;
                                }
                                $filter_details = $all_filters[$filter_name];
                                $filter_label = $filter_details['filter_label'];
                                $filter_group = $filter_details['filter_group'];
                                $active_class = (in_array($filter_name, $enabled_filters)) ? 'active-item' : '';
                                $input_name   = (in_array($filter_name, $enabled_filters)) ? '' : ''; ?>
                                <div class="entry-item reorder-item <?php echo esc_attr($active_class); ?> customize-item-<?php echo esc_attr($filter_name); ?>  <?php echo esc_attr($filter_group); ?>" data-name="<?php echo esc_attr($filter_name); ?>">
                                    <input class="customize-item-input" type="hidden" name="content_calendar_filters_order[<?php echo esc_attr($filter_name); ?>]" value="<?php echo esc_attr($filter_label); ?>" />
                                    <?php echo esc_html($filter_label); ?>
                                </div>
                                <?php
                                $added_filters[] = $filter_name;
                            }
                            foreach ($all_filters as $filter_name => $filter_details) :
                                if (!in_array($filter_name, $added_filters)) :
                                    $filter_label = $filter_details['filter_label'];
                                    $filter_group = $filter_details['filter_group'];
    
                                    $active_class = (in_array($filter_name, $enabled_filters)) ? 'active-item' : '';
                                    $input_name   = (in_array($filter_name, $enabled_filters)) ? '' : ''; ?>
                                    <div class="entry-item reorder-item <?php echo esc_attr($active_class); ?> customize-item-<?php echo esc_attr($filter_name); ?>  <?php echo esc_attr($filter_group); ?>" data-name="<?php echo esc_attr($filter_name); ?>">
                                        <input class="customize-item-input" type="hidden" name="content_calendar_filters_order[<?php echo esc_attr($filter_name); ?>]" value="<?php echo esc_attr($filter_label); ?>" />
                                        <?php echo esc_html($filter_label); ?>
                                    </div>
                                <?php $added_filters[] = $filter_name; 
                                endif;
                            endforeach; ?>
                        </div>
                    </div>
                    <div class="fixed-footer">
                        <div class="save-cc-changes save-customize-item-form">
                            <?php esc_html_e('Apply Changes', 'publishpress'); ?>
                        </div>
                    </div>
                </div>
            </form>
            <?php
            return ob_get_clean();
            
        }

        /**
         * Renders the admin page
         */
        public function render_admin_page()
        {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            global $publishpress;

            // update content calendar form action
            $this->update_content_calendar_form_action();

            // Get content calendar data
            $this->content_calendar_datas = $this->get_content_calendar_datas();
            
            $filters = $this->content_calendar_datas['content_calendar_filters'];
            /**
             * @param array $filters
             *
             * @return array
             */
            $this->filters = apply_filters('publishpress_content_calendar_filters', $filters);
    
            $this->form_filters = $this->get_content_calendar_form_filters();
            $this->form_filter_list = array_merge(...array_values(array_column($this->form_filters, 'filters')));

            // Get filters either from $_GET or from user settings
            $this->user_filters = $this->get_filters();

            // Total number of weeks to display on the calendar. Run it through a filter in case we want to override the
            // user's standard
            $this->total_weeks = empty($this->user_filters['weeks']) ? self::DEFAULT_NUM_WEEKS : $this->user_filters['weeks'];

            $this->start_date = empty($this->user_filters['start_date']) ? date('Y-m-d', current_time('timestamp')) :  $this->user_filters['start_date'];

            // Get the custom description for this page
            $description = '';

            // Should we display the subscribe button?
            if ('on' == $this->module->options->ics_subscription && $this->module->options->ics_secret_key) {
                // Prepare the download link
                $args = [
                    'action' => 'pp_calendar_ics_subscription',
                    'user' => wp_get_current_user()->user_login,
                    'user_key' => md5(wp_get_current_user()->user_login . $this->module->options->ics_secret_key),
                    '_wpnonce' => wp_create_nonce('publishpress_calendar_ics_sub'),
                ];

                // Prepare the subscribe link for the feed
                unset($args['action']);
                $args['post_status'] = 'unpublish';
                $args['pp_action'] = 'pp_calendar_ics_feed';
                $subscription_link = add_query_arg($args, site_url());

                $description .= '<div class="calendar-subscribe">';

                add_thickbox();

                ob_start(); ?>
                <div id="publishpress-calendar-ics-subs" style="display:none;">
                    <h3><?php
                        echo esc_html__('PublishPress', 'publishpress'); ?>
                        - <?php
                        echo esc_html__('Subscribe in iCal or Google Calendar', 'publishpress'); ?>
                    </h3>

                    <div>
                        <h4><?php
                            echo esc_html__('Start date', 'publishpress'); ?></h4>
                        <select id="publishpress-start-date">
                            <option value="0"
                                    selected="selected"><?php
                                echo esc_html__('Current week', 'publishpress'); ?></option>
                            <option value="1"><?php
                                echo esc_html__('One month ago', 'publishpress'); ?></option>
                            <option value="2"><?php
                                echo esc_html__('Two months ago', 'publishpress'); ?></option>
                            <option value="3"><?php
                                echo esc_html__('Three months ago', 'publishpress'); ?></option>
                            <option value="4"><?php
                                echo esc_html__('Four months ago', 'publishpress'); ?></option>
                            <option value="5"><?php
                                echo esc_html__('Five months ago', 'publishpress'); ?></option>
                            <option value="6"><?php
                                echo esc_html__('Six months ago', 'publishpress'); ?></option>
                        </select>

                        <br/>

                        <h4><?php
                            echo esc_html__('End date', 'publishpress'); ?></h4>
                        <select id="publishpress-end-date">
                            <optgroup label="<?php
                            echo esc_attr__('Weeks'); ?>">
                                <option value="w1"><?php
                                    echo esc_html__('One week', 'publishpress'); ?></option>
                                <option value="w2"><?php
                                    echo esc_html__('Two weeks', 'publishpress'); ?></option>
                                <option value="w3"><?php
                                    echo esc_html__('Three weeks', 'publishpress'); ?></option>
                                <option value="w4"><?php
                                    echo esc_html__('Four weeks', 'publishpress'); ?></option>
                            </optgroup>

                            <optgroup label="<?php
                            echo esc_attr__('Months'); ?>">
                                <option value="m1"><?php
                                    echo esc_html__('One month', 'publishpress'); ?></option>
                                <option value="m2"
                                        selected="selected"><?php
                                    echo esc_html__('Two months', 'publishpress'); ?></option>
                                <option value="m3"><?php
                                    echo esc_html__('Three months', 'publishpress'); ?></option>
                                <option value="m4"><?php
                                    echo esc_html__('Four months', 'publishpress'); ?></option>
                                <option value="m5"><?php
                                    echo esc_html__('Five months', 'publishpress'); ?></option>
                                <option value="m6"><?php
                                    echo esc_html__('Six months', 'publishpress'); ?></option>
                                <option value="m7"><?php
                                    echo esc_html__('Seven months', 'publishpress'); ?></option>
                                <option value="m8"><?php
                                    echo esc_html__('Eight months', 'publishpress'); ?></option>
                                <option value="m9"><?php
                                    echo esc_html__('Nine months', 'publishpress'); ?></option>
                                <option value="m10"><?php
                                    echo esc_html__('Ten months', 'publishpress'); ?></option>
                                <option value="m11"><?php
                                    echo esc_html__('Eleven months', 'publishpress'); ?></option>
                                <option value="m12"><?php
                                    echo esc_html__('Twelve months', 'publishpress'); ?></option>
                            </optgroup>
                        </select>
                    </div>

                    <br/>

                    <a href="<?php
                    echo esc_url($subscription_link); ?>" id="publishpress-ics-download"
                       style="margin-right: 20px;" class="button">
                        <span class="dashicons dashicons-download" style="text-decoration: none"></span>
                        <?php
                        echo esc_html__('Download .ics file', 'publishpress'); ?></a>

                    <button data-clipboard-text="<?php
                    echo esc_attr($subscription_link); ?>" id="publishpress-ics-copy"
                            class="button-primary">
                        <span class="dashicons dashicons-clipboard" style="text-decoration: none"></span>
                        <?php
                        echo esc_html__('Copy to the clipboard', 'publishpress'); ?>
                    </button>
                </div>

                <a href="#TB_inline?width=550&height=270&inlineId=publishpress-calendar-ics-subs" class="thickbox">
                    <?php
                    echo esc_html__('Click here to subscribe in iCal or Google Calendar', 'publishpress'); ?>
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
                        $ids = isset($_GET['ids']) ? sanitize_text_field($_GET['ids']) : 0;
                        $pid = explode(',', $ids);
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


                    if (isset($_GET['untrashed']) && (int)$_GET['untrashed']) {
                        printf(
                            _n(
                                'Post restored from the Trash.',
                                '%d posts restored from the Trash.',
                                (int)$_GET['untrashed']
                            ),
                            number_format_i18n((int)$_GET['untrashed'])
                        );
                        unset($_GET['undeleted']);
                    }
                    echo '</p></div>';
                } ?>

                <div class="publishpress-calendar-filter-bar">
                    <?php echo $this->get_calendar_filters(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>

                <div id="publishpress-calendar-wrap" class="publishpress-calendar-wrap">
                    <div class="publishpress-calendar-loader">
                        <div class="publishpress-calendar-loader-message">
                            <div class="sk-cube-grid">
                                <div class="sk-cube sk-cube1"></div>
                                <div class="sk-cube sk-cube2"></div>
                                <div class="sk-cube sk-cube3"></div>
                                <div class="sk-cube sk-cube4"></div>
                                <div class="sk-cube sk-cube5"></div>
                                <div class="sk-cube sk-cube6"></div>
                                <div class="sk-cube sk-cube7"></div>
                                <div class="sk-cube sk-cube8"></div>
                                <div class="sk-cube sk-cube9"></div>
                            </div>
                            <div><?php
                                echo __('Initializing the calendar. Please wait...', 'publishpress'); ?></div>
                        </div>
                        <div class="publishpress-calendar-loader-delayed-tip">
                            <?php
                            echo __(
                                'It seems like it is taking too long. Please, try reloading the page again and check the browser console looking for errors.',
                                'publishpress'
                            ); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="pp-content-calendar-general-modal" style="display: none;">
                <div id="pp-content-calendar-general-modal-container" class="pp-content-calendar-general-modal-container"></div>
            </div>
            <?php
           wp_localize_script(
                'publishpress-calendar-js',
                'PPContentCalendar',
                [
                    'nonce' => wp_create_nonce('publishpress-calendar-get-data'),
                    'moduleUrl' => $this->module_url,
                ]
            );

            $publishpress->settings->print_default_footer($publishpress->modules->calendar);
            // phpcs:enable
        }

        /**
         * Generates the HTML for a single post item in the calendar
         *
         * @param WP_Post $post The WordPress post in question
         * @param string $post_date The date of the post
         * @param int $num The index of the post
         *
         * @return str HTML for a single post item
         */
        public function generate_post_li_html($post, $post_date, $num = 0)
        {
            $can_modify = $this->current_user_can_modify_post($post);
            $cache_key = sprintf(
                '%s%s',
                $post->ID,
                $can_modify ? 'can_modify' : 'read_only'
            );
            $cache_val = wp_cache_get($cache_key, self::$post_li_html_cache_key);
            // Because $num is pertinent to the display of the post LI, need to make sure that's what's in cache
            if (is_array($cache_val) && $cache_val['num'] == $num) {
                $this->hidden = $cache_val['hidden'];

                return $cache_val['post_li_html'];
            }

            ob_start();

            $post_id = $post->ID;
            $edit_post_link = get_edit_post_link($post_id);

            $show_posts_publish_time = $this->showPostsPublishTime($post->post_status);

            $post_publish_date_time = '';
            if ($show_posts_publish_time) {
                $post_publish_datetime = get_the_date('c', $post);
                $post_publish_date_timestamp = get_post_time('U', false, $post);
                $posts_publish_time_format = isset($this->module->options->posts_publish_time_format) && ! empty(
                $this->module->options->posts_publish_time_format
                )
                    ? $this->module->options->posts_publish_time_format
                    : self::TIME_FORMAT_12H_NO_LEADING_ZEROES;
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
            $max_visible_posts_option = isset($this->module->options->max_visible_posts_per_date) && ! empty($this->default_max_visible_posts_per_date) ?
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
                    class="<?php
                    echo esc_attr(implode(' ', $post_classes)); ?>"
                    id="post-<?php
                    echo esc_attr($post->ID); ?>">

                <div style="clear:right;"></div>
                <div class="item-static">
                    <div class="item-default-visible" style="background:<?php
                    echo $post_type_options['color']; ?>;">
                        <div class="inner">
                            <span class="dashicons <?php
                            echo esc_attr($post_type_options['icon']); ?>"></span>
                            <?php
                            $title = esc_html(_draft_or_post_title($post->ID)); ?>

                            <span class="item-headline post-title" title="<?php
                            echo esc_attr($title); ?>">
                                <?php
                                if ($show_posts_publish_time): ?>
                                    <time
                                            class="item-headline-time"
                                            datetime="<?php
                                            echo esc_attr($post_publish_datetime); ?>"
                                            title="<?php
                                            echo esc_attr(
                                                date_i18n(
                                                    $this->default_date_time_format,
                                                    $post_publish_date_timestamp
                                                )
                                            ); ?>"
                                    >
                                    <?php
                                    echo esc_html(
                                        date_i18n($posts_publish_time_format, $post_publish_date_timestamp)
                                    ); ?>
                                </time>
                                <?php
                                endif; ?>
                                <strong><?php
                                    echo $title; ?></strong>
                            </span>
                        </div>
                    </div>
                    <div class="item-inner">
                        <?php
                        $this->get_inner_information($this->get_post_information_fields($post), $post); ?>
                    </div>
                    <?php
                    if (! $this->current_user_can_modify_post($post)) : ?>
                        <div class="item-inner read-only-notice"
                             title="<?php
                             esc_attr_e('You can\'t edit or move this post'); ?>">
                            <?php
                            esc_html_e('Read only'); ?>
                        </div>
                    <?php
                    endif; ?>
                </div>
            </li>
            <?php

            $post_li_html = ob_get_contents();
            ob_end_clean();

            $post_li_cache = [
                'num' => $num,
                'post_li_html' => $post_li_html,
                'hidden' => $this->hidden,
            ];
            wp_cache_set($cache_key, $post_li_cache, self::$post_li_html_cache_key);

            return $post_li_html;
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
                <?php
                foreach ($pp_calendar_item_information_fields as $field => $values) : ?>
                    <tr class="item-field item-information-<?php
                    echo esc_attr($field); ?>">
                        <th class="label"><?php
                            echo esc_html($values['label']); ?>:
                        </th>
                        <?php
                        if ($values['value'] && isset($values['type'])) : ?>
                            <?php
                            if (isset($values['editable']) && $this->current_user_can_modify_post($post)) : ?>
                                <td class="value<?php
                                if ($values['editable']) {
                                    ?> editable-value<?php
                                } ?>">
                                    <?php
                                    echo esc_html($values['value']); ?>

                                </td>
                                <?php
                                if ($values['editable']) : ?>
                                    <td class="editable-html hidden"
                                        data-type="<?php
                                        echo esc_attr($values['type']); ?>"
                                        data-metadataterm="<?php
                                        echo esc_attr(
                                            str_replace(
                                                'editorial-metadata-',
                                                '',
                                                str_replace('tax_', '', $field)
                                            )
                                        ); ?>">

                                        <?php
                                        echo $this->get_editable_html($values['type'], $values['value']); ?>
                                    </td>
                                <?php
                                endif; ?>
                            <?php
                            else : ?>
                                <td class="value"><?php
                                    echo esc_html($values['value']); ?></td>
                            <?php
                            endif; ?>
                        <?php
                        elseif ($values['value']) : ?>
                            <td class="value"><?php
                                echo esc_html($values['value']); ?></td>
                        <?php
                        else : ?>
                            <td class="value">
                                <em class="none"><?php
                                    echo esc_html_e('None', 'publishpress'); ?></em>
                            </td>
                        <?php
                        endif; ?>
                    </tr>
                <?php
                endforeach; ?>
                <?php
                do_action('pp_calendar_item_additional_html', $post->ID); ?>
            </table>
            <?php
            $post_type_object = get_post_type_object($post->post_type);
            $item_actions = [];
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
                if (! in_array($post->post_status, $this->published_statuses)) {
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
                $item_actions['save hidden'] = '<a href="#savemetadata" id="save-editorial-metadata" class="post-' . esc_attr(
                        $post->ID
                    ) . '" title="' . esc_attr(
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
                    return '<input type="text" class="metadata-edit-' . esc_attr($type) . '" value="' . esc_html(
                            $value
                        ) . '"/>';
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
                            'echo' => 0,
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
                'type' => 'author',
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
            $args = [
                'post_type' => $post->post_type,
            ];
            $taxonomies = get_object_taxonomies($args, 'object');
            foreach ((array)$taxonomies as $taxonomy) {
                // Sometimes taxonomies skip by, so let's make sure it has a label too
                if (! $taxonomy->public || ! $taxonomy->label) {
                    continue;
                }

                $terms = get_the_terms($post->ID, $taxonomy->name);
                if (! $terms || is_wp_error($terms)) {
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
                // Used when editing editorial fields and post meta
                if (is_taxonomy_hierarchical($taxonomy->name)) {
                    $type = 'taxonomy hierarchical';
                } else {
                    $type = 'taxonomy';
                }

                $information_fields[$key] = [
                    'label' => $taxonomy->label,
                    'value' => $value,
                    'type' => $type,
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
                        <input type="hidden" name="start_date" value="<?php
                        echo esc_attr($filters['start_date']); ?>"/>
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
                        <input type="hidden" name="start_date" value="<?php
                        echo esc_attr($filters['start_date']); ?>"/>
                        <?php
                        foreach ($this->calendar_filters() as $select_id => $select_name) {
                            echo '<input type="hidden" name="' . esc_attr($select_name) . '" value="" />';
                        } ?>
                        <input type="submit" id="post-query-clear" class="button-secondary button"
                               value="<?php
                               esc_html_e('Reset', 'publishpress'); ?>"/>
                    </form>
                </li>

                <?php
                /** Previous and next navigation items (translatable so they can be increased if needed)**/ ?>
                <li class="date-change next-week">
                    <a title="<?php
                    esc_attr(sprintf(__('Forward 1 week', 'publishpress'))); ?>"
                       href="<?php
                       echo esc_url(
                           $this->get_pagination_link(
                               'next',
                               $filters,
                               1
                           )
                       ); ?>"><?php
                        esc_html_e('&rsaquo;', 'publishpress'); ?></a>
                    <?php
                    if ($this->total_weeks > 1) : ?>
                        <a title="<?php
                        esc_attr(
                            printf(
                                __('Forward %d weeks', 'publishpress'),
                                $this->total_weeks
                            )
                        ); ?>"
                           href="<?php
                           echo esc_url(
                               $this->get_pagination_link(
                                   'next',
                                   $filters
                               )
                           ); ?>"><?php
                            esc_html_e('&raquo;', 'publishpress'); ?></a>
                    <?php
                    endif; ?>
                </li>
                <li class="date-change today">
                    <a title="<?php
                    esc_attr(
                        printf(
                            __('Today is %s', 'publishpress'),
                            date(get_option('date_format'), current_time('timestamp'))
                        )
                    ); ?>"
                       href="<?php
                       echo esc_url($this->get_pagination_link('next', $filters, 0)); ?>"><?php
                        esc_html_e(
                            'Today',
                            'publishpress'
                        ); ?></a>
                </li>
                <li class="date-change previous-week">
                    <?php
                    if ($this->total_weeks > 1) : ?>
                        <a title="<?php
                        esc_attr(sprintf(__('Back %d weeks', 'publishpress'), $this->total_weeks)); ?>"
                           href="<?php
                           echo esc_url(
                               $this->get_pagination_link(
                                   'previous',
                                   $filters
                               )
                           ); ?>"><?php
                            esc_html_e('&laquo;', 'publishpress'); ?></a>
                    <?php
                    endif; ?>
                    <a title="<?php
                    esc_attr(sprintf(__('Back 1 week', 'publishpress'))); ?>"
                       href="<?php
                       echo esc_url(
                           $this->get_pagination_link(
                               'previous',
                               $filters,
                               1
                           )
                       ); ?>"><?php
                        esc_html_e('&lsaquo;', 'publishpress'); ?></a>
                </li>
                <li class="ajax-actions">
                    <img class="waiting" style="display:none;"
                         src="<?php
                         echo esc_url(admin_url('images/wpspin_light.gif')); ?>" alt=""/>
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
         * @param string $context Where the query is coming from, to distinguish dashboard and subscriptions
         *
         * @return array $posts All of the posts as an array sorted by date
         */
        public function getCalendarDataForMultipleWeeks($args = [], $context = 'dashboard')
        {
            $supported_post_types = $this->get_post_types_for_module($this->module);
            $defaults = [
                'post_status' => null,
                'author' => null,
                'post_type' => $supported_post_types,
                'posts_per_page' => -1,
                'order' => 'ASC',
            ];

            $args = array_merge($defaults, $args);

            if (isset($args['s']) && ! empty($args['s'])) {
                $args['s'] = sanitize_text_field($args['s']);
            }

            $current_user_id = get_current_user_id();

            $this->user_filters = $this->get_filters();

            // Get content calendar data
            $this->content_calendar_datas = $this->get_content_calendar_datas();
            
            $filters = $this->content_calendar_datas['content_calendar_filters'];
            /**
             * @param array $filters
             *
             * @return array
             */
            $this->filters = apply_filters('publishpress_content_calendar_filters', $filters);

            $enabled_filters = array_keys($this->filters);
            $editorial_metadata = $this->terms_options;

            if (!empty($args['cpt'])) {
                $args['post_type'] = $args['cpt'];
            }
            

            if (empty($args['post_type']) || ! in_array($args['post_type'], $supported_post_types)) {
                $args['post_type'] = $supported_post_types;
            }

            //remove inactive builtin filter
            if (!in_array('cpt', $enabled_filters)) {
                // show all post type
                $args['post_type'] = $supported_post_types;
            }
    
            if (!in_array('author', $enabled_filters)) {
                unset($args['author']);
            }

            $meta_query = $tax_query = ['relation' => 'AND'];
            $metadata_filter = $taxonomy_filter = false;
            $checklists_filters = [];
            
            // apply enabled filter
            foreach ($enabled_filters as $enabled_filter) {
                if (array_key_exists($enabled_filter, $editorial_metadata)) {
                    //metadata field filter
                    $meta_key = $enabled_filter;
                    $metadata_term = $editorial_metadata[$meta_key];
                    unset($args[$enabled_filter]);
                    if ($metadata_term['type'] === 'date') {
                        $date_type_metaquery = [];
                        
                        if (! empty($this->user_filters[$meta_key . '_start'])) {
                            $date_type_metaquery[] = strtotime($this->user_filters[$meta_key . '_start_hidden']);
                        }
                        if (! empty($this->user_filters[$meta_key . '_end'])) {
                            $date_type_metaquery[] = strtotime($this->user_filters[$meta_key . '_end_hidden']);
                        }
                        if (count($date_type_metaquery) === 2) {
                            $metadata_filter = true;
                            $compare        = 'BETWEEN';
                            $meta_value     = $date_type_metaquery;
                        } elseif (count($date_type_metaquery) === 1) {
                            $metadata_filter = true;
                            $compare        = '=';
                            $meta_value     = $date_type_metaquery[0];
                        }
                        
                        if (!empty($date_type_metaquery)) {
                            $metadata_filter = true;
                            $meta_query[] = array(
                                'key' => '_pp_editorial_meta_' . $metadata_term['type'] . '_' . $metadata_term['slug'],
                                'value' => $meta_value,
                                'compare' => $compare
                            );
                        }
                    
                    } elseif (! empty($this->user_filters[$meta_key])) {
                        if ($metadata_term['type'] === 'date') {
                            continue;
                        } else {
                            $meta_value = sanitize_text_field($this->user_filters[$meta_key]);
                        }
        
                         $compare = '=';
                        if ($metadata_term['type'] === 'paragraph'
                            || ($metadata_term['type'] === 'select' && isset($metadata_term->select_type) && $metadata_term['select_type'] === 'multiple')
                        ) {
                            $compare = 'LIKE';
                        }
                        $metadata_filter = true;
                        $meta_query[] = array(
                            'key' => '_pp_editorial_meta_' . $metadata_term['type'] . '_' . $metadata_term['slug'],
                            'value' => $meta_value,
                            'compare' => $compare
                        );
                    }
    
                } elseif(
                    in_array($enabled_filter, $this->content_calendar_datas['meta_keys']) 
                    && (
                        isset($this->user_filters[$enabled_filter])
                        && 
                            (
                                !empty($this->user_filters[$enabled_filter]) 
                                || $this->user_filters[$enabled_filter] == '0'
                                || (
                                    !empty($this->user_filters[$enabled_filter . '_operator'])
                                    && $this->user_filters[$enabled_filter . '_operator'] === 'not_exists'
                                    )
                            )
                        )
                    ) {
                    // metakey filter
                    unset($args[$enabled_filter]);
                    $meta_value = sanitize_text_field($this->user_filters[$enabled_filter]);
                    $meta_operator = !empty($this->user_filters[$enabled_filter . '_operator']) ? $this->user_filters[$enabled_filter . '_operator'] : 'equals';
                    $compare = $this->meta_query_operator_symbol($meta_operator);
                    
                    $metadata_filter = true;
    
                    if ($meta_operator == 'not_exists') {
                        $meta_query[] = array(
                            'relation' => 'OR',
                            array(
                                'key' => $enabled_filter,
                                'compare' => 'NOT EXISTS'
                            ),
                            array(
                                'key' => $enabled_filter,
                                'value' => '',
                                'compare' => '='
                            )
                        );
                    } else {
                        $meta_query[] = array(
                            'key' => $enabled_filter,
                            'value' => $meta_value,
                            'compare' => $compare
                        );
                    }
                } elseif (in_array($enabled_filter, ['ppch_co_yoast_seo__yoast_wpseo_linkdex', 'ppch_co_yoast_seo__yoast_wpseo_content_score']) && !empty($this->user_filters[$enabled_filter]) && array_key_exists($enabled_filter, $this->form_filter_list) && class_exists('WPSEO_Meta')) {
                    // yoast seo filter
                    unset($args[$enabled_filter]);
                    $meta_value = sanitize_text_field($this->user_filters[$enabled_filter]);
                    $meta_key = str_replace('ppch_co_yoast_seo_', '', $enabled_filter);
                    $meta_operator = !empty($this->user_filters[$enabled_filter . '_operator']) ? $this->user_filters[$enabled_filter . '_operator'] : 'equals';
                    $compare = $this->meta_query_operator_symbol($meta_operator);
                    $metadata_filter = true;
                    $meta_query[] = array(
                        'key' => $meta_key,
                        'value' => $meta_value,
                        'compare' => $compare
                    );
                    
                } elseif(array_key_exists($enabled_filter, $this->content_calendar_datas['taxonomies']) && !empty($this->user_filters[$enabled_filter])) {
                    //taxonomy filter
                    unset($args[$enabled_filter]);
                    $tax_value = sanitize_text_field($this->user_filters[$enabled_filter]);
                    $taxonomy_filter = true;
                    $tax_query[] = array(
                          'taxonomy' => $enabled_filter,
                          'field'     => 'slug',
                          'terms'    => [$tax_value],
                          'include_children' => true,
                          'operator' => 'IN',
                    );
                } elseif(!empty($this->user_filters[$enabled_filter]) && strpos($enabled_filter, "ppch_co_checklist_") === 0 && array_key_exists($enabled_filter, $this->form_filter_list)) {
                    // checklists filter
                    /**
                     * TODO: Implement metaquery filter when checklists started storing checklists status in meta_key
                     */
                    unset($args[$enabled_filter]);
                    $meta_value = sanitize_text_field($this->user_filters[$enabled_filter]);
                    $meta_key = str_replace('ppch_co_checklist_', '', $enabled_filter);
                    $checklists_filters[$meta_key] = $meta_value;
                }
    
            }
            
            if ($metadata_filter) {
                $args['meta_query'] = $meta_query;
            }
    
            if ($taxonomy_filter) {
                $args['tax_query'] = $tax_query;
            }
            
            // Unpublished as a status is just an array of everything but 'publish'
            if ($args['post_status'] == 'unpublish') {
                $args['post_status'] = '';
                $post_statuses = $this->get_post_statuses();
                foreach ($post_statuses as $post_status) {
                    $args['post_status'] .= $post_status->slug . ', ';
                }
                $args['post_status'] = rtrim($args['post_status'], ', ');
                // Optional filter to include scheduled content as unpublished
                if (apply_filters('pp_show_scheduled_as_unpublished', true)) {
                    $args['post_status'] .= ', future';
                }
            }
            // unset legacy options
            if (isset($args['cat'])) {
                unset($args['cat']);
            }
            if (isset($args['tag'])) {
                unset($args['tag']);
            }

            if (!empty($args['me_mode']) && !empty($current_user_id)) {
                $args['author'] = $current_user_id;
            }

            // Filter by post_author if it's set
            if (isset($args['author']) && empty($args['author'])) {
                unset($args['author']);
            }

            // Filter for an end user to implement any of their own query args
            $args = apply_filters('pp_calendar_posts_query_args', $args, $context);

            if (isset($this->module->options->sort_by)) {
                add_filter('posts_orderby', [$this, 'filterPostsOrderBy'], 10);
            }

            $post_results = new WP_Query($args);

            $posts = [];
            while ($post_results->have_posts()) {
                $post_results->the_post();
                global $post;

                $add_post = true;

                if (!empty($checklists_filters)) {
                    $post_checklists = apply_filters('publishpress_checklists_requirement_list', [], $post);
                    foreach ($checklists_filters as $checklists_filter_name => $checklists_filter_check) {
                        if (!array_key_exists($checklists_filter_name, $post_checklists)) {
                            // post that doesn't have this requirement shouldn't show?
                            $add_post = false;
                        } elseif ($checklists_filter_check == 'passed' && empty($post_checklists[$checklists_filter_name]['status'])) {
                            // filter posts that failed when condition is passed
                            $add_post = false;
                        } elseif ($checklists_filter_check == 'failed' && !empty($post_checklists[$checklists_filter_name]['status'])) {
                            // filter out post that passed when condition is failed
                            $add_post = false;
                        }
                    }
                }
    
                if ($add_post) {
                    /**
                     * TODO: Should we require posts like x2 if results is empty due to $add_post been false for all?
                    */
                    $key_date = date('Y-m-d', strtotime($post->post_date));
                    $posts[$key_date][] = $post;
                }
            }

            if (isset($this->module->options->sort_by)) {
                remove_filter('posts_orderby', [$this, 'filterPostsOrderBy']);
            }

            return $posts;
        }

        public function filterPostsOrderBy($orderBy)
        {
            if ($this->module->options->sort_by === 'status') {
                $orderBy = 'post_status ASC, post_date ASC';
            } else {
                $orderBy = 'post_date ASC';
            }

            return $orderBy;
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
            $supported_post_types = $this->get_post_types_for_module($this->module);
            $defaults = [
                'post_status' => null,
                'cat' => null,
                'tag' => null,
                'author' => null,
                'post_type' => $supported_post_types,
                'posts_per_page' => -1,
                'order' => 'ASC',
            ];

            $args = array_merge($defaults, $args);

            // Unpublished as a status is just an array of everything but 'publish'
            if ($args['post_status'] == 'unpublish' || $context == 'ics_subscription') {
                $args['post_status'] = '';
                $post_statuses = $this->get_post_statuses();
                foreach ($post_statuses as $post_status) {
                    $args['post_status'] .= $post_status->slug . ', ';
                }
                $args['post_status'] = rtrim($args['post_status'], ', ');
                // Optional filter to include scheduled content as unpublished
                if (apply_filters('pp_show_scheduled_as_unpublished', true)) {
                    $args['post_status'] .= ', future';
                }
                if ($context == 'ics_subscription') {
                    $args['post_status'] .= ', publish';
                }

                if (isset($this->module->options->ics_subscription_public_visibility) && 'on' === $this->module->options->ics_subscription_public_visibility) {
                    $args['suppress_filters'] = true;
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

            if (empty($args['post_type']) || ! in_array($args['post_type'], $supported_post_types)) {
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
                $key_date = date('Y-m-d', strtotime($post->post_date));
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
            $ending_date = $this->get_ending_of_week($this->start_date, 'Y-m-d', $this->current_week);
            // Adjust the ending date to account for the entire day of the last day of the week
            $ending_date = date('Y-m-d', strtotime('+1 day', strtotime($ending_date)));
            $where = $where . $wpdb->prepare(
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

            if (! isset($weeks_offset)) {
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
            $url = add_query_arg($filters, menu_page_url('pp-calendar', false));

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
            $date = strtotime($date);
            $start_of_week = (int)get_option('start_of_week');
            $day_of_week = date('w', $date);
            $date += (($start_of_week - $day_of_week - 7) % 7) * 60 * 60 * 24 * $week;
            $additional = 3600 * 24 * 7 * ($week - 1);
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
            $date = strtotime($date);
            $end_of_week = (int)get_option('start_of_week') - 1;
            $day_of_week = date('w', $date);
            $date += (($end_of_week - $day_of_week + 7) % 7) * 60 * 60 * 24;
            $additional = 3600 * 24 * 7 * ($week - 1);
            $formatted_end_of_week = date($format, $date + $additional);

            return $formatted_end_of_week;
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
            if (! $post) {
                return false;
            }

            $post_type_object = get_post_type_object($post->post_type);

            // Is the current user an author of the post?
            $userId = (int)wp_get_current_user()->ID;
            $isAuthor = apply_filters(
                'publishpress_is_author_of_post',
                $userId === (int)$post->post_author,
                $userId,
                $post->ID
            );
            $isPublished = in_array($post->post_status, $this->published_statuses);
            $canPublish = current_user_can($post_type_object->cap->publish_posts, $post->ID);
            $passedPublishedPostRule = (! $isPublished || ($isPublished && $canPublish));

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
                __('Enable subscriptions in iCal or Google Calendar', 'publishpress'),
                [$this, 'settings_ics_subscription_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'ics_subscription_public_visibility',
                __('Allow public access to subscriptions in iCal or Google Calendar', 'publishpress'),
                [$this, 'settings_ics_subscription_public_visibility_option'],
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

            add_settings_field(
                'show_calendar_posts_full_title',
                __('Always show complete post titles', 'publishpress'),
                [$this, 'settings_show_calendar_posts_full_title_option'],
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
                'on' => __('Enabled', 'publishpress'),
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
         * Enable calendar subscriptions via .ics in iCal or Google Calendar
         *
         * @since 0.8
         */
        public function settings_ics_subscription_public_visibility_option()
        {


            echo '<div class="c-input-group">';

            echo sprintf(
                '<input type="checkbox" name="%s" value="on" %s>',
                esc_attr($this->module->options_group_name) . '[ics_subscription_public_visibility]',
                'on' === $this->module->options->ics_subscription_public_visibility ? 'checked' : ''
            );

            echo '</div>';
        }

        /**
         * Option that define either Posts publish times are displayed or not.
         *
         * @since 1.20.0
         */
        public function settings_show_posts_publish_time_option()
        {
            global $publishpress;

            $field_name = esc_attr($this->module->options_group_name) . '[show_posts_publish_time]';

            $customStatuses = $publishpress->getCustomStatuses();

            if (empty($customStatuses)) {
                $statuses = [
                    'publish' => __('Publish'),
                    'future' => __('Scheduled'),
                ];
            } else {
                $statuses = [];

                foreach ($customStatuses as $status) {
                    $statuses[$status->slug] = ['title' => $status->label, 'status_obj' => $status];
                }
            }

            // Add support to the legacy value for this setting, where "on" means post and page selected.
            if ($this->module->options->show_posts_publish_time === 'on') {
                $this->module->options->show_posts_publish_time = [
                    'publish' => 'on',
                    'future' => 'on',
                ];
            }

            if (empty($customStatuses)) {
                foreach ($statuses as $status => $title) {
                    $id = esc_attr($status) . '-display-publish-time';

                    echo '<div><label for="' . $id . '">';
                    echo '<input id="' . $id . '" name="' . $field_name . '[' . esc_attr($status) . ']"';

                    if (isset($this->module->options->show_posts_publish_time[$status])) {
                        checked($this->module->options->show_posts_publish_time[$status], 'on');
                    }

                    // Defining post_type_supports in the functions.php file or similar should disable the checkbox
                    disabled(post_type_supports($status, $this->module->post_type_support), true);

                    echo ' type="checkbox" value="on" />&nbsp;'
                    . esc_html($title)
                    . '</span>'
                    . '</label>';

                    echo '</div>';
                }
            } else {
                echo '<style>div.pp-calendar-settings div {padding: 4px 0 8px 0;} div.pp-calendar-settings a {vertical-align: bottom}</style>';

                echo '<div class="pp-calendar-settings">';

                foreach ($statuses as $status => $arr_status) {
                    $id = esc_attr($status) . '-display-publish-time';

                    echo '<div><label for="' . $id . '">';
                    echo '<input id="' . $id . '" name="' . $field_name . '[' . esc_attr($status) . ']"';

                    if (isset($this->module->options->show_posts_publish_time[$status])) {
                        checked($this->module->options->show_posts_publish_time[$status], 'on');
                    }

                    // Defining post_type_supports in the functions.php file or similar should disable the checkbox
                    disabled(post_type_supports($status, $this->module->post_type_support), true);

                    echo ' type="checkbox" value="on" />&nbsp;';

                    echo '<span class="dashicons ' . esc_html($arr_status['status_obj']->icon) . '"></span>&nbsp;';

                    $style = 'background:' . $arr_status['status_obj']->color . '; color:white';

                    echo '<span class="pp-status-color pp-status-color-title" style="' . esc_attr($style) . '">'
                    . esc_html($arr_status['title'])
                    . '</span>'
                    . '</label>';

                    if (class_exists('PublishPress_Statuses')) {
                        $_args = [
                            'action' => 'edit-status',
                            'return_module' => 'pp-calendar-settings',
                        ];

                        $_args['name'] = $arr_status['status_obj']->name;

                        $item_edit_link = esc_url(
                            PublishPress_Statuses::get_link(
                                $_args
                            )
                        );

                        echo ' <a href="' . $item_edit_link . '">' . __('edit') . '</a>';
                    }

                    echo '</div>';
                }

                echo '</div>';
            }
        }

        private function getCalendarTimeFormat()
        {
            return ! isset($this->module->options->posts_publish_time_format) || is_null(
                $this->module->options->posts_publish_time_format
            )
                ? self::TIME_FORMAT_12H_NO_LEADING_ZEROES
                : $this->module->options->posts_publish_time_format;
        }

        /**
         * Define the time format for Posts publish date.
         *
         * @since 1.20.0
         */
        public function settings_posts_publish_time_format_option()
        {
            $timeFormats = [
                self::TIME_FORMAT_12H_NO_LEADING_ZEROES => '1-12 am/pm',
                self::TIME_FORMAT_12H_WITH_LEADING_ZEROES => '01-12 am/pm',
                self::TIME_FORMAT_24H => '00-23',
            ];

            $posts_publish_time_format = $this->getCalendarTimeFormat();

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
                '<input type="text" name="%s" value="%s" class="time-pick" readonly>',
                esc_attr($this->module->options_group_name) . '[default_publish_time]',
                $this->module->options->default_publish_time
            );

            echo '</div>';
        }

        public function settings_show_calendar_posts_full_title_option()
        {
            echo '<div class="c-input-group">';

            echo sprintf(
                '<input type="checkbox" name="%s" value="on" %s>',
                esc_attr($this->module->options_group_name) . '[show_calendar_posts_full_title]',
                'on' === $this->module->options->show_calendar_posts_full_title ? 'checked' : ''
            );

            echo '</div>';
        }

        public function settings_sort_by_option()
        {
            $fields = [
                'time' => __('Publishing Time', 'publishpress'),
                'status' => __('Post Status', 'publishpress'),
            ];

            $sortByOptionValue = ! isset($this->module->options->sort_by) || is_null(
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
            $maxVisiblePostsPerDate = ! isset($this->module->options->max_visible_posts_per_date) || is_null(
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
                        'future' => 'on',
                    ];
                } else {
                    $options['show_posts_publish_time'] = $new_options['show_posts_publish_time'];
                }
            } else {
                $options['show_posts_publish_time'] = [];
            }

            $options['posts_publish_time_format'] = isset($new_options['posts_publish_time_format'])
                ? $new_options['posts_publish_time_format']
                : self::TIME_FORMAT_12H_NO_LEADING_ZEROES;

            if (isset($new_options['show_calendar_posts_full_title'])) {
                $options['show_calendar_posts_full_title'] = 'on';
            } else {
                $options['show_calendar_posts_full_title'] = 'off';
            }

            if (isset($new_options['ics_subscription_public_visibility'])) {
                $options['ics_subscription_public_visibility'] = 'on';
            } else {
                $options['ics_subscription_public_visibility'] = 'off';
            }

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
                  action="<?php
                  echo esc_url(menu_page_url($this->module->settings_slug, false)); ?>" method="post">
                <?php
                settings_fields($this->module->options_group_name); ?>
                <?php
                do_settings_sections($this->module->options_group_name); ?>
                <?php
                echo '<input id="publishpress_module_name" name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
                        $this->module->name
                    ) . '" />'; ?>
                <p class="submit"><?php
                    submit_button(null, 'primary', 'submit', false); ?></p>
                <?php
                echo '<input name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
                        $this->module->name
                    ) . '" />'; ?>
                <?php
                wp_nonce_field('edit-publishpress-settings'); ?>
            </form>
            <?php
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
         * Allow altering modified date when creating posts. WordPress by default
         * doesn't allow that. We need it to fix an issue where the post_modified
         * field is saved with the post_date value. That is a problem when you save
         * a post with post_date to the future. For scheduled posts.
         *
         * @param array $data
         * @param array $postarr
         *
         * @return array
         */
        public function alter_post_modification_time($data, $postarr)
        {
            if (! empty($postarr['post_modified']) && ! empty($postarr['post_modified_gmt'])) {
                $data['post_modified'] = $postarr['post_modified'];
                $data['post_modified_gmt'] = $postarr['post_modified_gmt'];
            }

            return $data;
        }

        public function calendar_filters()
        {
            $select_filter_names = [];

            $select_filter_names['post_status'] = 'post_status';
            $select_filter_names['cat'] = 'cat';
            $select_filter_names['tag'] = 'tag';
            $select_filter_names['author'] = 'author';
            $select_filter_names['type'] = 'cpt';
            $select_filter_names['weeks'] = 'weeks';

            return apply_filters('pp_calendar_filter_names', $select_filter_names);
        }

        public function searchAuthors()
        {
            header('Content-type: application/json;');

            if (! wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'publishpress-calendar-get-data')) {
                wp_send_json([]);
            }

            $queryText = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
            $user_roles = (isset($_GET['user_role']) && is_array($_GET['user_role'])) ? array_map('sanitize_text_field', $_GET['user_role']) : [];

            if (empty($user_roles)) {
                /**
                 * @param array $results
                 * @param string $searchText
                 */
                $results = apply_filters('publishpress_search_authors_results_pre_search', [], $queryText);
            } else {
                /**
                 * @param array $results
                 * @param array $args
                 */
                $results = apply_filters(
                    'publishpress_search_authors_with_args_results_pre_search',
                    [],
                    ['search' => $queryText, 'role__in' => $user_roles]
                );
            }

            if (! empty($results)) {
                wp_send_json($results);
            }

            $user_args = [
                'number' => 20,
                'orderby' => 'display_name',
            ];

            if (!empty($user_roles)) {
                $user_args['role__in'] = $user_roles;
            } else {
                $user_args['capability'] = 'edit_posts';
            }

            if (! empty($queryText)) {
                $user_args['search'] = '*' . $queryText . '*';
            }

            $users = get_users($user_args);

            foreach ($users as $user) {
                $results[] = [
                    'id' => $user->ID,
                    'text' => $user->display_name,
                ];
            }

            wp_send_json($results);
        }

        public function searchTerms()
        {
            header('Content-type: application/json;');

            if (! wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'publishpress-calendar-get-data')) {
                wp_send_json([]);
            }

            $queryText = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
            $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field($_GET['taxonomy']) : '';
            global $wpdb;

            $queryResult = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT t.slug AS id, t.name AS text
                FROM {$wpdb->term_taxonomy} as tt
                INNER JOIN {$wpdb->terms} as t ON (tt.term_id = t.term_id)
                WHERE tt.taxonomy = %s AND t.name LIKE %s
                ORDER BY 2
                LIMIT 20",
                    $taxonomy,
                    '%' . $wpdb->esc_like($queryText) . '%'
                )
            );

            $queryResult = map_deep($queryResult, 'html_entity_decode');

            wp_send_json($queryResult);
        }

        private function getPostTypeObject($postType)
        {
            if (! isset($this->postTypeObjectCache[$postType])) {
                $this->postTypeObjectCache[$postType] = get_post_type_object($postType);
            }

            return $this->postTypeObjectCache[$postType];
        }

        private function extractPostDataForTheCalendar($post)
        {
            $postTypeOptions = $this->get_post_status_options($post->post_status);
            $postTypeObject = $this->getPostTypeObject($post->post_type);
            $canEdit = current_user_can($postTypeObject->cap->edit_post, $post->ID);

            $data = [
                'label' => esc_html($post->post_title),
                'id' => (int)$post->ID,
                'timestamp' => esc_attr($post->post_date),
                'icon' => esc_attr($postTypeOptions['icon']),
                'color' => esc_attr($postTypeOptions['color']),
                'showTime' => (bool)$this->showPostsPublishTime($post->post_status),
                'canEdit' => $canEdit,
            ];

            if (Util::isPlannersProActive()) {
                $modal_data = $this->localize_post_data([], $post, $canEdit);

                $data['calendar_post_data'] = !empty($modal_data['posts'][0]) ? $modal_data['posts'][0] : [];
                $data['calendar_taxonomies_data'] = !empty($modal_data['taxonomies'][$post->ID]) ? $modal_data['taxonomies'][$post->ID] : [];
            } else {
                $data['calendar_post_data'] = [];
                $data['calendar_taxonomies_data'] = [];
            }

            return $data;
        }

        public function moveCalendarItemToNewDate()
        {
            if (! wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'publishpress-calendar-get-data')) {
                wp_send_json(['error' => __('Invalid nonce', 'publishpress')], 403);
            }

            $postId = isset($_POST['id']) ? (int)$_POST['id'] : null;
            $newYear = isset($_POST['year']) ? (int)$_POST['year'] : null;
            $newMonth = isset($_POST['month']) ? (int)$_POST['month'] : null;
            $newDay = isset($_POST['day']) ? (int)$_POST['day'] : null;

            if (empty($postId) || empty($newYear) || empty($newMonth) || empty($newDay)) {
                wp_send_json(['error' => __('Invalid input', 'publishpress')], 400);
            }

            $post = get_post($postId);

            if (empty($post) || is_wp_error($post)) {
                wp_send_json(['error' => __('Post not found', 'publishpress')], 404);
            }

            // Check that the user can modify the post
            if (! $this->current_user_can_modify_post($post)) {
                wp_send_json(['error' => __('No enough permissions', 'publishpress')], 403);
            }

            $oldPostDate = $post->post_date;
            $postDate = null;
            try {
                $postDate = new DateTime($post->post_date);
                $postDate->setDate($newYear, $newMonth, $newDay);
            } catch (Exception $e) {
                wp_send_json(['error' => __('Invalid date', 'publishpress')], 400);
            }

            $newDate = $postDate->format('Y-m-d H:i:s');

            wp_update_post(
                [
                    'ID' => $postId,
                    'post_date' => $newDate,
                    'post_date_gmt' => get_gmt_from_date($newDate),
                    'edit_date' => true,

                ]
            );

            /**
             * @param int $postId
             * @param string $newDate
             */
            do_action('publishpress_after_moving_calendar_item', $postId, $newDate, $oldPostDate);

            wp_send_json(
                true,
                200
            );
        }

        private function addTaxQueryToArgs($taxonomy, $termSlug, $args)
        {
            if (! isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }

            $args['tax_query'][] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $termSlug
            ];

            return $args;
        }

        public function fetchCalendarDataJson()
        {
            if (! wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'publishpress-calendar-get-data')) {
                wp_send_json([], 403);
            }

            $beginningDate = $this->get_beginning_of_week(sanitize_text_field($_GET['start_date']));
            $endingDate = $this->get_ending_of_week($beginningDate, 'Y-m-d', (int)$_GET['number_of_weeks']);

            //get and update filters
            $args = $this->get_filters();

            wp_send_json(
                $this->getCalendarData($beginningDate, $endingDate, $args),
                200
            );
        }

        private function getPostTypeName($postType)
        {
            $postTypeObj = get_post_type_object($postType);

            return $postTypeObj->label;
        }

        private function getPostStatusName($postStatusSlug)
        {
            $postStatuses = $this->get_post_statuses();

            foreach ($postStatuses as $postStatus) {
                if ($postStatus->slug === $postStatusSlug) {
                    return $postStatus->label;
                }
            }

            return null;
        }

        private function getPostTerms($postId, $taxonomy)
        {
            $terms = wp_get_post_terms($postId, $taxonomy);

            $termsNames = [];
            foreach ($terms as $term) {
                $termsNames[] = $term->name;
            }

            return $termsNames;
        }

        private function getPostCategoriesNames($postId)
        {
            return $this->getPostTerms($postId, 'category');
        }

        private function getPostTagsNames($postId)
        {
            return $this->getPostTerms($postId, 'post_tag');
        }

        public function getPostData()
        {
            if (! wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'publishpress-calendar-get-data')) {
                wp_send_json([], 403);
            }

            $id = (int)$_GET['id'];

            if (empty($id)) {
                wp_send_json(null, 404);
            }

            $post = get_post($id);

            if (empty($post) || is_wp_error($post)) {
                wp_send_json(null, 404);
            }

            $authorsNames = apply_filters(
                'publishpress_post_authors_names',
                [get_the_author_meta('display_name', $post->post_author)],
                $id
            );

            $categories = $this->getPostCategoriesNames($id);
            $tags = $this->getPostTagsNames($id);

            $data = [
                'id' => $id,
                'status' => $post->post_status,
                'fields' => [
                    'type' => [
                        'label' => __('Post Type', 'publishpress'),
                        'value' => $this->getPostTypeName($post->post_type),
                        'type' => 'type',
                    ],
                    'id' => [
                        'label' => __('ID', 'publishpress'),
                        'value' => $id,
                        'type' => 'number',
                    ],
                    'date' => [
                        'label' => __('Date', 'publishpress'),
                        'value' => $post->post_date,
                        'valueString' => get_the_date(get_option('date_format', 'Y-m-d H:i:s'), $post),
                        'type' => 'date',
                    ],
                    'status' => [
                        'label' => __('Post Status', 'publishpress'),
                        'value' => $this->getPostStatusName($post->post_status),
                        'type' => 'status',
                    ],
                    'authors' => [
                        'label' => _n('Author', 'Authors', count($authorsNames), 'publishpress'),
                        'value' => $authorsNames,
                        'type' => 'authors',
                    ],
                    'categories' => [
                        'label' => _n('Category', 'Categories', count($categories), 'publishpress'),
                        'value' => $categories,
                        'type' => 'taxonomy',
                    ],
                    'tags' => [
                        'label' => _n('Tag', 'Tags', count($tags), 'publishpress'),
                        'value' => $tags,
                        'type' => 'taxonomy',
                    ],
                ],
                'links' => []
            ];

            $postTypeObject = get_post_type_object($post->post_type);

            if (current_user_can($postTypeObject->cap->edit_post, $post->ID)) {
                $data['links']['edit'] = [
                    'label' => __('Edit', 'publishpress'),
                    'url' => htmlspecialchars_decode(get_edit_post_link($id))
                ];
            }

            if (current_user_can($postTypeObject->cap->delete_post, $post->ID)) {
                $data['links']['trash'] = [
                    'label' => __('Trash', 'publishpress'),
                    'url' => htmlspecialchars_decode(get_delete_post_link($id)),
                ];
            }

            if (current_user_can($postTypeObject->cap->read_post, $post->ID)) {
                if ($post->post_status === 'publish') {
                    $label = __('View', 'publishpress');
                    $link = get_permalink($id);
                } else {
                    $label = __('Preview', 'publishpress');
                    $link = get_preview_post_link($id);
                }

                $data['links']['view'] = [
                    'label' => $label,
                    'url' => htmlspecialchars_decode($link),
                ];
            }

            $data = apply_filters('publishpress_calendar_get_post_data', $data, $post);

            wp_send_json($data, 202);
        }

        public function getPostTypeFields()
        {
            global $publishpress;

            if (! wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'publishpress-calendar-get-data')) {
                wp_send_json([], 403);
            }

            $postType = isset($_GET['postType']) ? sanitize_text_field($_GET['postType']) : 'post';
            $postTypeObject = get_post_type_object($postType);
            if (empty($postTypeObject) || is_wp_error($postTypeObject)) {
                wp_send_json([], 404);
            }

            $fields = [
                'title' => [
                    'label' => __('Title', 'publishpress'),
                    'value' => null,
                    'type' => 'text',
                ],
                'status' => [
                    'label' => __('Post Status', 'publishpress'),
                    'value' => 'draft',
                    'type' => 'status',
                    'options' => $this->getUserAuthorizedPostStatusOptions($postType)
                ],
                'time' => [
                    'label' => __('Publish Time', 'publishpress'),
                    'value' => null,
                    'type' => 'time',
                    'placeholder' => isset($this->module->options->default_publish_time) ? $this->module->options->default_publish_time : null,
                ]
            ];

            if (current_user_can($postTypeObject->cap->edit_others_posts)) {
                $fields['authors'] = [
                    'label' => __('Author', 'publishpress'),
                    'value' => null,
                    'type' => 'authors',
                ];
            }

            $taxonomies = get_object_taxonomies($postType);

            if (in_array('category', $taxonomies)) {
                $fields['categories'] = [
                    'label' => __('Categories', 'publishpress'),
                    'value' => null,
                    'type' => 'taxonomy',
                    'taxonomy' => 'category',
                ];
            }

            if (in_array('post_tag', $taxonomies)) {
                $fields['tags'] = [
                    'label' => __('Tags', 'publishpress'),
                    'value' => null,
                    'type' => 'taxonomy',
                    'taxonomy' => 'post_tag',
                ];
            }

            $fields['content'] = [
                'label' => __('Content', 'publishpress'),
                'value' => null,
                'type' => 'html'
            ];

            if (class_exists('PP_Editorial_Metadata')) {
                $editorial_metadata_class = new PP_Editorial_Metadata;
                $editorial_metadata_terms = $publishpress->editorial_metadata->get_editorial_metadata_terms(['show_in_calendar_form' => true]);
                foreach ($editorial_metadata_terms as $term) {
                    if (isset($term->post_types) && is_array($term->post_types) && in_array($postType, $term->post_types)) {
                        $term_options = $editorial_metadata_class->get_editorial_metadata_term_by('id', $term->term_id);
                        $postmeta_key = esc_attr($editorial_metadata_class->get_postmeta_key($term));
                        $post_types = (isset($term->post_types) && is_array($term->post_types)) ? array_values($term->post_types) : [];
                        $post_types = join(" ", $post_types);
                        $term_data = [
                        'name' => $postmeta_key,
                        'label' => $term->name,
                        'description' => $term->description,
                        'term_options' => $term_options,
                    ];
                        $term_type = $term->type;
                        if ($term_type === 'user') {
                            $ajaxArgs    = [];
                            if (isset($term->user_role)) {
                                $ajaxArgs['user_role'] = $term->user_role;
                            }
                            $fields[$term_data['name']] = [
                            'metadata' => true,
                            'term'     => $term,
                            'label'    => $term->name,
                            'value'    => '',
                            'ajaxArgs' => $ajaxArgs,
                            'post_types' => $post_types,
                            'type'     => 'authors',
                            'multiple' => ''
                        ];
                        } elseif ($term_type === 'paragraph') {
                            $fields[$term_data['name']] = [
                            'metadata' => true,
                            'term'     => $term,
                            'label'    => $term->name,
                            'post_types' => $post_types,
                            'value'    => '',
                            'type'     => 'html'
                        ];
                        } else {
                            $html = apply_filters("pp_editorial_metadata_{$term->type}_get_input_html", $term_data, '');
                            $fields[$term_data['name']] = [
                            'metadata' => true,
                            'post_types' => $post_types,
                            'html'     => (is_object($html) || is_array($html)) ? '' : '<div class="pp-calendar-form-metafied '. $post_types .'">' . $html . '</div>',
                            'term'     => $term,
                            'label'    => $term->name,
                            'value'    => '',
                            'type'     => 'metafield'
                        ];
                        }
                    }
                }
            }

            $fields = apply_filters('publishpress_calendar_get_post_type_fields', $fields, $postType);

            $data = ['fields' => $fields];

            wp_send_json($data, 202);
        }

        private function formatDateFromString($date, $originalFormat = 'Y-m-d')
        {
            $datetime = date_create_immutable_from_format($originalFormat, $date);
            return $datetime->format(get_option('date_format', 'Y-m-d H:i:s'));
        }

        /**
         * @throws Exception
         */
        public function createItem()
        {
            if (! wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'publishpress-calendar-get-data')) {
                $this->print_ajax_response('error', $this->module->messages['nonce-failed']);
            }

            // Check that the user has the right capabilities to add posts to the calendar (defaults to 'edit_posts')
            if (! current_user_can($this->create_post_cap)) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            $postType = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : null;
            $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : null;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
            $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            // Sanitized by the wp_filter_post_kses function.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $content = isset($_POST['content']) ? wp_filter_post_kses($_POST['content']) : '';
            $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
            $authors = isset($_POST['authors']) ? explode(',', sanitize_text_field($_POST['authors'])) : [];
            $categories = isset($_POST['categories']) ? explode(',', sanitize_text_field($_POST['categories'])) : [];
            $tags = isset($_POST['tags']) ? explode(',', sanitize_text_field($_POST['tags'])) : [];

            if (empty($date)) {
                $this->print_ajax_response('error', __('No date supplied.', 'publishpress'));
            }

            // Post type has to be visible on the calendar to create a placeholder
            if (empty($postType)) {
                $postType = 'post';
            }

            if (! in_array($postType, $this->get_post_types_for_module($this->module))) {
                $this->print_ajax_response(
                    'error',
                    __('The selected post type is not enabled for the calendar.', 'publishpress')
                );
            }

            $title = apply_filters('pp_calendar_after_form_submission_sanitize_title', $title);
            if (empty($title)) {
                $title = __('Untitled', 'publishpress');
            }

            $content = apply_filters('pp_calendar_after_form_submission_sanitize_content', $content);
            if (empty($content)) {
                $content = '';
            }

            $authors = apply_filters('pp_calendar_after_form_submission_sanitize_author', $authors);
            try {
                $authors = apply_filters('pp_calendar_after_form_submission_validate_author', $authors);
            } catch (Exception $e) {
                $this->print_ajax_response('error', $e->getMessage());
            }

            if (empty($authors)) {
                $authors = apply_filters('publishpress_calendar_default_author', get_current_user_id());
            }

            if (! is_array($authors)) {
                $authors = [$authors];
            }

            if (! $this->isPostStatusValid($status)) {
                $this->print_ajax_response('error', __('Invalid Status supplied.', 'publishpress'));
            }

            $categories = array_map('sanitize_text_field', $categories);
            $tags = array_map('sanitize_text_field', $tags);

            $dateTimestamp = strtotime($date);

            if (empty($time)) {
                $time = $this->module->options->default_publish_time;
            }

            if (! empty($time)) {
                $date = sprintf(
                    '%s %s',
                    $date,
                    ((function_exists('mb_strlen') ? mb_strlen($time) : strlen($time)) === 5)
                        ? "{$time}:" . date('s', $dateTimestamp)
                        : date('H:i:s', $dateTimestamp)
                );
            }

            $dateTimeInstance = new DateTime($date);
            if (! $dateTimeInstance) {
                $this->print_ajax_response('error', __('Invalid Publish Date supplied.', 'publishpress'));
            }
            unset($dateTimeInstance);

            // Set new post parameters
            $postPlaceholder = [
                'post_author' => $authors[0],
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => $postType,
                'post_status' => $status,
                'post_date' => $date,
                'post_modified' => current_time('mysql'),
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
                $postPlaceholder['post_date_gmt'] = get_gmt_from_date($date);
            }

            // Create the post
            add_filter('wp_insert_post_data', [$this, 'alter_post_modification_time'], 99, 2);
            $postId = wp_insert_post($postPlaceholder);
            remove_filter('wp_insert_post_data', [$this, 'alter_post_modification_time'], 99);

            do_action('publishpress_calendar_after_create_post', $postId, $authors);

            if ($postId) {
                if (! empty($categories)) {
                    $categoriesIdList = [];
                    foreach ($categories as $categorySlug) {
                        $category = get_term_by('slug', $categorySlug, 'category');

                        if (! $category || is_wp_error($category)) {
                            $category = wp_create_category($categorySlug);
                            $category = get_term($category);
                        }

                        if (! is_wp_error($category)) {
                            $categoriesIdList[] = $category->term_id;
                        }
                    }

                    wp_set_post_terms($postId, $categoriesIdList, 'category');
                }

                if (! empty($tags)) {
                    foreach ($tags as $tagSlug) {
                        $tag = get_term_by('slug', $tagSlug, 'post_tag');

                        if (! $tag || is_wp_error($tag)) {
                            wp_create_tag($tagSlug);
                        }
                    }

                    wp_set_post_terms($postId, $tags);
                }

                // announce success and send back the html to inject
                $this->print_ajax_response(
                    'success',
                    __('Post created successfully', 'publishpress'),
                    [
                        'postId' => $postId,
                        'link' => htmlspecialchars_decode(get_edit_post_link($postId)),
                    ]
                );
            } else {
                $this->print_ajax_response('error', __('Post could not be created', 'publishpress'));
            }
        }

        private function getCalendarData($beginningDate, $endingDate, $args = [])
        {
            $post_query_args = [
                'post_status' => null,
                'post_type' => null,
                'author' => null,
                'date_query' => [
                    'column' => 'post_date',
                    'after' => $beginningDate,
                    'before' => $endingDate,
                    'inclusive' => true,
                ]
            ];

            $post_query_args = wp_parse_args($args, $post_query_args);

            if (isset($this->module->options->sort_by) && $this->module->options->sort_by === 'status') {
                $post_query_args['orderby'] = ['post_status' => 'ASC'];
            } else {
                $post_query_args['orderby'] = ['post_date' => 'ASC'];
            }

            /**
             * @param array $post_query_args The array with args passed to post query
             * @param string $beginningDate The beginning date showed in the calendar
             * @param string $endingDate The ending date showed in the calendar
             *
             * @return array
             */
            $post_query_args = apply_filters('publishpress_calendar_data_args', $post_query_args, $beginningDate, $endingDate);

            $postsList = $this->getCalendarDataForMultipleWeeks($post_query_args);

            $data = [];

            foreach ($postsList as $date => $posts) {
                if (! isset($data[$date])) {
                    $data[$date] = [];
                }

                foreach ($posts as $post) {
                    $data[$date][] = $this->extractPostDataForTheCalendar($post);
                }
            }

            return $data;
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
                    $valid_statuses = wp_list_pluck($this->get_post_statuses(), 'slug');
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
                    $cpt = sanitize_key($dirty_value);
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
                    return sanitize_text_field($dirty_value);
                    break;
            }
        }

        public function calendar_filter_options($select_id, $select_name, $filters)
        {
            switch ($select_id) {
                case 'post_status':
                    $post_statuses = $this->get_post_statuses();
                    ?>
                    <select id="<?php
                    echo esc_attr($select_id); ?>" name="<?php
                    echo esc_attr($select_name); ?>">
                        <option value=""><?php
                            esc_html_e('All statuses', 'publishpress'); ?></option>
                        <?php
                        foreach ($post_statuses as $post_status) {
                            echo "<option value='" . esc_attr($post_status->slug) . "' " . selected(
                                    $post_status->slug,
                                    $filters['post_status']
                                ) . '>' . esc_html($post_status->label) . '</option>';
                        }
                        ?>
                    </select>
                    <?php
                    break;
                case 'cat':
                    $categoryId = isset($filters['cat']) ? (int)$filters['cat'] : 0;
                    ?>
                    <select id="filter_category" name="cat">
                        <option value=""><?php
                            esc_html_e('View all categories', 'publishpress'); ?></option>
                        <?php
                        if (! empty($categoryId)) {
                            $category = get_term($categoryId, 'category');

                            echo "<option value='" . esc_attr($categoryId) . "' selected='selected'>" . esc_html(
                                    $category->name
                                ) . "</option>";
                        }
                        ?>
                    </select>
                    <?php
                    break;
                case 'tag':
                    $tagId = isset($filters['tag']) ? (int)$filters['tag'] : 0;
                    ?>
                    <select id="filter_tag" name="tag">
                        <option value=""><?php
                            esc_html_e('All tags', 'publishpress'); ?></option>
                        <?php
                        if (! empty($tagId)) {
                            $tag = get_term($tagId, 'post_tag');

                            echo "<option value='" . esc_attr($tagId) . "' selected='selected'>" . esc_html(
                                    $tag->name
                                ) . "</option>";
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
                        <option value="" <?php
                        echo $selectedOptionAll; ?>>
                            <?php
                            esc_html_e('All authors', 'publishpress'); ?>
                        </option>
                        <?php
                        if (! empty($authorId)) {
                            $author = get_user_by('id', $authorId);
                            $option = '';

                            if (! empty($author)) {
                                $option = '<option value="' . esc_attr($authorId) . '" selected="selected">' . esc_html(
                                        $author->display_name
                                    ) . '</option>';
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
                            <option value=""><?php
                                esc_html_e('All types', 'publishpress'); ?></option>
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
                    if (! isset($filters['weeks'])) {
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
         *
         * @return  bool
         *
         * @access  private
         */
        private function showPostsPublishTime($status)
        {
            if ($this->module->options->show_posts_publish_time === 'on') {
                $this->module->options->show_posts_publish_time = [
                    'publish' => 'on',
                    'future' => 'on',
                ];
            }

            return isset($this->module->options->show_posts_publish_time[$status])
                && $this->module->options->show_posts_publish_time[$status] === 'on';
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
         * @param array $authorsIds
         *
         * @return  array
         */
        public static function sanitize_author_input($authorsIds = [])
        {
            return array_map('intval', $authorsIds);
        }

        /**
         * @param array $postAuthorIds
         *
         * @return  array
         *
         * @throws  Exception
         */
        public static function validateAuthorForPost($postAuthorIds = [])
        {
            if (empty($postAuthorIds)) {
                return null;
            }

            foreach ($postAuthorIds as $authorId) {
                $user = get_user_by('id', $authorId);

                $isValid = is_object($user) && ! is_wp_error($user) && $user->has_cap('edit_posts');

                if (! apply_filters('publishpress_author_can_edit_posts', $isValid, $authorId)) {
                    throw new Exception(
                        esc_html__(
                            "The selected user doesn't have enough permissions to be set as the post author.",
                            'publishpress'
                        )
                    );
                }
            }

            return $postAuthorIds;
        }

        public function setDefaultCapabilities()
        {
            $role = get_role('administrator');

            $viewCapability = $this->getViewCapability();

            if (! $role->has_cap($viewCapability)) {
                $role->add_cap($viewCapability);
            }
        }

        private function getViewCapability()
        {
            $viewCapability = apply_filters_deprecated(
                    'pp_view_calendar_cap',
                    [self::VIEW_CAPABILITY],
                '3.6.4',
                'publishpress_calendar_cap_view_calendar'
            );

            return apply_filters('publishpress_calendar_cap_view_calendar', $viewCapability);
        }
    }
}
