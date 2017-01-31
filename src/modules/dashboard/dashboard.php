<?php
/**
 * @package PublishPress
 * @author PressShack
 *
 * Copyright (c) 2017 PressShack
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

if (!class_exists('PP_Dashboard')) {
    /**
     * class PP_Dashboard
     * All of the code for the dashboard widgets from PublishPress
     *
     * Dashboard widgets currently:
     * - Post Status Widget - Shows numbers for all (custom|post) statuses
     * - Posts I'm Following Widget - Show the headlines with edit links for posts I'm following
     *
     * @todo for 0.7
     * - Update the Posts I'm Following widget to use new activity class
     */
    class PP_Dashboard extends PP_Module
    {

        public $widgets;

    /**
     * Load the PP_Dashboard class as an PublishPress module
     */
    public function __construct()
    {

        // Register the module with PublishPress
        $this->module_url = $this->get_module_url(__FILE__);
        $args             = array(
            'title'                => __('Dashboard Widgets', 'publishpress'),
            'short_description'    => __('Track your content from the WordPress dashboard.', 'publishpress'),
            'extended_description' => __('Enable dashboard widgets to quickly get an overview of what state your content is in.', 'publishpress'),
            'module_url'           => $this->module_url,
            'icon_class'           => 'dashicons dashicons-layout',
            'slug'                 => 'dashboard',
            'post_type_support'    => 'pp_dashboard',
            'default_options'      => array(
                'enabled'            => 'on',
                'post_status_widget' => 'on',
                'my_posts_widget'    => 'on',
                'notepad_widget'     => 'on',
            ),
            'configure_page_cb'   => 'print_configure_view',
            'configure_link_text' => __('Widget Options', 'publishpress'),
            'options_page'        => true,
        );
        $this->module = PublishPress()->register_module('dashboard', $args);
    }

    /**
     * Initialize all of the class' functionality if its enabled
     */
    public function init()
    {
        $this->widgets = new stdClass;

        if ('on' == $this->module->options->notepad_widget) {
            require_once dirname(__FILE__) . '/widgets/dashboard-notepad.php';
            $this->widgets->notepad_widget = new PP_Dashboard_Notepad_Widget;
            $this->widgets->notepad_widget->init();
        }

        // Add the widgets to the dashboard
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));

        // Register our settings
        add_action('admin_init', array($this, 'register_settings'));
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
            // Migrate whether dashboard widgets were enabled or not
            if ($enabled = get_option('publishpress_dashboard_widgets_enabled')) {
                $enabled = 'on';
            } else {
                $enabled = 'off';
            }
            $publishpress->update_module_option($this->module->name, 'enabled', $enabled);
            delete_option('publishpress_dashboard_widgets_enabled');
            // Migrate whether the post status widget was on
            if ($post_status_widget = get_option('publishpress_post_status_widget_enabled')) {
                $post_status_widget = 'on';
            } else {
                $post_status_widget = 'off';
            }
            $publishpress->update_module_option($this->module->name, 'post_status_widget', $post_status_widget);
            delete_option('publishpress_post_status_widget_enabled');
            // Migrate whether the my posts widget was on
            if ($my_posts_widget = get_option('publishpress_myposts_widget_enabled')) {
                $my_posts_widget = 'on';
            } else {
                $my_posts_widget = 'off';
            }
            $publishpress->update_module_option($this->module->name, 'post_status_widget', $my_posts_widget);
            delete_option('publishpress_myposts_widget_enabled');
            // Delete legacy option
            delete_option('publishpress_quickpitch_widget_enabled');

            // Technically we've run this code before so we don't want to auto-install new data
            $publishpress->update_module_option($this->module->name, 'loaded_once', true);
        }
    }

    /**
     * Add PublishPress dashboard widgets to the WordPress admin dashboard
     */
    public function add_dashboard_widgets()
    {

        // Only show dashboard widgets for Contributor or higher
        if (!current_user_can('edit_posts')) {
            return;
        }

        wp_enqueue_style('publishpress-dashboard-css', $this->module_url . 'lib/dashboard.css', false, PUBLISHPRESS_VERSION, 'all');

        // Set up Post Status widget but, first, check to see if it's enabled
        if ($this->module->options->post_status_widget == 'on') {
            wp_add_dashboard_widget('post_status_widget', __('Unpublished Content', 'publishpress'), array($this, 'post_status_widget'));
        }

        // Set up the Notepad widget if it's enabled
        if ('on' == $this->module->options->notepad_widget) {
            wp_add_dashboard_widget('notepad_widget', __('Notepad', 'publishpress'), array($this->widgets->notepad_widget, 'notepad_widget'));
        }

        // Add the MyPosts widget, if enabled
        if ($this->module->options->my_posts_widget == 'on' && $this->module_enabled('notifications')) {
            wp_add_dashboard_widget('myposts_widget', __('Posts I\'m Following', 'publishpress'), array($this, 'myposts_widget'));
        }
    }

    /**
     * Creates Post Status widget
     * Display an at-a-glance view of post counts for all (post|custom) statuses in the system
     *
     * @todo Support custom post types
     */
    public function post_status_widget()
    {
        global $publishpress;

        $statuses   = $this->get_post_statuses();
        $statuses[] = (object)array(
                'name' => __('Scheduled', 'publishpress'),
                'description' => '',
                'slug' => 'future',
            );
        $statuses = apply_filters('pp_dashboard_post_status_widget_statuses', $statuses);
        // If custom statuses are enabled, we'll output a link to edit the terms just below the post counts
        if ($this->module_enabled('custom_status')) {
            $edit_custom_status_url = add_query_arg('page', 'pp-custom-status-settings', get_admin_url(null, 'admin.php'));
        }

        ?>
        <p class="sub"><?php _e('Posts at a Glance', 'publishpress') ?></p>

        <div class="table">
            <table>
                <tbody>
                    <?php $post_count = wp_count_posts('post');
        ?>
                    <?php foreach ($statuses as $status) : ?>
                        <?php $filter_link = $this->filter_posts_link($status->slug);
        ?>
                        <tr>
                            <td class="b">
                                <a href="<?php echo esc_url($filter_link);
        ?>">
                                    <?php
                                    $slug = $status->slug;
        echo esc_html($post_count->$slug);
        ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($filter_link);
        ?>"><?php echo esc_html($status->name);
            ?></a>
                                </td>
                            </tr>

                        <?php endforeach;
            ?>
                    </tbody>
                </table>
                <?php if (isset($edit_custom_status_url)) : ?>
                    <span class="small"><a href="<?php echo esc_url($edit_custom_status_url);
            ?>"><?php _e('Edit Custom Statuses', 'publishpress');
            ?></a></span>
                <?php endif;
            ?>
            </div>
            <?php

        }

        /**
         * Creates My Posts widget
         * Shows a list of the "posts you're following" sorted by most recent activity.
         */
        public function myposts_widget()
        {
            global $publishpress;

            $myposts = $publishpress->notifications->get_user_following_posts();

            ?>
            <div class="pp-myposts">
                <?php if (!empty($myposts)) : ?>

                    <?php foreach ($myposts as $post) : ?>
                        <?php
                        $url = esc_url(get_edit_post_link($post->ID));
            $title           = esc_html($post->post_title);
            ?>
                        <li>
                            <h4><a href="<?php echo $url ?>" title="<?php _e('Edit this post', 'publishpress') ?>"><?php echo $title;
            ?></a></h4>
                            <span class="pp-myposts-timestamp"><?php _e('This post was last updated on', 'publishpress') ?> <?php echo get_the_time('F j, Y \\a\\t g:i a', $post) ?></span>
                        </li>
                    <?php endforeach;
            ?>
                <?php else : ?>
                    <p><?php _e('Sorry! You\'re not subscribed to any posts!', 'publishpress') ?></p>
                <?php endif;
            ?>
            </div>
            <?php

        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         *
         * @since 0.7
         */
        public function register_settings()
        {
            add_settings_section($this->module->options_group_name . '_general', false, '__return_false', $this->module->options_group_name);
            add_settings_field('post_status_widget', __('Post Status Widget', 'publishpress'), array($this, 'settings_post_status_widget_option'), $this->module->options_group_name, $this->module->options_group_name . '_general');
            add_settings_field('my_posts_widget', __('Posts I\'m Following', 'publishpress'), array($this, 'settings_my_posts_widget_option'), $this->module->options_group_name, $this->module->options_group_name . '_general');
            add_settings_field('notepad_widget', __('Notepad', 'publishpress'), array($this, 'settings_notepad_widget_option'), $this->module->options_group_name, $this->module->options_group_name . '_general');
        }

        /**
         * Enable or disable the Post Status Widget for the WP dashboard
         *
         * @since 0.7
         */
        public function settings_post_status_widget_option()
        {
            $options = array(
                'off' => __('Disabled', 'publishpress'),
                'on' => __('Enabled', 'publishpress'),
            );
            echo '<select id="post_status_widget" name="' . $this->module->options_group_name . '[post_status_widget]">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"';
                echo selected($this->module->options->post_status_widget, $value);
                echo '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }

        /**
         * Enable or disable the Posts I'm Following Widget for the WP dashboard
         *
         * @since 0.7
         */
        public function settings_my_posts_widget_option()
        {
            global $publishpress;
            $options = array(
                'off' => __('Disabled', 'publishpress'),
                'on' => __('Enabled', 'publishpress'),
            );
            echo '<select id="my_posts_widget" name="' . $this->module->options_group_name . '[my_posts_widget]"';
            // Notifications module has to be enabled for the My Posts widget to work
            if (!$this->module_enabled('notifications')) {
                echo ' disabled="disabled"';
                $this->module->options->my_posts_widget = 'off';
            }
            echo '>';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"';
                echo selected($this->module->options->my_posts_widget, $value);
                echo '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            if (!$this->module_enabled('notifications')) {
                echo '&nbsp;&nbsp;&nbsp;<span class="description">' . __('The notifications module will need to be enabled for this widget to display.', 'publishpress');
            }
        }

        /**
         * Enable or disable the Notepad widget for the dashboard
         *
         * @since 0.8
         */
        public function settings_notepad_widget_option()
        {
            $options = array(
                'off' => __('Disabled', 'publishpress'),
                'on' => __('Enabled', 'publishpress'),
            );
            echo '<select id="notepad_widget" name="' . $this->module->options_group_name . '[notepad_widget]">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"';
                echo selected($this->module->options->notepad_widget, $value);
                echo '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }

        /**
         * Validate the field submitted by the user
         *
         * @since 0.7
         */
        public function settings_validate($new_options)
        {

            // Follow whitelist validation for modules
            if (array_key_exists('post_status_widget', $new_options) && $new_options['post_status_widget'] != 'on') {
                $new_options['post_status_widget'] = 'off';
            }

            if (array_key_exists('my_posts_widget', $new_options) && $new_options['my_posts_widget'] != 'on') {
                $new_options['my_posts_widget'] = 'off';
            }

            return $new_options;
        }

        /**
         * Settings page for the dashboard
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            ?>
            <form class="basic-settings" action="<?php echo esc_url(menu_page_url($this->module->settings_slug, false));
            ?>" method="post">
                <?php settings_fields($this->module->options_group_name);
            ?>
                <?php do_settings_sections($this->module->options_group_name);
            ?>
                <?php
                    echo '<input id="publishpress_module_name" name="publishpress_module_name" type="hidden" value="' . esc_attr($this->module->name) . '" />';
            ?>
                <p class="submit"><?php submit_button(null, 'primary', 'submit', false);
            ?></p>
            </form>
            <?php

        }
    }
} // END - !class_exists('PP_Dashboard')
