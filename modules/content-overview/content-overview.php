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

/**
 * class PP_Content_Overview
 * This class displays a budgeting system for an editorial desk's publishing workflow.
 *
 * @author sbressler
 */
class PP_Content_Overview extends PP_Module
{
    use Dependency_Injector;

    /**
     * Screen id
     */
    const SCREEN_ID = 'dashboard_page_content-overview';

    /**
     * Usermeta key prefix
     */
    const USERMETA_KEY_PREFIX = 'PP_Content_Overview_';

    /**
     * Default number of columns
     */
    const DEFAULT_NUM_COLUMNS = 1;

    /**
     * @var string
     */
    const MENU_SLUG = 'pp-content-overview';

    /**
     * [$taxonomy_used description]
     *
     * @var string
     */
    public $taxonomy_used = 'category';

    /**
     * [$module description]
     *
     * @var [type]
     */
    public $module;

    /**
     * [$num_columns description]
     *
     * @var integer
     */
    public $num_columns = 0;

    /**
     * [$max_num_columns description]
     *
     * @var [type]
     */
    public $max_num_columns;

    /**
     * [$no_matching_posts description]
     *
     * @var boolean
     */
    public $no_matching_posts = true;

    /**
     * [$terms description]
     *
     * @var array
     */
    public $terms = [];

    /**
     * @var array
     */
    public $columns;

    /**
     * [$user_filters description]
     *
     * @var [type]
     */
    public $user_filters;

    /**
     * Custom methods
     *
     * @var Array
     */
    private $terms_options = [];

    /**
     * Register the module with PublishPress but don't do anything else
     */
    public function __construct()
    {
        $this->module_url = $this->get_module_url(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title'                => __('Content Overview', 'publishpress'),
            'short_description'    => false,
            'extended_description' => false,
            'module_url'           => $this->module_url,
            'icon_class'           => 'dashicons dashicons-list-view',
            'slug'                 => 'content-overview',
            'default_options'      => [
                'enabled'    => 'on',
                'post_types' => [
                    'post' => 'on',
                    'page' => 'off',
                ],
            ],
            'general_options'      => true,
            'options_page'         => false,
            'autoload'             => false,
            'add_menu'             => true,
            'page_link'            => admin_url('admin.php?page=content-overview'),
        ];

        $this->module = PublishPress()->register_module('content_overview', $args);
    }

    /**
     * Initialize the rest of the stuff in the class if the module is active
     */
    public function init()
    {
        $this->setDefaultCapabilities();

        $view_content_overview_cap = apply_filters('pp_view_content_overview_cap', 'pp_view_content_overview');
        if (!current_user_can($view_content_overview_cap)) {
            return;
        }

        $this->num_columns     = $this->get_num_columns();
        $this->max_num_columns = apply_filters('PP_Content_Overview_max_num_columns', 3);

        // Filter to allow users to pick a taxonomy other than 'category' for sorting their posts
        $this->taxonomy_used = apply_filters('PP_Content_Overview_taxonomy_used', $this->taxonomy_used);

        add_action('admin_init', [$this, 'handle_form_date_range_change']);

        add_action('admin_init', [$this, 'handle_screen_options']);

        // Register our settings
        add_action('admin_init', [$this, 'register_settings']);

        add_action('admin_init', [$this, 'register_columns']);

        add_action('wp_ajax_publishpress_content_overview_search_authors', [$this, 'searchAuthors']);
        add_action('wp_ajax_publishpress_content_overview_search_categories', [$this, 'searchCategories']);

        // Menu
        add_filter('publishpress_admin_menu_slug', [$this, 'filter_admin_menu_slug'], 20);
        add_action('publishpress_admin_menu_page', [$this, 'action_admin_menu_page'], 20);
        add_action('publishpress_admin_submenu', [$this, 'action_admin_submenu'], 20);

        // Load necessary scripts and stylesheets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'action_enqueue_admin_styles']);
    }

    public function setDefaultCapabilities()
    {
        $role = get_role('administrator');

        $view_content_overview_cap = 'pp_view_content_overview';
        $view_content_overview_cap = apply_filters('pp_view_content_overview_cap', $view_content_overview_cap);

        if (!$role->has_cap($view_content_overview_cap)) {
            $role->add_cap($view_content_overview_cap);
        }
    }

    /**
     * Get the number of columns to show on the content overview
     */
    public function get_num_columns()
    {
        if (empty($this->num_columns)) {
            $current_user      = wp_get_current_user();
            $this->num_columns = $this->get_user_meta(
                $current_user->ID,
                self::USERMETA_KEY_PREFIX . 'screen_columns',
                true
            );
            // If usermeta didn't have a value already, use a default value and insert into DB
            if (empty($this->num_columns)) {
                $this->num_columns = self::DEFAULT_NUM_COLUMNS;
                $this->save_column_prefs([self::USERMETA_KEY_PREFIX . 'screen_columns' => $this->num_columns]);
            }
        }

        return $this->num_columns;
    }

