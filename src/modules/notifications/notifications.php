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

if (! defined('PP_NOTIFICATION_USE_CRON')) {
    define('PP_NOTIFICATION_USE_CRON', false);
}

if (!class_exists('PP_Notifications')) {
    /**
     * class PP_Notifications
     * Email notifications for PublishPress and more
     */
    class PP_Notifications extends PP_Module
    {

        // Taxonomy name used to store users following posts
        public $following_users_taxonomy = 'following_users';
        // Taxonomy name used to store user groups following posts
        public $following_usergroups_taxonomy = PP_User_Groups::taxonomy_key;

        public $module;

        public $edit_post_subscriptions_cap = 'edit_post_subscriptions';

        /**
         * Register the module with PublishPress but don't do anything else
         */
        public function __construct()
        {

            // Register the module with PublishPress
            $this->module_url = $this->get_module_url(__FILE__);
            $args             = array(
                'title'                => __('Notifications', 'publishpress'),
                'short_description'    => __('With email notifications, you can keep everyone updated about what’s happening with your content.', 'publishpress'),
                'extended_description' => __('With email notifications, you can keep everyone updated about what’s happening with a given content. Each status change or editorial comment sends out an email notification to users subscribed to a post. User groups can be used to manage who receives notifications on what.', 'publishpress'),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-email',
                'slug'                 => 'notifications',
                'default_options'      => array(
                    'enabled'    => 'on',
                    'post_types' => array(
                        'post' => 'on',
                        'page' => 'on',
                    ),
                    'always_notify_admin' => 'off',
                ),
                'configure_page_cb' => 'print_configure_view',
                'post_type_support' => 'pp_notification',
                'autoload'          => false,
                'settings_help_tab' => array(
                    'id'      => 'pp-notifications-overview',
                    'title'   => __('Overview', 'publishpress'),
                    'content' => __('<p>Notifications ensure you keep up to date with progress your most important content. Users can be subscribed to notifications on a post one by one or by selecting user groups.</p><p>When enabled, email notifications can be sent when a post changes status or an editorial comment is left by a writer or an editor.</p>', 'publishpress'),
                ),
                'settings_help_sidebar' => __('<p><strong>For more information:</strong></p><p><a href="https://pressshack.com/features/notifications/">Notifications Documentation</a></p><p><a href="http://wordpress.org/tags/publishpress?forum_id=10">PublishPress Forum</a></p><p><a href="https://github.com/danielbachhuber/Edit-Flow">PublishPress on Github</a></p>', 'publishpress'),
                'add_menu' => true,
            );
            $this->module = PublishPress()->register_module('notifications', $args);
        }

        /**
         * Initialize the notifications class if the plugin is enabled
         */
        public function init()
        {

            // Register our taxonomies for managing relationships
            $this->register_taxonomies();

            // Allow users to use a different user capability for editing post subscriptions
            $this->edit_post_subscriptions_cap = apply_filters('pp_edit_post_subscriptions_cap', $this->edit_post_subscriptions_cap);

            // Set up metabox and related actions
            add_action('add_meta_boxes', array($this, 'add_post_meta_box'));

            // Saving post actions
            // self::save_post_subscriptions() is hooked into transition_post_status so we can ensure usergroup data
            // is properly saved before sending notifs
            add_action('transition_post_status', array($this, 'save_post_subscriptions'), 0, 3);
            add_action('transition_post_status', array($this, 'notification_status_change'), 10, 3);
            add_action('pp_post_insert_editorial_comment', array($this, 'notification_comment'));
            add_action('delete_user',  array($this, 'delete_user_action'));
            add_action('pp_send_scheduled_email', array($this, 'send_single_email'), 10, 4);

            add_action('admin_init', array($this, 'register_settings'));

            // Javascript and CSS if we need it
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

            // Add a "Follow" link to posts
            if (apply_filters('pp_notifications_show_follow_link', true)) {
                // A little extra JS for the follow button
                add_action('admin_head', array($this, 'action_admin_head_follow_js'));
                // Manage Posts
                add_filter('post_row_actions', array($this, 'filter_post_row_actions'), 10, 2);
                add_filter('page_row_actions', array($this, 'filter_post_row_actions'), 10, 2);
                // Calendar and Story Budget
                add_filter('pp_calendar_item_actions', array($this, 'filter_post_row_actions'), 10, 2);
                add_filter('pp_story_budget_item_actions', array($this, 'filter_post_row_actions'), 10, 2);
            }

            //Ajax for saving notifiction updates
            add_action('wp_ajax_save_notifications', array($this, 'ajax_save_post_subscriptions'));
            add_action('wp_ajax_pp_notifications_user_post_subscription', array($this, 'handle_user_post_subscription'));
        }

        /**
         * Load the capabilities onto users the first time the module is run
         *
         * @since 0.7
         */
        public function install()
        {

            // Add necessary capabilities to allow management of notifications
            $notifications_roles = array(
                'administrator' => array('edit_post_subscriptions'),
                'editor'        => array('edit_post_subscriptions'),
                'author'        => array('edit_post_subscriptions'),
            );

            foreach ($notifications_roles as $role => $caps) {
                $this->add_caps_to_role($role, $caps);
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
                // Migrate whether notifications were enabled or not
                if ($enabled = get_option('publishpress_notifications_enabled')) {
                    $enabled = 'on';
                } else {
                    $enabled = 'off';
                }
                $publishpress->update_module_option($this->module->name, 'enabled', $enabled);
                delete_option('publishpress_notifications_enabled');
                // Migrate whether to always notify the admin
                if ($always_notify_admin = get_option('publishpress_always_notify_admin')) {
                    $always_notify_admin = 'on';
                } else {
                    $always_notify_admin = 'off';
                }
                $publishpress->update_module_option($this->module->name, 'always_notify_admin', $always_notify_admin);
                delete_option('publishpress_always_notify_admin');

                // Technically we've run this code before so we don't want to auto-install new data
                $publishpress->update_module_option($this->module->name, 'loaded_once', true);
            }
        }

        /**
         * Register the taxonomies we use to manage relationships
         *
         * @since 0.7
         *
         * @uses register_taxonomy()
         */
        public function register_taxonomies()
        {

            // Load the currently supported post types so we only register against those
            $supported_post_types = $this->get_post_types_for_module($this->module);

            $args = array(
                'hierarchical'           => false,
                'update_count_callback'  => '_update_post_term_count',
                'label'                  => false,
                'query_var'              => false,
                'rewrite'                => false,
                'public'                 => false,
                'show_ui'                => false
            );
            register_taxonomy($this->following_users_taxonomy, $supported_post_types, $args);
        }

        /**
         * Enqueue necessary admin scripts
         *
         * @since 0.7
         *
         * @uses wp_enqueue_script()
         */
        public function enqueue_admin_scripts()
        {
            if ($this->is_whitelisted_functional_view()) {
                wp_enqueue_script('jquery-listfilterizer');
                wp_enqueue_script('jquery-quicksearch');
                wp_enqueue_script('publishpress-notifications-js', $this->module_url . 'lib/notifications.js', array('jquery', 'jquery-listfilterizer', 'jquery-quicksearch'), PUBLISHPRESS_VERSION, true);
            }
        }

        /**
         * Enqueue necessary admin styles, but only on the proper pages
         *
         * @since 0.7
         *
         * @uses wp_enqueue_style()
         */
        public function enqueue_admin_styles()
        {
            if ($this->is_whitelisted_functional_view() || $this->is_whitelisted_settings_view()) {
                wp_enqueue_style('jquery-listfilterizer');
                wp_enqueue_style('publishpress-notifications-css', $this->module->module_url . 'lib/notifications.css', false, PUBLISHPRESS_VERSION);
            }
        }

        /**
         * JS required for the Follow link to work
         *
         * @since 0.8
         */
        public function action_admin_head_follow_js()
        {
            ?>
    <script type='text/Javascript'>
    jQuery(document).ready(function($) {
        /**
         * Action to Follow / Unfollow posts on the manage posts screen
         */
        $('.wp-list-table, #pp-calendar-view, #pp-story-budget-wrap').on('click', '.pp_follow_link a', function(e){

            e.preventDefault();

            var link = $(this);

            $.ajax({
                type : 'GET',
                url : link.attr('href'),
                success : function(data) {
                    if ('success' == data.status) {
                        link.attr('href', data.message.link);
                        link.attr('title', data.message.title);
                        link.text(data.message.text);
                    }
                    // @todo expose the error somehow
                }
            });
            return false;
        });
    });
    </script><?php

        }

        /**
         * Add a "Follow" link to supported post types Manage Posts view
         *
         * @since 0.8
         *
         * @param array      $actions   Any existing item actions
         * @param int|object $post      Post id or object
         * @return array     $actions   The follow link has been appended
         */
        public function filter_post_row_actions($actions, $post)
        {
            $post = get_post($post);

            if (! in_array($post->post_type, $this->get_post_types_for_module($this->module))) {
                return $actions;
            }

            if (! current_user_can($this->edit_post_subscriptions_cap) || ! current_user_can('edit_post', $post->ID)) {
                return $actions;
            }

            $parts                     = $this->get_follow_action_parts($post);
            $actions['pp_follow_link'] = '<a title="' . esc_attr($parts['title']) . '" href="' . esc_url($parts['link']) . '">' . $parts['text'] . '</a>';

            return $actions;
        }

        /**
         * Get an action parts for a user to follow or unfollow a post
         *
         * @since 0.8
         */
        private function get_follow_action_parts($post)
        {
            $args = array(
                    'action'     => 'pp_notifications_user_post_subscription',
                    'post_id'    => $post->ID,
                );
            $following_users = $this->get_following_users($post->ID);
            if (in_array(wp_get_current_user()->user_login, $following_users)) {
                $args['method'] = 'unfollow';
                $title_text     = __('Click to unfollow updates to this post', 'publishpress');
                $follow_text    = __('Following', 'publishpress');
            } else {
                $args['method'] = 'follow';
                $title_text     = __('Follow updates to this post', 'publishpress');
                $follow_text    = __('Follow', 'publishpress');
            }

            // wp_nonce_url() has encoding issues: http://core.trac.wordpress.org/ticket/20771
            $args['_wpnonce'] = wp_create_nonce('pp_notifications_user_post_subscription');

            return array(
                    'title'     => $title_text,
                    'text'      => $follow_text,
                    'link'      => add_query_arg($args, admin_url('admin-ajax.php')),
                );
        }

        /**
         * Add the subscriptions meta box to relevant post types
         */
        public function add_post_meta_box()
        {
            if (!current_user_can($this->edit_post_subscriptions_cap)) {
                return;
            }

            $usergroup_post_types = $this->get_post_types_for_module($this->module);
            foreach ($usergroup_post_types as $post_type) {
                add_meta_box('publishpress-notifications', __('Notifications', 'publishpress'), array($this, 'notifications_meta_box'), $post_type, 'advanced');
            }
        }

        /**
         * Outputs box used to subscribe users and usergroups to Posts
         *
         * @todo add_cap to set subscribers for posts; default to Admin and editors
         */
        public function notifications_meta_box()
        {
            global $post, $post_ID, $publishpress;

            ?>
            <div id="pp-post_following_box">
                <a name="subscriptions"></a>

                <p><?php _e('Select the users and user groups that should receive notifications when the status of this post is updated or when an editorial comment is added.', 'publishpress');
            ?></p>
                <div id="pp-post_following_users_box">
                    <h4><?php _e('Users', 'publishpress');
            ?></h4>
                    <?php
                    $followers = $this->get_following_users($post->ID, 'id');
            $select_form_args  = array(
                        'list_class' => 'pp-post_following_list',
                    );
            $this->users_select_form($followers, $select_form_args);
            ?>
                </div>

                <?php if ($this->module_enabled('user_groups') && in_array($this->get_current_post_type(), $this->get_post_types_for_module($publishpress->user_groups->module))): ?>
                <div id="pp-post_following_usergroups_box">
                    <h4><?php _e('User Groups', 'publishpress') ?></h4>
                    <?php
                    $following_usergroups = $this->get_following_usergroups($post->ID, 'ids');
            $publishpress->user_groups->usergroups_select_form($following_usergroups);
            ?>
                </div>
                <?php endif;
            ?>
                <div class="clear"></div>
                <input type="hidden" name="pp-save_followers" value="1" /> <?php // Extra protection against autosaves ?>
                <?php wp_nonce_field('save_user_usergroups', 'pp_notifications_nonce', false);
            ?>
            </div>

            <?php

        }

        /**
         * Called when a notification editorial metadata checkbox is checked. Handles saving of a user/usergroup to a post.
         */
        public function ajax_save_post_subscriptions()
        {
            global $publishpress;

            // Verify nonce
            if (!wp_verify_nonce($_POST['_nonce'], 'save_user_usergroups')) {
                die(__("Nonce check failed. Please ensure you can add users or user groups to a post.", 'publishpress'));
            }

            $post_id            = (int)$_POST['post_id'];
            $post               = get_post($post_id);
            $user_usergroup_ids = array_map('intval', $_POST['user_group_ids']);
            if ((!wp_is_post_revision($post_id) && !wp_is_post_autosave($post_id))  && current_user_can($this->edit_post_subscriptions_cap)) {
                if ($_POST['pp_notifications_name'] === 'pp-selected-users[]') {
                    $this->save_post_following_users($post, $user_usergroup_ids);
                } elseif ($_POST['pp_notifications_name'] == 'following_usergroups[]') {
                    if ($this->module_enabled('user_groups') && in_array(get_post_type($post_id), $this->get_post_types_for_module($publishpress->user_groups->module))) {
                        $this->save_post_following_usergroups($post, $user_usergroup_ids);
                    }
                }
            }
            die();
        }

        /**
         * Handle a request to update a user's post subscription
         *
         * @since 0.8
         */
        public function handle_user_post_subscription()
        {
            if (! wp_verify_nonce($_GET['_wpnonce'], 'pp_notifications_user_post_subscription')) {
                $this->print_ajax_response('error', $this->module->messages['nonce-failed']);
            }

            if (! current_user_can($this->edit_post_subscriptions_cap)) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            $post = get_post(($post_id = $_GET['post_id']));

            if (! $post) {
                $this->print_ajax_response('error', $this->module->messages['missing-post']);
            }

            if ('follow' == $_GET['method']) {
                $retval = $this->follow_post_user($post, get_current_user_id());
            } else {
                $retval = $this->unfollow_post_user($post, get_current_user_id());
            }

            if (is_wp_error($retval)) {
                $this->print_ajax_response('error', $retval->get_error_message());
            }

            $this->print_ajax_response('success', (object)$this->get_follow_action_parts($post));
        }


        /**
         * Called when post is saved. Handles saving of user/usergroup followers
         *
         * @param int $post ID of the post
         */
        public function save_post_subscriptions($new_status, $old_status, $post)
        {
            global $publishpress;
            // only if has edit_post_subscriptions cap
            if ((!wp_is_post_revision($post) && !wp_is_post_autosave($post)) && isset($_POST['pp-save_followers']) && current_user_can($this->edit_post_subscriptions_cap)) {
                $users      = isset($_POST['pp-selected-users']) ? $_POST['pp-selected-users'] : array();
                $usergroups = isset($_POST['following_usergroups']) ? $_POST['following_usergroups'] : array();
                $this->save_post_following_users($post, $users);
                if ($this->module_enabled('user_groups') && in_array($this->get_current_post_type(), $this->get_post_types_for_module($publishpress->user_groups->module))) {
                    $this->save_post_following_usergroups($post, $usergroups);
                }
            }
        }

        /**
         * Sets users to follow specified post
         *
         * @param int $post ID of the post
         */
        public function save_post_following_users($post, $users = null)
        {
            if (!is_array($users)) {
                $users = array();
            }

            // Add current user to following users
            $user = wp_get_current_user();
            if ($user && apply_filters('pp_notification_auto_subscribe_current_user', true, 'subscription_action')) {
                $users[] = $user->ID;
            }

            // Add post author to following users
            if (apply_filters('pp_notification_auto_subscribe_post_author', true, 'subscription_action')) {
                $users[] = $post->post_author;
            }

            $users = array_unique(array_map('intval', $users));

            $follow = $this->follow_post_user($post, $users, false);
        }

        /**
         * Sets usergroups to follow specified post
         *
         * @param int $post ID of the post
         * @param array $usergroups Usergroups to follow posts
         */
        public function save_post_following_usergroups($post, $usergroups = null)
        {
            if (!is_array($usergroups)) {
                $usergroups = array();
            }
            $usergroups = array_map('intval', $usergroups);

            $follow = $this->follow_post_usergroups($post, $usergroups, false);
        }

        /**
         * Set up and send post status change notification email
         */
        public function notification_status_change($new_status, $old_status, $post)
        {
            global $publishpress;

            // Kill switch for notification
            if (! apply_filters('pp_notification_status_change', $new_status, $old_status, $post) || ! apply_filters("pp_notification_{$post->post_type}_status_change", $new_status, $old_status, $post)) {
                return false;
            }

            $supported_post_types = $this->get_post_types_for_module($this->module);
            if (!in_array($post->post_type, $supported_post_types)) {
                return;
            }

            // No need to notify if it's a revision, auto-draft, or if post status wasn't changed
            $ignored_statuses = apply_filters('pp_notification_ignored_statuses', array($old_status, 'inherit', 'auto-draft'), $post->post_type);

            if (!in_array($new_status, $ignored_statuses)) {

                // Get current user
                $current_user = wp_get_current_user();

                $post_author = get_userdata($post->post_author);
                //$duedate = $publishpress->post_metadata->get_post_meta($post->ID, 'duedate', true);

                $blogname = get_option('blogname');

                $body  = '';

                $post_id    = $post->ID;
                $post_title = pp_draft_or_post_title($post_id);
                $post_type  = get_post_type_object($post->post_type)->labels->singular_name;

                if (0 != $current_user->ID) {
                    $current_user_display_name = $current_user->display_name;
                    $current_user_email        = sprintf('(%s)', $current_user->user_email);
                } else {
                    $current_user_display_name = __('WordPress Scheduler', 'publishpress');
                    $current_user_email        = '';
                }

                // Email subject and first line of body
                // Set message subjects according to what action is being taken on the Post
                if ($old_status == 'new' || $old_status == 'auto-draft') {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] New %2$s Created: "%3$s"', 'publishpress'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
                    $body .= sprintf(__('A new %1$s (#%2$s "%3$s") was created by %4$s %5$s', 'publishpress'), $post_type, $post_id, $post_title, $current_user->display_name, $current_user->user_email) . "\r\n";
                } elseif ($new_status == 'trash') {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] %2$s Trashed: "%3$s"', 'publishpress'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
                    $body .= sprintf(__('%1$s #%2$s "%3$s" was moved to the trash by %4$s %5$s', 'publishpress'), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email) . "\r\n";
                } elseif ($old_status == 'trash') {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] %2$s Restored (from Trash): "%3$s"', 'publishpress'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
                    $body .= sprintf(__('%1$s #%2$s "%3$s" was restored from trash by %4$s %5$s', 'publishpress'), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email) . "\r\n";
                } elseif ($new_status == 'future') {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] %2$s Scheduled: "%3$s"'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email 6. scheduled date  */
                    $body .= sprintf(__('%1$s #%2$s "%3$s" was scheduled by %4$s %5$s.  It will be published on %6$s'), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email, $this->get_scheduled_datetime($post)) . "\r\n";
                } elseif ($new_status == 'publish') {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] %2$s Published: "%3$s"', 'publishpress'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
                    $body .= sprintf(__('%1$s #%2$s "%3$s" was published by %4$s %5$s', 'publishpress'), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email) . "\r\n";
                } elseif ($old_status == 'publish') {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] %2$s Unpublished: "%3$s"', 'publishpress'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
                    $body .= sprintf(__('%1$s #%2$s "%3$s" was unpublished by %4$s %5$s', 'publishpress'), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email) . "\r\n";
                } else {
                    /* translators: 1: site name, 2: post type, 3. post title */
                    $subject = sprintf(__('[%1$s] %2$s Status Changed for "%3$s"', 'publishpress'), $blogname, $post_type, $post_title);
                    /* translators: 1: post type, 2: post id, 3. post title, 4. user name, 5. user email */
                    $body .= sprintf(__('Status was changed for %1$s #%2$s "%3$s" by %4$s %5$s', 'publishpress'), $post_type, $post_id, $post_title, $current_user_display_name, $current_user_email) . "\r\n";
                }

                /* translators: 1: date, 2: time, 3: timezone */
                $body .= sprintf(__('This action was taken on %1$s at %2$s %3$s', 'publishpress'), date_i18n(get_option('date_format')), date_i18n(get_option('time_format')), get_option('timezone_string')) . "\r\n";

                $old_status_friendly_name = $this->get_post_status_friendly_name($old_status);
                $new_status_friendly_name = $this->get_post_status_friendly_name($new_status);

                // Email body
                $body .= "\r\n";
                /* translators: 1: old status, 2: new status */
                $body .= sprintf(__('%1$s => %2$s', 'publishpress'), $old_status_friendly_name, $new_status_friendly_name);
                $body .= "\r\n\r\n";

                $body .= "--------------------\r\n\r\n";

                $body .= sprintf(__('== %s Details ==', 'publishpress'), $post_type) . "\r\n";
                $body .= sprintf(__('Title: %s', 'publishpress'), $post_title) . "\r\n";
                if (! empty($post_author)) {
                    /* translators: 1: author name, 2: author email */
                    $body .= sprintf(__('Author: %1$s (%2$s)', 'publishpress'), $post_author->display_name, $post_author->user_email) . "\r\n";
                }

                $edit_link = htmlspecialchars_decode(get_edit_post_link($post_id));
                if ($new_status != 'publish') {
                    $view_link = add_query_arg(array('preview' => 'true'), wp_get_shortlink($post_id));
                } else {
                    $view_link = htmlspecialchars_decode(get_permalink($post_id));
                }
                $body .= "\r\n";
                $body .= __('== Actions ==', 'publishpress') . "\r\n";
                $body .= sprintf(__('Add editorial comment: %s', 'publishpress'), $edit_link . '#editorialcomments/add') . "\r\n";
                $body .= sprintf(__('Edit: %s', 'publishpress'), $edit_link) . "\r\n";
                $body .= sprintf(__('View: %s', 'publishpress'), $view_link) . "\r\n";

                $body .= $this->get_notification_footer($post);

                $this->send_email('status-change', $post, $subject, $body);
            }
        }

        /**
         * Set up and set editorial comment notification email
         */
        public function notification_comment($comment)
        {
            $post = get_post($comment->comment_post_ID);

            $supported_post_types = $this->get_post_types_for_module($this->module);
            if (!in_array($post->post_type, $supported_post_types)) {
                return;
            }

            // Kill switch for notification
            if (! apply_filters('pp_notification_editorial_comment', $comment, $post)) {
                return false;
            }

            $user         = get_userdata($post->post_author);
            $current_user = wp_get_current_user();

            $post_id    = $post->ID;
            $post_type  = get_post_type_object($post->post_type)->labels->singular_name;
            $post_title = pp_draft_or_post_title($post_id);

            // Check if this a reply
            //$parent_ID = isset($comment->comment_parent_ID) ? $comment->comment_parent_ID : 0;
            //if($parent_ID) $parent = get_comment($parent_ID);

            // Set user to follow post, but make it filterable
            if (apply_filters('pp_notification_auto_subscribe_current_user', true, 'comment')) {
                $this->follow_post_user($post, (int) $current_user->ID);
            }

            // Set the post author to follow the post but make it filterable
            if (apply_filters('pp_notification_auto_subscribe_post_author', true, 'comment')) {
                $this->follow_post_user($post, (int) $post->post_author);
            }

            $blogname = get_option('blogname');

            /* translators: 1: blog name, 2: post title */
            $subject = sprintf(__('[%1$s] New Editorial Comment: "%2$s"', 'publishpress'), $blogname, $post_title);

            /* translators: 1: post id, 2: post title, 3. post type */
            $body  = sprintf(__('A new editorial comment was added to %3$s #%1$s "%2$s"', 'publishpress'), $post_id, $post_title, $post_type) . "\r\n\r\n";
            /* translators: 1: comment author, 2: author email, 3: date, 4: time */
            $body .= sprintf(__('%1$s (%2$s) said on %3$s at %4$s:', 'publishpress'), $current_user->display_name, $current_user->user_email, mysql2date(get_option('date_format'), $comment->comment_date), mysql2date(get_option('time_format'), $comment->comment_date)) . "\r\n";
            $body .= "\r\n" . $comment->comment_content . "\r\n";

            // @TODO: mention if it was a reply
            /*
            if($parent) {

            }
            */

            $body .= "\r\n--------------------\r\n";

            $edit_link = htmlspecialchars_decode(get_edit_post_link($post_id));
            $view_link = htmlspecialchars_decode(get_permalink($post_id));

            $body .= "\r\n";
            $body .= __('== Actions ==', 'publishpress') . "\r\n";
            $body .= sprintf(__('Reply: %s', 'publishpress'), $edit_link . '#editorialcomments/reply/' . $comment->comment_ID) . "\r\n";
            $body .= sprintf(__('Add editorial comment: %s', 'publishpress'), $edit_link . '#editorialcomments/add') . "\r\n";
            $body .= sprintf(__('Edit: %s', 'publishpress'), $edit_link) . "\r\n";
            $body .= sprintf(__('View: %s', 'publishpress'), $view_link) . "\r\n";

            $body .= "\r\n" . sprintf(__('You can see all editorial comments on this %s here: ', 'publishpress'), $post_type) . "\r\n";
            $body .= $edit_link . "#editorialcomments" . "\r\n\r\n";

            $body .= $this->get_notification_footer($post);

            $this->send_email('comment', $post, $subject, $body);
        }

            public function get_notification_footer($post)
            {
                $body  = "";
                $body .= "\r\n--------------------\r\n";
                $body .= sprintf(__('You are receiving this email because you are subscribed to "%s".', 'publishpress'), pp_draft_or_post_title($post->ID));
                $body .= "\r\n";
                $body .= sprintf(__('This email was sent %s.', 'publishpress'), date('r'));
                $body .= "\r\n \r\n";
                $body .= get_option('blogname') . " | " . get_bloginfo('url') . " | " . admin_url('/') . "\r\n";
                return $body;
            }

        /**
         * send_email()
         */
        public function send_email($action, $post, $subject, $message, $message_headers = '')
        {

            // Get list of email recipients -- set them CC
            $recipients = $this->_get_notification_recipients($post, true);

            if ($recipients && ! is_array($recipients)) {
                $recipients = explode(',', $recipients);
            }

            $subject         = apply_filters('pp_notification_send_email_subject', $subject, $action, $post);
            $message         = apply_filters('pp_notification_send_email_message', $message, $action, $post);
            $message_headers = apply_filters('pp_notification_send_email_message_headers', $message_headers, $action, $post);

            if (PP_NOTIFICATION_USE_CRON) {
                $this->schedule_emails($recipients, $subject, $message, $message_headers);
            } elseif (!empty($recipients)) {
                foreach ($recipients as $recipient) {
                    $this->send_single_email($recipient, $subject, $message, $message_headers);
                }
            }
        }

        /**
         * Schedules emails to be sent in succession
         *
         * @param mixed $recipients Individual email or array of emails
         * @param string $subject Subject of the email
         * @param string $message Body of the email
         * @param string $message_headers. (optional) Message headers
         * @param int $time_offset (optional) Delay in seconds per email
         */
        public function schedule_emails($recipients, $subject, $message, $message_headers = '', $time_offset = 1)
        {
            $recipients = (array) $recipients;

            $send_time = time();

            foreach ($recipients as $recipient) {
                wp_schedule_single_event($send_time, 'pp_send_scheduled_email', array($recipient, $subject, $message, $message_headers));
                $send_time += $time_offset;
            }
        }

        /**
         * Sends an individual email
         *
         * @param mixed $to Email to send to
         * @param string $subject Subject of the email
         * @param string $message Body of the email
         * @param string $message_headers. (optional) Message headers
         */
        public function send_single_email($to, $subject, $message, $message_headers = '')
        {
            wp_mail($to, $subject, $message, $message_headers);
        }

        /**
         * Returns a list of recipients for a given post
         *
         * @param $post object
         * @param $string bool Whether to return recipients as comma-delimited string or array
         * @return string or array of recipients to receive notification
         */
        private function _get_notification_recipients($post, $string = false)
        {
            global $publishpress;

            $post_id = $post->ID;
            if (!$post_id) {
                return;
            }

            $authors    = array();
            $admins     = array();
            $recipients = array();

            // Email all admins, if enabled
            if ('on' == $this->module->options->always_notify_admin) {
                $admins[] = get_option('admin_email');
            }

            $usergroup_users = array();
            if ($this->module_enabled('user_groups')) {
                // Get following users and usergroups
                $usergroups = $this->get_following_usergroups($post_id, 'ids');
                foreach ((array)$usergroups as $usergroup_id) {
                    $usergroup = $publishpress->user_groups->get_usergroup_by('id', $usergroup_id);
                    foreach ((array)$usergroup->user_ids as $user_id) {
                        $usergroup_user = get_user_by('id', $user_id);
                        if ($usergroup_user && is_user_member_of_blog($user_id)) {
                            $usergroup_users[] = $usergroup_user->user_email;
                        }
                    }
                }
            }

            $users = $this->get_following_users($post_id, 'user_email');

            // Merge arrays and filter any duplicates
            $recipients = array_merge($authors, $admins, $users, $usergroup_users);
            $recipients = array_unique($recipients);

            // Process the recipients for this email to be sent
            foreach ($recipients as $key => $user_email) {
                // Get rid of empty email entries
                if (empty($recipients[$key])) {
                    unset($recipients[$key]);
                }
                // Don't send the email to the current user unless we've explicitly indicated they should receive it
                if (false === apply_filters('pp_notification_email_current_user', false) && wp_get_current_user()->user_email == $user_email) {
                    unset($recipients[$key]);
                }
            }

            // Filter to allow further modification of recipients
            $recipients = apply_filters('pp_notification_recipients', $recipients, $post, $string);

            // If string set to true, return comma-delimited
            if ($string && is_array($recipients)) {
                return implode(',', $recipients);
            } else {
                return $recipients;
            }
        }

        /**
         * Set a user or users to follow a post
         *
         * @param int|object         $post      Post object or ID
         * @param string|array       $users     User or users to subscribe to post updates
         * @param bool               $append    Whether users should be added to following_users list or replace existing list
         *
         * @return true|WP_Error     $response  True on success, WP_Error on failure
         */
        public function follow_post_user($post, $users, $append = true)
        {
            $post = get_post($post);
            if (! $post) {
                return new WP_Error('missing-post', $this->module->messages['missing-post']);
            }

            if (! is_array($users)) {
                $users = array($users);
            }

            $user_terms = array();
            foreach ($users as $user) {
                if (is_int($user)) {
                    $user = get_user_by('id', $user);
                } elseif (is_string($user)) {
                    $user = get_user_by('login', $user);
                }

                if (! is_object($user)) {
                    continue;
                }

                $name = $user->user_login;

                // Add user as a term if they don't exist
                $term = $this->add_term_if_not_exists($name, $this->following_users_taxonomy);

                if (! is_wp_error($term)) {
                    $user_terms[] = $name;
                }
            }
            $set = wp_set_object_terms($post->ID, $user_terms, $this->following_users_taxonomy, $append);

            if (is_wp_error($set)) {
                return $set;
            } else {
                return true;
            }
        }

        /**
         * Removes user from following_users taxonomy for the given Post,
         * so they no longer receive future notifications.
         *
         * @param object             $post      Post object or ID
         * @param int|string|array   $users     One or more users to unfollow from the post
         * @return true|WP_Error     $response  True on success, WP_Error on failure
         */
         public function unfollow_post_user($post, $users)
         {
             $post = get_post($post);
             if (! $post) {
                 return new WP_Error('missing-post', $this->module->messages['missing-post']);
             }

             if (! is_array($users)) {
                 $users = array($users);
             }

             $terms = get_the_terms($post->ID, $this->following_users_taxonomy);
             if (is_wp_error($terms)) {
                 return $terms;
             }

             $user_terms = wp_list_pluck($terms, 'slug');
             foreach ($users as $user) {
                 if (is_int($user)) {
                     $user = get_user_by('id', $user);
                 } elseif (is_string($user)) {
                     $user = get_user_by('login', $user);
                 }

                 if (! is_object($user)) {
                     continue;
                 }

                 $key = array_search($user->user_login, $user_terms);
                 if (false !== $key) {
                     unset($user_terms[$key]);
                 }
             }
             $set = wp_set_object_terms($post->ID, $user_terms, $this->following_users_taxonomy, false);

             if (is_wp_error($set)) {
                 return $set;
             } else {
                 return true;
             }
         }

        /**
         * follow_post_usergroups()
         *
         */
        public function follow_post_usergroups($post, $usergroups = 0, $append = true)
        {
            if (!$this->module_enabled('user_groups')) {
                return;
            }

            $post_id = (is_int($post)) ? $post : $post->ID;
            if (!is_array($usergroups)) {
                $usergroups = array($usergroups);
            }

            // make sure each usergroup id is an integer and not a number stored as a string
            foreach ($usergroups as $key => $usergroup) {
                $usergroups[$key] = intval($usergroup);
            }

            wp_set_object_terms($post_id, $usergroups, $this->following_usergroups_taxonomy, $append);
            return;
        }

        /**
         * Removes users that are deleted from receiving future notifications (i.e. makes them unfollow posts FOREVER!)
         *
         * @param $id int ID of the user
         */
        public function delete_user_action($id)
        {
            if (!$id) {
                return;
            }

            // get user data
            $user = get_userdata($id);

            if ($user) {
                // Delete term from the following_users taxonomy
                $user_following_term = get_term_by('name', $user->user_login, $this->following_users_taxonomy);
                if ($user_following_term) {
                    wp_delete_term($user_following_term->term_id, $this->following_users_taxonomy);
                }
            }
            return;
        }

        /**
         * Add user as a term if they aren't already
         * @param $term string term to be added
         * @param $taxonomy string taxonomy to add term to
         * @return WP_error if insert fails, true otherwise
         */
        public function add_term_if_not_exists($term, $taxonomy)
        {
            if (!term_exists($term, $taxonomy)) {
                $args = array('slug' => sanitize_title($term));
                return wp_insert_term($term, $taxonomy, $args);
            }
            return true;
        }

        /**
         * Gets a list of the users following the specified post
         *
         * @param int $post_id The ID of the post
         * @param string $return The field to return
         * @return array $users Users following the specified posts
         */
        public function get_following_users($post_id, $return = 'user_login')
        {

            // Get following_users terms for the post
            $users = wp_get_object_terms($post_id, $this->following_users_taxonomy, array('fields' => 'names'));

            // Don't have any following users
            if (!$users || is_wp_error($users)) {
                return array();
            }

            // if just want user_login, return as is
            if ($return == 'user_login') {
                return $users;
            }

            foreach ((array)$users as $key => $user) {
                switch ($user) {
                    case is_int($user):
                        $search = 'id';
                        break;
                    case is_email($user):
                        $search = 'email';
                        break;
                    default:
                        $search = 'login';
                        break;
                }
                $new_user = get_user_by($search, $user);
                if (! $new_user || ! is_user_member_of_blog($new_user->ID)) {
                    unset($users[$key]);
                    continue;
                }
                switch ($return) {
                    case 'user_login':
                        $users[$key] = $new_user->user_login;
                        break;
                    case 'id':
                        $users[$key] = $new_user->ID;
                        break;
                    case 'user_email':
                        $users[$key] = $new_user->user_email;
                        break;
                }
            }
            if (!$users || is_wp_error($users)) {
                $users = array();
            }
            return $users;
        }

        /**
         * Gets a list of the usergroups that are following specified post
         *
         * @param int $post_id
         * @return array $usergroups All of the usergroup slugs
         */
        public function get_following_usergroups($post_id, $return = 'all')
        {
            global $publishpress;

            // Workaround for the fact that get_object_terms doesn't return just slugs
            if ($return == 'slugs') {
                $fields = 'all';
            } else {
                $fields = $return;
            }

            $usergroups = wp_get_object_terms($post_id, $this->following_usergroups_taxonomy, array('fields' => $fields));

            if ($return == 'slugs') {
                $slugs = array();
                foreach ($usergroups as $usergroup) {
                    $slugs[] = $usergroup->slug;
                }
                $usergroups = $slugs;
            }
            return $usergroups;
        }

        /**
         * Gets a list of posts that a user is following
         *
         * @param string|int $user user_login or id of user
         * @param array $args
         * @return array $posts Posts a user is following
         */
        public function get_user_following_posts($user = 0, $args = null)
        {
            if (!$user) {
                $user = (int) wp_get_current_user()->ID;
            }

            if (is_int($user)) {
                $user = get_userdata($user)->user_login;
            }

            $post_args = array(
                'tax_query' => array(
                    array(
                        'taxonomy' => $this->following_users_taxonomy,
                        'field'    => 'slug',
                        'terms'    => $user,
                    )
                ),
                'posts_per_page' => '10',
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'post_status'    => 'any',
            );
            $post_args = apply_filters('pp_user_following_posts_query_args', $post_args);
            $posts     = get_posts($post_args);
            return $posts;
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
            add_settings_field('post_types', __('Post types for notifications:', 'publishpress'), array($this, 'settings_post_types_option'), $this->module->options_group_name, $this->module->options_group_name . '_general');
            add_settings_field('always_notify_admin', __('Always notify blog admin', 'publishpress'), array($this, 'settings_always_notify_admin_option'), $this->module->options_group_name, $this->module->options_group_name . '_general');
        }

        /**
         * Chose the post types for notifications
         *
         * @since 0.7
         */
        public function settings_post_types_option()
        {
            global $publishpress;
            $publishpress->settings->helper_option_custom_post_type($this->module);
        }

        /**
         * Option for whether the blog admin email address should be always notified or not
         *
         * @since 0.7
         */
        public function settings_always_notify_admin_option()
        {
            $options = array(
                'off' => __('Disabled', 'publishpress'),
                'on'  => __('Enabled', 'publishpress'),
            );
            echo '<select id="always_notify_admin" name="' . $this->module->options_group_name . '[always_notify_admin]">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"';
                echo selected($this->module->options->always_notify_admin, $value);
                echo '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }

        /**
         * Validate our user input as the settings are being saved
         *
         * @since 0.7
         */
        public function settings_validate($new_options)
        {

            // Whitelist validation for the post type options
            if (!isset($new_options['post_types'])) {
                $new_options['post_types'] = array();
            }
            $new_options['post_types'] = $this->clean_post_type_options($new_options['post_types'], $this->module->post_type_support);

            // Whitelist validation for the 'always_notify_admin' options
            if (!isset($new_options['always_notify_admin']) || $new_options['always_notify_admin'] != 'on') {
                $new_options['always_notify_admin'] = 'off';
            }

            return $new_options;
        }

        /**
         * Settings page for notifications
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
            ?><a class="cancel-settings-link" href="<?php echo PUBLISHPRESS_SETTINGS_PAGE;
            ?>"><?php _e('Back to PublishPress', 'publishpress');
            ?></a></p>
            </form>
            <?php

        }

        /**
        * Gets a simple phrase containing the formatted date and time that the post is scheduled for.
        *
        * @since 0.8
        *
        * @param  obj    $post               Post object
        * @return str    $scheduled_datetime The scheduled datetime in human-readable format
        */
        private function get_scheduled_datetime($post)
        {
            $scheduled_ts = strtotime($post->post_date);

            $date = date_i18n(get_option('date_format'), $scheduled_ts);
            $time = date_i18n(get_option('time_format'), $scheduled_ts);

            return sprintf(__('%1$s at %2$s', 'publishpress'), $date, $time);
        }
    }
}