    /**
     * Save the current user's preference for number of columns.
     */
    public function save_column_prefs($posted_fields)
    {
        $key               = self::USERMETA_KEY_PREFIX . 'screen_columns';
        $this->num_columns = (int)$posted_fields[$key];

        $current_user = wp_get_current_user();
        $this->update_user_meta($current_user->ID, $key, $this->num_columns);
    }

    public function handle_screen_options()
    {
        include_once PUBLISHPRESS_BASE_PATH . '/common/php/' . 'screen-options.php';

        if (function_exists('add_screen_options_panel')) {
            add_screen_options_panel(
                self::USERMETA_KEY_PREFIX . 'screen_columns',
                __('Screen Layout', 'publishpress'),
                [$this, 'print_column_prefs'],
                self::SCREEN_ID,
                [$this, 'save_column_prefs'],
                true
            );
        }
    }

    /**
     * Register settings for notifications so we can partially use the Settings API
     * (We use the Settings API for form generation, but not saving)
     *
     * @since 0.7
     * @uses  add_settings_section(), add_settings_field()
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
            __('Add to these post types:', 'publishpress'),
            [$this, 'settings_post_types_option'],
            $this->module->options_group_name,
            $this->module->options_group_name . '_general'
        );
    }

    /**
     * Choose the post types for editorial metadata
     *
     * @since 0.7
     */
    public function settings_post_types_option()
    {
        global $publishpress;
        $publishpress->settings->helper_option_custom_post_type($this->module);
    }

    /**
     * Get the post types for editorial metadata
     *
     * @return array $post_types All existing post types
     *
     * @since 0.7
     */
    public function get_settings_post_types()
    {
        global $publishpress;
        return $publishpress->settings->get_supported_post_types_for_module($this->module);
    }


    /**
     * Validate data entered by the user
     *
     * @param array $new_options New values that have been entered by the user
     *
     * @return array $new_options Form values after they've been sanitized
     * @since 0.7
     *
     */
    public function settings_validate($new_options)
    {
        // Whitelist validation for the post type options
        if (!isset($new_options['post_types'])) {
            $new_options['post_types'] = [];
        }
        $new_options['post_types'] = $this->clean_post_type_options(
            $new_options['post_types'],
            $this->module->post_type_support
        );

        return $new_options;
    }

    /**
     * Settings page for notifications
     *
     * @since 0.7
     */
    public function print_configure_view()
    {
        settings_fields($this->module->options_group_name);
        do_settings_sections($this->module->options_group_name);
    }

    /**
     * Give users the appropriate permissions to view the content overview the first time the module is loaded
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
            // Migrate whether the content overview was enabled or not and clean up old option
            if ($enabled = get_option('publishpress_content_overview_enabled')) {
                $enabled = 'on';
            } else {
                $enabled = 'off';
            }
            $publishpress->update_module_option($this->module->name, 'enabled', $enabled);
            delete_option('publishpress_content_overview_enabled');

            // Technically we've run this code before so we don't want to auto-install new data
            $publishpress->update_module_option($this->module->name, 'loaded_once', true);
        }
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
        if (empty($menu_slug) && $this->module_enabled('content_overview')) {
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
            esc_html__('Content Overview', 'publishpress'),
            apply_filters('pp_view_content_overview_cap', 'pp_view_calendar'),
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
            esc_html__('Content Overview', 'publishpress'),
            esc_html__('Content Overview', 'publishpress'),
            apply_filters('pp_view_content_overview_cap', 'pp_view_calendar'),
            self::MENU_SLUG,
            [$this, 'render_admin_page'],
            20
        );
    }

    /**
     * Enqueue necessary admin scripts only on the content overview page.
     *
     * @uses enqueue_admin_script()
     */
    public function enqueue_admin_scripts()
    {
        global $pagenow;

        // Only load calendar styles on the calendar page
        if ('admin.php' === $pagenow && isset($_GET['page']) && $_GET['page'] === 'pp-content-overview') {
            $num_columns = $this->get_num_columns();
            echo '<script type="text/javascript"> var PP_Content_Overview_number_of_columns="' . esc_js(
                    $this->num_columns
                ) . '";</script>';

            $this->enqueue_datepicker_resources();
            wp_enqueue_script(
                'publishpress-content_overview',
                $this->module_url . 'lib/content-overview.js',
                ['publishpress-date_picker', 'publishpress-select2'],
                PUBLISHPRESS_VERSION,
                true
            );

            wp_enqueue_script(
                'publishpress-select2',
                PUBLISHPRESS_URL . 'common/libs/select2-v4.0.13.1/js/select2.min.js',
                ['jquery'],
                PUBLISHPRESS_VERSION
            );

            wp_localize_script(
                'publishpress-content_overview',
                'PPContentOverview',
                [
                    'nonce' => wp_create_nonce('content_overview_filter_nonce'),
                ]
            );
        }
    }

    /**
     * Enqueue a screen and print stylesheet for the content overview.
     */
    public function action_enqueue_admin_styles()
    {
        global $pagenow;

        // Only load calendar styles on the calendar page
        if ('admin.php' === $pagenow && isset($_GET['page']) && $_GET['page'] === 'pp-content-overview') {
            wp_enqueue_style(
                'pp-admin-css',
                PUBLISHPRESS_URL . 'common/css/publishpress-admin.css',
                ['publishpress-select2'],
                PUBLISHPRESS_VERSION,
                'screen'
            );
            wp_enqueue_style(
                'publishpress-content_overview-styles',
                $this->module_url . 'lib/content-overview.css',
                false,
                PUBLISHPRESS_VERSION,
                'screen'
            );
            wp_enqueue_style(
                'publishpress-content_overview-print-styles',
                $this->module_url . 'lib/content-overview-print.css',
                false,
                PUBLISHPRESS_VERSION,
                'print'
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
     * Register the columns of information that appear for each term module.
     * Modeled after how WP_List_Table works, but focused on hooks instead of OOP extending
     *
     * @since 0.7
     */
    public function register_columns()
    {
        $columns = [
            'post_title'         => __('Title', 'publishpress'),
            'post_status'        => __('Status', 'publishpress'),
            'post_author'        => __('Author', 'publishpress'),
            'post_date'     => __('Post Date', 'publishpress'),
            'post_modified' => __('Last Modified', 'publishpress'),
        ];

        /**
         * @param array $columns
         *
         * @deprecated Use publishpress_content_overview_columns
         * @return array
         */
        $columns = apply_filters('PP_Content_Overview_term_columns', $columns);

        /**
         * @param array $columns
         *
         * @return array
         */
        $columns = apply_filters('publishpress_content_overview_columns', $columns);

        if (class_exists('PP_Editorial_Metadata')) {
            $additional_terms = get_terms(
                [
                    'taxonomy'   => PP_Editorial_Metadata::metadata_taxonomy,
                    'orderby'    => 'name',
                    'order'      => 'asc',
                    'hide_empty' => 0,
                    'parent'     => 0,
                    'fields'     => 'all',
                ]
            );

            $additional_terms = apply_filters('PP_Content_Overview_filter_terms', $additional_terms);
            foreach ($additional_terms as $term) {
                if (!is_object($term) || $term->taxonomy !== PP_Editorial_Metadata::metadata_taxonomy) {
                    continue;
                }

                $term_options = $this->get_unencoded_description($term->description);

                if (!isset($term_options['viewable']) ||
                    (bool)$term_options['viewable'] === false ||
                    isset($columns[$term->slug])) {
                    continue;
                }

                $this->terms_options[$term->slug] = $term_options;

                $columns[$term->slug] = $term->name;
            }

            $this->columns = $columns;
        }
    }

    /**
     * Handle a form submission to change the user's date range on the budget
     *
     * @since 0.7
     */
    public function handle_form_date_range_change()
    {
        if (
            !isset(
                $_POST['pp-content-overview-number-days'],
                $_POST['pp-content-overview-start-date_hidden'],
                $_POST['pp-content-overview-range-use-today']
            )
            || (
                !isset($_POST['pp-content-overview-range-submit'])
                && $_POST['pp-content-overview-range-use-today'] == '0'
            )
        ) {
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'change-date')) {
            wp_die($this->module->messages['nonce-failed']);
        }

        $current_user = wp_get_current_user();
        $user_filters = $this->get_user_meta(
            $current_user->ID,
            self::USERMETA_KEY_PREFIX . 'filters',
            true
        );

        $use_today_as_start_date = (bool)$_POST['pp-content-overview-range-use-today'];

        $start_date_format          = 'Y-m-d';
        $user_filters['start_date'] = $use_today_as_start_date
            ? current_time($start_date_format)
            : date($start_date_format, strtotime($_POST['pp-content-overview-start-date_hidden']));

        $user_filters['number_days'] = (int)$_POST['pp-content-overview-number-days'];

        if ($user_filters['number_days'] <= 1) {
            $user_filters['number_days'] = 1;
        }

        $this->update_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', $user_filters);
        wp_redirect(menu_page_url('pp-content-overview', false));

        exit;
    }

    /**
     * Print column number preferences for screen options
     */
    public function print_column_prefs()
    {
        $return_val = __('Number of Columns: ', 'publishpress');

        for ($i = 1; $i <= $this->max_num_columns; ++$i) {
            $return_val .= "<label><input type='radio' name='" . esc_attr(
                    self::USERMETA_KEY_PREFIX
                ) . "screen_columns' value='" . esc_attr($i) . "' " . checked(
                    $this->get_num_columns(),
                    $i,
                    false
                ) . " />&nbsp;" . esc_attr($i) . "</label>\n";
        }

        return $return_val;
    }

    /**
     * Create the content overview view. This calls lots of other methods to do its work. This will
     * output any messages, create the table navigation, then print the columns based on
     * get_num_columns(), which will in turn print the stories themselves.
     */
    public function render_admin_page()
    {
        global $publishpress;

        // Update the current user's filters with the variables set in $_GET
        $this->user_filters = $this->update_user_filters();

        if (!empty($this->user_filters['cat'])) {
            $terms   = [];
            $terms[] = get_term($this->user_filters['cat'], $this->taxonomy_used);
        } else {
            // Get all of the terms from the taxonomy, regardless whether there are published posts
            $args  = [
                'orderby'    => 'name',
                'order'      => 'asc',
                'hide_empty' => 0,
                'parent'     => 0,
            ];
            $terms = get_terms($this->taxonomy_used, $args);
        }

        if (class_exists('PP_Editorial_Metadata')) {
            $this->terms = array_filter(
            // allow for reordering or any other filtering of terms
                apply_filters('PP_Content_Overview_filter_terms', $terms),
                function ($term) {
                    if ($term->taxonomy !== PP_Editorial_Metadata::metadata_taxonomy) {
                        return true;
                    }

                    $term_options = $this->get_unencoded_description($term->description);

                    return isset($term_options['viewable']) && (bool)$term_options['viewable'];
                }
            );
        } else {
            // allow for reordering or any other filtering of terms
            $this->terms = apply_filters('PP_Content_Overview_filter_terms', $terms);
        }

        $description = sprintf(
            '%s <span class="time-range">%s</span>',
            esc_html__('Content Overview', 'publishpress'),
            $this->content_overview_time_range()
        );
        $publishpress->settings->print_default_header($publishpress->modules->content_overview, $description); ?>
        <div class="wrap" id="pp-content-overview-wrap">
            <?php $this->print_messages(); ?>
            <?php $this->table_navigation(); ?>

            <div class="metabox-holder">
                <?php
                if (isset($_GET['ptype']) && !empty($_GET['ptype'])) {
                    $selectedPostTypes = [sanitize_text_field($_GET['ptype'])];
                } else {
                    $selectedPostTypes = $this->get_selected_post_types();
                }

                foreach ($selectedPostTypes as $postType) {
                    echo '<div class="postbox-container">';
                    $this->printPostForPostType(null, $postType);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <br clear="all">
        <?php

        $publishpress->settings->print_default_footer($publishpress->modules->content_overview);
    }

    public function get_selected_post_types()
    {
        $postTypesOption = $this->module->options->post_types;

        $enabledPostTypes = [];
        foreach ($postTypesOption as $postType => $status) {
            if ('on' === $status
                && !in_array($postType, $enabledPostTypes)) {
                $enabledPostTypes[] = $postType;
            }
        }

        return $enabledPostTypes;
    }

    /**
     * Update the current user's filters for content overview display with the filters in $_GET. The filters
     * in $_GET take precedence over the current users filters if they exist.
     */
    public function update_user_filters()
    {
        $current_user = wp_get_current_user();

        $user_filters = [
            'post_status' => $this->filter_get_param('post_status'),
            'cat'         => $this->filter_get_param('cat'),
            'author'      => $this->filter_get_param('author'),
            'start_date'  => $this->filter_get_param('start_date'),
            'number_days' => $this->filter_get_param('number_days'),
            'ptype'       => $this->filter_get_param('ptype'),
        ];

        $current_user_filters = [];
        $current_user_filters = $this->get_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', true);

        // If any of the $_GET vars are missing, then use the current user filter
        foreach ($user_filters as $key => $value) {
            if (is_null($value) && !empty($current_user_filters[$key])) {
                $user_filters[$key] = $current_user_filters[$key];
            }
        }

        if (!$user_filters['start_date']) {
            $user_filters['start_date'] = date('Y-m-d');
        }

        if (!$user_filters['number_days']) {
            $user_filters['number_days'] = 10;
        }

        $user_filters = apply_filters('PP_Content_Overview_filter_values', $user_filters, $current_user_filters);

        $this->update_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', $user_filters);

        return $user_filters;
    }

    /**
     *
     * @param string $param The parameter to look for in $_GET
     *
     * @return null if the parameter is not set in $_GET, empty string if the parameter is empty in $_GET,
     *                      or a sanitized version of the parameter from $_GET if set and not empty
     */
    public function filter_get_param($param)
    {
        // Sure, this could be done in one line. But we're cooler than that: let's make it more readable!
        if (!isset($_GET[$param])) {
            return null;
        } elseif (empty($_GET[$param])) {
            return '';
        }

        return sanitize_key($_GET[$param]);
    }

    /**
     * Allow the user to define the date range in a new and exciting way
     *
     * @since 0.7
     */
    public function content_overview_time_range()
    {
        $filtered_start_date           = $this->user_filters['start_date'];
        $filtered_start_date_timestamp = strtotime($filtered_start_date);

        $output = '<form method="POST" action="' . menu_page_url('pp-content-overview', false) . '">';

        $date_format = get_option('date_format');

        $start_date_value = '<input type="text" id="pp-content-overview-start-date" name="pp-content-overview-start-date"'
            . ' size="10" class="date-pick" data-alt-field="pp-content-overview-start-date_hidden" data-alt-format="' . pp_convert_date_format_to_jqueryui_datepicker(
                'Y-m-d'
            ) . '" value="'
            . esc_attr(date_i18n($date_format, $filtered_start_date_timestamp)) . '" />';
        $start_date_value .= '<input type="hidden" name="pp-content-overview-start-date_hidden" value="' . $filtered_start_date . '" />';
        $start_date_value .= '<span class="form-value">';

        $start_date_value .= esc_html(date_i18n($date_format, $filtered_start_date_timestamp));
        $start_date_value .= '</span>';

        $number_days_value = '<input type="text" id="pp-content-overview-number-days" name="pp-content-overview-number-days"'
            . ' size="3" maxlength="3" value="'
            . esc_attr($this->user_filters['number_days']) . '" /><span class="form-value">' . esc_html(
                $this->user_filters['number_days']
            )
            . '</span>';

        $output .= sprintf(
            _x(
                'starting %1$s showing %2$s %3$s',
                '%1$s = start date, %2$s = number of days, %3$s = translation of \'Days\'',
                'publishpress'
            ),
            $start_date_value,
            $number_days_value,
            _n('day', 'days', $this->user_filters['number_days'], 'publishpress')
        );
        $output .= '&nbsp;&nbsp;<span class="change-date-buttons">';
        $output .= '<input id="pp-content-overview-range-submit" name="pp-content-overview-range-submit" type="submit"';
        $output .= ' class="button button-primary hidden" value="' . __('Change', 'publishpress') . '" />';
        $output .= '&nbsp;';
        $output .= '<input id="pp-content-overview-range-today-btn" name="pp-content-overview-range-today-btn" type="submit"';
        $output .= ' class="button button-secondary hidden" value="' . __('Reset', 'publishpress') . '" />';
        $output .= '<input id="pp-content-overview-range-use-today" name="pp-content-overview-range-use-today" value="0" type="hidden" />';
        $output .= '&nbsp;';
        $output .= '<a class="change-date-cancel hidden" href="#">' . __('Cancel', 'publishpress') . '</a>';
        $output .= '<a class="change-date" href="#">' . __('Change', 'publishpress') . '</a>';
        $output .= wp_nonce_field('change-date', 'nonce', 'change-date-nonce', false);
        $output .= '</span></form>';

        return $output;
    }

    /**
     * Print any messages that should appear based on the action performed
     */
    public function print_messages()
    {
        if (isset($_GET['trashed']) || isset($_GET['untrashed'])) {
            echo '<div id="trashed-message" class="updated"><p>';

            // Following mostly stolen from edit.php

            if (isset($_GET['trashed']) && (int)$_GET['trashed']) {
                printf(
                    _n('Item moved to the trash.', '%d items moved to the trash.', (int)$_GET['trashed']),
                    number_format_i18n($_GET['trashed'])
                );
                $ids = isset($_GET['ids']) ? $_GET['ids'] : 0;
                echo ' <a href="' . esc_url(
                        wp_nonce_url(
                            "edit.php?post_type=post&doaction=undo&action=untrash&ids=$ids",
                            "bulk-posts"
                        )
                    ) . '">' . __('Undo', 'publishpress') . '</a><br />';
                unset($_GET['trashed']);
            }

            if (isset($_GET['untrashed']) && (int)$_GET['untrashed']) {
                printf(
                    _n(
                        'Item restored from the Trash.',
                        '%d items restored from the Trash.',
                        (int)$_GET['untrashed']
                    ),
                    number_format_i18n($_GET['untrashed'])
                );
                unset($_GET['undeleted']);
            }

            echo '</p></div>';
        }
    }

    /**
     * Print the table navigation and filter controls, using the current user's filters if any are set.
     */
    public function table_navigation()
    {
        ?>
        <div class="tablenav" id="pp-content-overview-tablenav">
            <div class="alignleft actions">
                <form method="GET" id="pp-content-filters">
                    <input type="hidden" name="page" value="pp-content-overview"/>
                    <?php
                    foreach ($this->content_overview_filters() as $select_id => $select_name) {
                        echo $this->content_overview_filter_options($select_id, $select_name, $this->user_filters);
                    } ?>
                </form>

                <form method="GET" id="pp-content-filters-hidden">
                    <input type="hidden" name="page" value="pp-content-overview"/>
                    <input type="hidden" name="post_status" value=""/>
                    <input type="hidden" name="cat" value=""/>
                    <input type="hidden" name="author" value=""/>
                    <input type="hidden" name="orderby" value="<?php echo (isset($_GET['orderby']) && !empty($_GET['orderby'])) ? $_GET['orderby'] : 'post_date'; ?>"/>
                    <input type="hidden" name="order" value="<?php echo (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC'; ?>"/>
                    <?php
                    foreach ($this->content_overview_filters() as $select_id => $select_name) {
                        echo '<input type="hidden" name="' . esc_attr($select_name) . '" value="" />';
                    } ?>
                    <input type="submit" id="post-query-clear" value="<?php esc_attr(_e('Reset', 'publishpress')); ?>"
                           class="button-secondary button"/>
                </form>
            </div><!-- /alignleft actions -->

            <div class="print-box" style="float:right; margin-right: 30px;"><!-- Print link -->
                <a href="#" id="print_link"><span
                        class="pp-icon pp-icon-print"></span>&nbsp;<?php esc_attr(_e('Print', 'publishpress')); ?>
                </a>
            </div>
            <div class="clear"></div>
        </div><!-- /tablenav -->
        <?php
    }

    public function content_overview_filters()
    {
        $select_filter_names = [];


        $select_filter_names['post_status'] = 'post_status';

        if (isset($this->module->options->post_types['post']) && $this->module->options->post_types['post'] == 'on') {
            $select_filter_names['cat'] = 'cat';
        }

        $select_filter_names['author'] = 'author';
        $select_filter_names['ptype']  = 'ptype';

        return apply_filters('PP_Content_Overview_filter_names', $select_filter_names);
    }

    public function content_overview_filter_options($select_id, $select_name, $filters)
    {
        switch ($select_id) {
            case 'post_status':
                $post_statuses = $this->get_post_statuses();
                ?>
                <select id="post_status" name="post_status"><!-- Status selectors -->
                    <option value=""><?php _e('View all statuses', 'publishpress'); ?></option>
                    <?php
                    foreach ($post_statuses as $post_status) {
                        echo "<option value='" . esc_attr($post_status->slug) . "' " . selected(
                                $post_status->slug,
                                $filters['post_status']
                            ) . ">" . esc_html($post_status->name) . "</option>";
                    }
                    ?>
                </select>
                <?php
                break;

            case 'cat':
                $categoryId = isset($filters['cat']) ? (int)$filters['cat'] : 0;
                ?>
                <select id="filter_category" name="cat">
                    <option value=""><?php _e('View all categories', 'publishpress'); ?></option>
                    <?php
                    if (!empty($categoryId)) {
                        $category = get_term($categoryId, 'category');

                        echo "<option value='" . esc_attr($categoryId) . "' selected='selected'>" . esc_html($category->name) . "</option>";
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
                        <?php _e('All authors', 'publishpress'); ?>
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

            case 'ptype':
                $selectedPostType = isset($filters['ptype']) ? sanitize_text_field($filters['ptype']) : '';
                ?>
                <select id="filter_post_type" name="ptype">
                    <option value=""><?php _e('View all post types', 'publishpress'); ?></option>
                    <?php
                    $postTypes = $this->get_selected_post_types();
                    foreach ($postTypes as $postType) {
                        $postTypeObject = get_post_type_object($postType);
                        echo '<option value="' . esc_attr($postType) . '" ' . selected($selectedPostType, $postType) . '>' . esc_html($postTypeObject->label) . '</option>';
                    }
                    ?>
                </select>
                <?php
                break;

            default:
                do_action('PP_Content_Overview_filter_display', $select_id, $select_name, $filters);
                break;
        }
    }

    /**
     * Prints the stories in a single term in the content overview.
     *
     * @param object $term The term to print.
     * @param string $postType
     */
    public function printPostForPostType($term, $postType)
    {
        $order           = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        $orderBy         = (isset($_GET['orderby']) && !empty($_GET['orderby'])) ? $_GET['orderby'] : 'post_date';

        $this->user_filters['orderby'] = sanitize_key($orderBy);
        $this->user_filters['order']   = sanitize_key($order);

        $posts           = $this->getPostsForPostType($term, $postType, $this->user_filters);
        $postTypeObject  = get_post_type_object($postType);
        $sortableColumns = $this->getSortableColumns();

        if (!empty($posts)) {
            // Don't display the message for $no_matching_posts
            $this->no_matching_posts = false;
        } ?>

        <div class="postbox<?php echo (!empty($posts)) ? ' postbox-has-posts' : ''; ?>">
            <div class="handlediv" title="<?php esc_attr(_e('Click to toggle', 'publishpress')); ?>">
                <br/></div>
            <h3 class=\'hndle\'><span><?php echo $postTypeObject->label; ?></span></h3>
            <div class="inside">
                <?php if (!empty($posts)) : ?>
                    <table class="widefat post fixed content-overview striped" cellspacing="0">
                        <thead>
                        <tr>
                            <?php foreach ((array)$this->columns as $key => $name): ?>
                                <?php
                                $newOrder = 'ASC';
                                if ($key === $orderBy) :
                                    $newOrder = ($order === 'ASC') ? 'DESC' : 'ASC';
                                endif;
                                ?>
                                <th scope="col" id="<?php echo esc_attr(sanitize_key($key)); ?>"
                                    class="manage-column column-<?php echo esc_attr(
                                        sanitize_key($key)
                                    ); ?>">
                                    <?php if (in_array($key, $sortableColumns)) : ?>
                                        <a href="<?php echo add_query_arg(['orderby' => esc_attr(sanitize_key($key)), 'order' => esc_attr($newOrder)]); ?>">
                                            <?php echo esc_html($name); ?>
                                            <?php if ($orderBy === $key) : ?>
                                                <?php $orderIconClass = $order === 'DESC' ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>
                                                <i class="dashicons <?php echo esc_attr($orderIconClass); ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($name); ?>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tfoot></tfoot>
                        <tbody>
                        <?php
                        foreach ($posts as $post) {
                            $this->print_post($post, $term);
                        } ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="message info">
                        <p><?php _e(
                                'There are no posts in the range or filter specified.',
                                'publishpress'
                            ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function getSortableColumns()
    {
        $sortableColumns = [
            'post_title',
            'post_date',
            'post_modified',
        ];

        return apply_filters('publishpress_content_overview_sortable_columns', $sortableColumns);
    }

    /**
     * Get all of the posts for a given term based on filters
     *
     * @param object $term The term we're getting posts for
     * @param string $postType
     * @param array $args
     *
     * @return array $term_posts An array of post objects for the term
     */
    public function getPostsForPostType($term, $postType, $args = null)
    {
        $defaults = [
            'post_status'    => null,
            'author'         => null,
            'posts_per_page' => apply_filters('PP_Content_Overview_max_query', 200),
        ];

        $args = array_merge($defaults, $args);

        if ($postType === 'post' && !empty($term)) {
            // Filter to the term and any children if it's hierarchical
            $arg_terms = [
                $term->term_id,
            ];

            if (is_object($term) && property_exists($term, 'term_id')) {
                $arg_terms = array_merge($arg_terms, get_term_children($term->term_id, $this->taxonomy_used));
            }

            $args['tax_query'] = [
                [
                    'taxonomy' => $this->taxonomy_used,
                    'field'    => 'id',
                    'terms'    => $arg_terms,
                    'operator' => 'IN',
                ],
            ];
        }

        $args['post_type'] = $postType;

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

        // Filter by post_author if it's set
        if ($args['author'] === '0') {
            unset($args['author']);
        }

        // Order the post list by publishing date.
        if (!isset($args['orderby'])) {
            $args['orderby'] = 'post_date';
            $args['order']   = 'ASC';
        }

        // Filter for an end user to implement any of their own query args
        $args = apply_filters('PP_Content_Overview_posts_query_args', $args);

        add_filter('posts_where', [$this, 'posts_where_range']);
        $term_posts_query_results = new WP_Query($args);
        remove_filter('posts_where', [$this, 'posts_where_range']);

        $term_posts = [];
        while ($term_posts_query_results->have_posts()) {
            $term_posts_query_results->the_post();

            global $post;

            $term_posts[] = $post;
        }



        return $term_posts;
    }

    /**
     * Prints a single post within a term in the content overview.
     *
     * @param object $post The post to print.
     * @param object $parent_term The top-level term to which this post belongs.
     */
    public function print_post($post, $parent_term)
    {
        ?>
        <tr id='post-<?php echo esc_attr($post->ID); ?>' valign="top">
            <?php foreach ((array)$this->columns as $key => $name) {
                echo '<td>';
                if (method_exists($this, 'column_' . $key)) {
                    $method = 'column_' . $key;
                    echo $this->$method($post, $parent_term);
                } else {
                    echo $this->column_default($post, $key, $parent_term);
                }

                echo '</td>';
            } ?>
        </tr>
        <?php
    }

    /**
     * Default callback for producing the HTML for a term column's single post value
     * Includes a filter other modules can hook into
     *
     * @param object $post The post we're displaying
     * @param string $column_name Name of the column, as registered with register_columns
     * @param object $parent_term The parent term for the term column
     *
     * @return string $output Output value for the term column
     * @since 0.7
     *
     */
    public function column_default($post, $column_name)
    {
        // Hook for other modules to get data into columns
        $column_value = null;

        /**
         * @deprecated
         */
        $column_value = apply_filters('PP_Content_Overview_term_column_value', $column_name, $post, null);

        /**
         * @param string $column_name
         * @param WP_Post $post
         *
         * @return string
         */
        $column_value = apply_filters('publishpress_content_overview_column_value', $column_name, $post);

        if (!is_null($column_value) && $column_value != $column_name) {
            return $column_value;
        }

        if (strpos($column_name, '_pp_editorial_meta_') === 0) {
            $column_value = get_post_meta($post->ID, $column_name, true);

            if (empty($column_value)) {
                return '<span>' . __('None', 'publishpress') . '</span>';
            }

            return $column_value;
        }

        switch ($column_name) {
            case 'post_status':
                $status_name = $this->get_post_status_friendly_name($post->post_status);

                return $status_name;
                break;
            case 'post_author':
                $post_author = get_userdata($post->post_author);
                $author_name = is_object($post_author) ? $post_author->display_name : '';
                $author_name = apply_filters('the_author', $author_name);

                $author_name = apply_filters('publishpress_content_overview_author_column', $author_name, $post);

                return $author_name;
                break;
            case 'post_date':
                $output = get_the_time(get_option('date_format'), $post->ID) . '<br />';
                $output .= get_the_time(get_option('time_format'), $post->ID);

                return $output;
                break;
            case 'post_modified':
                $modified_time_gmt = strtotime($post->post_modified_gmt . " GMT");

                return $this->timesince($modified_time_gmt);
                break;
            default:
                break;
        }

        $meta_options = isset($this->terms_options[$column_name])
            ? $this->terms_options[$column_name]
            : null;

        if (is_null($meta_options)) {
            return '';
        }

        $column_type  = $meta_options['type'];
        $column_value = get_post_meta($post->ID, "_pp_editorial_meta_{$column_type}_{$column_name}", true);

        return apply_filters("pp_editorial_metadata_{$column_type}_render_value_html", $column_value);
    }

    /**
     * Filter the WP_Query so we can get a range of posts
     *
     * @param string $where The original WHERE SQL query string
     *
     * @return string $where Our modified WHERE query string
     */
    public function posts_where_range($where = '')
    {
        global $wpdb;

        $beginning_date = date('Y-m-d', strtotime($this->user_filters['start_date']));
        $end_day        = $this->user_filters['number_days'];
        $ending_date    = date("Y-m-d", strtotime("+" . $end_day . " days", strtotime($beginning_date)));
        $where          = $where . $wpdb->prepare(
                " AND ($wpdb->posts.post_date >= %s AND $wpdb->posts.post_date < %s)",
                $beginning_date,
                $ending_date
            );

        return $where;
    }

    /**
     * Prepare the data for the title term column
     *
     * @since 0.7
     */
    public function column_post_title($post, $parent_term)
    {
        $post_title = _draft_or_post_title($post->ID);

        $post_type_object = get_post_type_object($post->post_type);
        $can_edit_post    = current_user_can($post_type_object->cap->edit_post, $post->ID);
        if ($can_edit_post) {
            $output = '<strong><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html(
                    $post_title
                ) . '</a></strong>';
        } else {
            $output = '<strong>' . esc_html($post_title) . '</strong>';
        }

        // Edit or Trash or View
        $output       .= '<div class="row-actions">';
        $item_actions = [];

        if ($can_edit_post) {
            $item_actions['edit'] = '<a title="' . esc_attr(
                    __(
                        'Edit this post',
                        'publishpress'
                    )
                ) . '" href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html__(
                    'Edit',
                    'publishpress'
                ) . '</a>';
        }

        if (EMPTY_TRASH_DAYS > 0 && current_user_can($post_type_object->cap->delete_post, $post->ID)) {
            $item_actions['trash'] = '<a class="submitdelete" title="' . esc_attr(
                    __(
                        'Move this item to the Trash',
                        'publishpress'
                    )
                ) . '" href="' . esc_url(get_delete_post_link($post->ID)) . '">' . esc_html__(
                    'Trash',
                    'publishpress'
                ) . '</a>';
        }

        // Display a View or a Preview link depending on whether the post has been published or not
        if (in_array($post->post_status, ['publish'])) {
            $item_actions['view'] = '<a href="' . esc_url(get_permalink($post->ID)) . '" title="' . esc_attr(
                    sprintf(
                        __(
                            'View &#8220;%s&#8221;',
                            'publishpress'
                        ),
                        $post_title
                    )
                ) . '" rel="permalink">' . __('View', 'publishpress') . '</a>';
        } elseif ($can_edit_post) {
            $item_actions['previewpost'] = '<a href="' . esc_url(
                    apply_filters(
                        'preview_post_link',
                        add_query_arg('preview', 'true', get_permalink($post->ID)),
                        $post
                    )
                ) . '" title="' . esc_attr(
                    sprintf(
                        __('Preview &#8220;%s&#8221;', 'publishpress'),
                        $post_title
                    )
                ) . '" rel="permalink">' . __('Preview', 'publishpress') . '</a>';
        }

        $item_actions = apply_filters('PP_Content_Overview_item_actions', $item_actions, $post->ID);
        if (count($item_actions)) {
            $output .= '<div class="row-actions">';
            $html   = '';
            foreach ($item_actions as $class => $item_action) {
                $html .= '<span class="' . esc_attr($class) . '">' . $item_action . '</span> | ';
            }
            $output .= rtrim($html, '| ');
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Get the filters for the current user for the content overview display, or insert the default
     * filters if not already set.
     *
     * @return array The filters for the current user, or the default filters if the current user has none.
     */
    public function get_user_filters()
    {
        $current_user = wp_get_current_user();
        $user_filters = [];
        $user_filters = $this->get_user_meta($current_user->ID, self::USERMETA_KEY_PREFIX . 'filters', true);

        // If usermeta didn't have filters already, insert defaults into DB
        if (empty($user_filters)) {
            $user_filters = $this->update_user_filters();
        }

        return $user_filters;
    }

    public function searchAuthors()
    {
        header('Content-type: application/json;');

        if (!wp_verify_nonce($_GET['nonce'], 'content_overview_filter_nonce')) {
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

        global $wpdb;

        $queryResult = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT u.ID as 'id', u.display_name as 'text'
                FROM {$wpdb->posts} as p
                INNER JOIN {$wpdb->users} as u ON p.post_author = u.ID
                WHERE u.display_name LIKE %s
                ORDER BY 2
                LIMIT 20",
                '%' . $wpdb->esc_like($queryText) . '%'
            )
        );

        echo json_encode($queryResult);
        exit;
    }

    public function searchCategories()
    {
        header('Content-type: application/json;');

        if (!wp_verify_nonce($_GET['nonce'], 'content_overview_filter_nonce')) {
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

        echo json_encode($queryResult);
        exit;
    }
}
