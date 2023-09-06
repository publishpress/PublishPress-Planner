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

use PublishPress\Notifications\Traits\Dependency_Injector;

if (! class_exists('PP_Custom_Status')) {
    /**
     * class PP_Custom_Status
     * Custom statuses make it simple to define the different stages in your publishing workflow.
     *
     * @todo for v0.7
     * - Improve the copy
     * - Thoroughly test what happens when the default post statuses 'Draft' and 'Pending Review' no longer exist
     * - Ensure all of the form processing uses our messages functionality
     */
    #[\AllowDynamicProperties]
    class PP_Custom_Status extends PP_Module
    {
        use Dependency_Injector;

        const MODULE_NAME = 'custom_status';

        const SETTINGS_SLUG = 'pp-custom-status-settings';

        const STATUS_PUBLISH = 'publish';

        const STATUS_PRIVATE = 'private';

        const STATUS_SCHEDULED = 'future';

        const STATUS_PENDING = 'pending';

        const STATUS_DRAFT = 'draft';

        const DEFAULT_COLOR = '#655997';

        const DEFAULT_STATUS = 'draft';

        public $module;

        private $custom_statuses_cache = [];

        // This is taxonomy name used to store all our custom statuses
        const taxonomy_key = 'post_status';

        /**
         * Register the module with PublishPress but don't do anything else
         */
        public function __construct()
        {
            global $publishpress;

            $this->module_url = $this->get_module_url(__FILE__);
            // Register the module with PublishPress
            $args = [
                'title' => __('Statuses', 'publishpress'),
                'short_description' => false,
                'extended_description' => false,
                'module_url' => $this->module_url,
                'icon_class' => 'dashicons dashicons-tag',
                'slug' => 'custom-status',
                'default_options' => [
                    'enabled' => 'on',
                    'always_show_dropdown' => 'on',
                    'post_types' => [
                        'post' => 'on',
                        'page' => 'on',
                    ],
                ],
                'post_type_support' => 'pp_custom_statuses', // This has been plural in all of our docs
                'configure_page_cb' => 'print_configure_view',
                'configure_link_text' => __('Edit Statuses', 'publishpress'),
                'messages' => [
                    'status-added' => __('Post status created.', 'publishpress'),
                    'status-updated' => __('Post status updated.', 'publishpress'),
                    'status-missing' => __("Post status doesn't exist.", 'publishpress'),
                    'default-status-changed' => __('Default post status has been changed.', 'publishpress'),
                    'term-updated' => __("Post status updated.", 'publishpress'),
                    'status-deleted' => __('Post status deleted.', 'publishpress'),
                    'status-position-updated' => __("Status order updated.", 'publishpress'),
                ],
                'autoload' => false,
                'settings_help_tab' => [
                    'id' => 'pp-custom-status-overview',
                    'title' => __('Overview', 'publishpress'),
                    'content' => __(
                        '<p>PublishPress’s custom statuses allow you to define the most important stages of your editorial workflow. Out of the box, WordPress only offers “Draft” and “Pending Review” as post states. With custom statuses, you can create your own post states like “In Progress”, “Pitch”, or “Waiting for Edit” and keep or delete the originals. You can also drag and drop statuses to set the best order for your workflow.</p><p>Custom statuses are fully integrated into the rest of PublishPress and the WordPress admin. On the calendar and content overview, you can filter your view to see only posts of a specific post state. Furthermore, email notifications can be sent to a specific group of users when a post changes state.</p>',
                        'publishpress'
                    ),
                ],
                'settings_help_sidebar' => __(
                    '<p><strong>For more information:</strong></p><p><a href="https://publishpress.com/features/custom-statuses/">Custom Status Documentation</a></p><p><a href="https://github.com/ostraining/PublishPress">PublishPress on Github</a></p>',
                    'publishpress'
                ),
                'options_page' => true,
            ];
            $this->module = PublishPress()->register_module(self::MODULE_NAME, $args);
        }

        /**
         * Initialize the PP_Custom_Status class if the module is active
         */
        public function init()
        {
            global $publishpress;

            // Register custom statuses as a taxonomy
            $this->register_custom_statuses();

            if (is_admin()) {
                // Register our settings
                add_action('admin_init', [$this, 'register_settings']);

                // Load CSS and JS resources that we probably need
                add_action('admin_enqueue_scripts', [$this, 'action_admin_enqueue_scripts']);
                add_action('admin_notices', [$this, 'no_js_notice']);
                add_action('admin_print_scripts', [$this, 'post_admin_header']);
                add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);

                // Methods for handling the actions of creating, making default, and deleting post stati
                add_action('admin_init', [$this, 'handle_add_custom_status']);
                add_action('admin_init', [$this, 'handle_edit_custom_status']);
                add_action('admin_init', [$this, 'handle_delete_custom_status']);
                add_action('wp_ajax_update_status_positions', [$this, 'handle_ajax_update_status_positions']);

                // Hook to add the status column to Manage Posts

                add_filter('manage_posts_columns', [$this, '_filter_manage_posts_columns']);
                add_action('manage_posts_custom_column', [$this, '_filter_manage_posts_custom_column']);

                // We need these for pages (http://core.trac.wordpress.org/browser/tags/3.3.1/wp-admin/includes/class-wp-posts-list-table.php#L283)
                add_filter('manage_pages_columns', [$this, '_filter_manage_posts_columns']);
                add_action('manage_pages_custom_column', [$this, '_filter_manage_posts_custom_column']);
            }

            // These seven-ish methods are temporary fixes for solving bugs in WordPress core
            add_filter('preview_post_link', [$this, 'fix_preview_link_part_one']);
            add_filter('post_link', [$this, 'fix_preview_link_part_two'], 10, 3);
            add_filter('page_link', [$this, 'fix_preview_link_part_two'], 10, 3);
            add_filter('post_type_link', [$this, 'fix_preview_link_part_two'], 10, 3);
            add_filter('get_sample_permalink', [$this, 'fix_get_sample_permalink'], 10, 5);
            add_filter('get_sample_permalink_html', [$this, 'fix_get_sample_permalink_html'], 9, 5);
            add_filter('post_row_actions', [$this, 'fix_post_row_actions'], 10, 2);
            add_filter('page_row_actions', [$this, 'fix_post_row_actions'], 10, 2);

            add_filter('wp_insert_post_data', [$this, 'filter_insert_post_data'], 10, 2);
        }

        /**
         * Returns the list of default custom statuses.
         *
         * @return array
         */
        protected function get_default_terms()
        {
            return [
                'pitch' => [
                    'term' => __('Pitch', 'publishpress'),
                    'args' => [
                        'slug' => 'pitch',
                        'description' => __('Idea proposed; waiting for acceptance.', 'publishpress'),
                        'position' => 1,
                        'color' => '#cc0000',
                        'icon' => 'dashicons-post-status',
                    ],
                ],
                'assigned' => [
                    'term' => __('Assigned', 'publishpress'),
                    'args' => [
                        'slug' => 'assigned',
                        'description' => __('Post idea assigned to writer.', 'publishpress'),
                        'position' => 2,
                        'color' => '#00bcc5',
                        'icon' => 'dashicons-admin-users',
                    ],
                ],
                'in-progress' => [
                    'term' => __('In Progress', 'publishpress'),
                    'args' => [
                        'slug' => 'in-progress',
                        'description' => __('Writer is working on the post.', 'publishpress'),
                        'position' => 3,
                        'color' => '#ccc500',
                        'icon' => 'dashicons-format-status',
                    ],
                ]
            ];
        }

        /**
         * Create the default set of custom statuses the first time the module is loaded
         *
         * @since 0.7
         */
        public function install()
        {
            $default_terms = $this->get_default_terms();
            $roles = ['administrator', 'author', 'editor', 'contributor'];

            // Okay, now add the default statuses to the db if they don't already exist
            foreach ($default_terms as $term) {
                if (! term_exists($term['term'], self::taxonomy_key)) {
                    $this->add_custom_status($term['term'], $term['args']);
                }
            }

            // Add basic capabilities for each post status
            $default_terms['publish'] = [];
            foreach ($default_terms as $termSlug => $data) {
                foreach ($roles as $roleName) {
                    $role = get_role($roleName);
                    if (! empty($role)) {
                        $role->add_cap('status_change_' . str_replace('-', '_', $termSlug));

                        if ('publish' === $termSlug) {
                            $role->add_cap('status_change_private');
                            $role->add_cap('status_change_future');
                        }
                    }
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
                // Migrate dropdown visibility option
                if ($dropdown_visible = get_option('publishpress_status_dropdown_visible')) {
                    $dropdown_visible = 'on';
                } else {
                    $dropdown_visible = 'off';
                }
                $publishpress->update_module_option($this->module->name, 'always_show_dropdown', $dropdown_visible);
                delete_option('publishpress_status_dropdown_visible');

                // Technically we've run this code before so we don't want to auto-install new data
                $publishpress->update_module_option($this->module->name, 'loaded_once', true);
            }

            // Upgrade path to v0.7.4
            if (version_compare($previous_version, '0.7.4', '<')) {
                // Custom status descriptions become base64_encoded, instead of maybe json_encoded.
                $this->upgrade_074_term_descriptions(self::taxonomy_key);
            }
        }

        /**
         * Makes the call to register_post_status to register the user's custom statuses.
         * Also unregisters draft and pending, in case the user doesn't want them.
         *
         * @param array $args
         */
        public function register_custom_statuses($args = [])
        {
            global $wp_post_statuses;

            if ($this->disable_custom_statuses_for_post_type()) {
                return;
            }

            // Register new taxonomy so that we can store all our fancy new custom statuses (or is it stati?)
            if (! taxonomy_exists(self::taxonomy_key)) {
                register_taxonomy(
                    self::taxonomy_key,
                    'post',
                    [
                        'hierarchical' => false,
                        'update_count_callback' => '_update_post_term_count',
                        'label' => __('Statuses', 'publishpress'),
                        'query_var' => false,
                        'rewrite' => false,
                        'show_ui' => false,
                    ]
                );
            }

            if (function_exists('register_post_status')) {
                $custom_statuses = $this->get_custom_statuses($args, ! is_admin());

                // Unfortunately, register_post_status() doesn't accept a
                // post type argument, so we have to register the post
                // statuses for all post types. This results in
                // all post statuses for a post type appearing at the top
                // of manage posts if there is a post with the status
                foreach ($custom_statuses as $status) {
                    // Ignore core statuses, defined as stdClass
                    if ('stdClass' === get_class($status)) {
                        continue;
                    }

                    $postStatusArgs = [
                        'label' => $status->name,
                        'protected' => true,
                        'date_floating' => true,
                        '_builtin' => false,
                        'label_count' => _n_noop(
                            "{$status->name} <span class='count'>(%s)</span>",
                            "{$status->name} <span class='count'>(%s)</span>"
                        ),
                    ];

                    $postStatusArgs = apply_filters('publishpress_new_custom_status_args', $postStatusArgs, $status);

                    register_post_status($status->slug, $postStatusArgs);
                }
            }
        }

        /**
         * Whether custom post statuses should be disabled for this post type.
         * Used to stop custom statuses from being registered for post types that don't support them.
         *
         * @return bool
         * @since 0.7.5
         */
        public function disable_custom_statuses_for_post_type($post_type = null)
        {
            global $pagenow;

            // Only allow deregistering on 'edit.php' and 'post.php'
            if (! in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])) {
                return false;
            }


            if (is_null($post_type)) {
                $post_type = $this->get_current_post_type();
            }

            // Always allow for the notification workflows
            if (defined('PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW')) {
                if (PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post_type) {
                    return false;
                }
            }

            if ($post_type && ! in_array($post_type, $this->get_post_types_for_module($this->module))) {
                return true;
            }

            return false;
        }

        /**
         * Enqueue Javascript resources that we need in the admin:
         * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
         * - jQuery Sortable plugin is used for drag and dropping custom statuses
         * - We have other custom code for JS niceties
         */
        public function action_admin_enqueue_scripts()
        {
            global $publishpress;


            if ($this->disable_custom_statuses_for_post_type()) {
                return;
            }

            // Load Javascript we need to use on the configuration views (jQuery Sortable)
            if ($this->is_whitelisted_settings_view($this->module->name)) {
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_script(
                    'publishpress-custom-status-configure',
                    $this->module_url . 'lib/custom-status-configure.js',
                    ['jquery', 'jquery-ui-sortable'],
                    PUBLISHPRESS_VERSION,
                    true
                );

                wp_localize_script(
                    'publishpress-custom-status-configure',
                    'objectL10ncustomstatus',
                    [
                        'pp_confirm_delete_status_string' => __(
                            'Are you sure you want to delete the post status? All posts with this status will be assigned to the default status.',
                            'publishpress'
                        ),
                    ]
                );
                wp_enqueue_script(
                    'publishpress-icon-preview',
                    $this->module_url . 'lib/icon-picker.js',
                    ['jquery'],
                    PUBLISHPRESS_VERSION,
                    true
                );
                wp_enqueue_style(
                    'publishpress-icon-preview',
                    $this->module_url . 'lib/icon-picker.css',
                    ['dashicons'],
                    PUBLISHPRESS_VERSION,
                    'all'
                );

                wp_enqueue_style(
                    'publishpress-custom_status-admin',
                    $this->module_url . 'lib/custom-status-admin.css',
                    false,
                    PUBLISHPRESS_VERSION,
                    'all'
                );
            }

            // Custom javascript to modify the post status dropdown where it shows up
            if ($this->is_whitelisted_page()) {
                wp_enqueue_script(
                    'publishpress-custom_status',
                    $this->module_url . 'lib/custom-status.js',
                    ['jquery'],
                    PUBLISHPRESS_VERSION,
                    true
                );

                if ($publishpress->isBlockEditorActive()) {
                    wp_enqueue_style(
                        'publishpress-custom_status-block',
                        $this->module_url . 'lib/custom-status-block-editor.css',
                        false,
                        PUBLISHPRESS_VERSION,
                        'all'
                    );
                } else {
                    wp_enqueue_style(
                        'publishpress-custom_status',
                        $this->module_url . 'lib/custom-status.css',
                        false,
                        PUBLISHPRESS_VERSION,
                        'all'
                    );
                }
            }
        }

        /**
         * Enqueue Gutenberg assets.
         */
        public function enqueue_block_editor_assets()
        {
            global $publishpress;

            if ($this->disable_custom_statuses_for_post_type()) {
                return;
            }

            wp_enqueue_script(
                'pp-custom-status-block',
                $this->module_url . '/lib/custom-status-block.min.js',
                ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-hooks'],
                PUBLISHPRESS_VERSION,
                true
            );

            $post = get_post();

            $statuses = apply_filters('pp_custom_status_list', $this->get_custom_statuses(), $post);

            wp_localize_script(
                'pp-custom-status-block',
                'PPCustomStatuses',
                array_values($statuses)
            );
        }

        /**
         * Displays a notice to users if they have JS disabled
         * Javascript is needed for custom statuses to be fully functional
         */
        public function no_js_notice()
        {
            if ($this->is_whitelisted_page()) :
                ?>
                <style type="text/css">
                    /* Hide post status dropdown by default in case of JS issues **/
                    label[for=post_status],
                    #post-status-display,
                    #post-status-select,
                    #publish {
                        display: none;
                    }
                </style>
                <div class="update-nag hide-if-js">
                    <?php
                    _e(
                        '<strong>Note:</strong> Your browser does not support JavaScript or has JavaScript disabled. You will not be able to access or change the post status.',
                        'publishpress'
                    ); ?>
                </div>
            <?php
            endif;
        }

        /**
         * Generate the color picker
         * $current_value   Selected icon for the status
         * fieldname        The name for the <select> field
         * $attributes      Insert attributes different to name and class. For example: 'id="something"'
         */
        public function pp_color_picker($current_value = '', $fieldname = 'icon', $attributes = '')
        {
            // Load Color Picker
            if (is_admin()) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script(
                    'publishpress-color-picker',
                    $this->module_url . 'lib/color-picker.js',
                    ['wp-color-picker'],
                    false,
                    true
                );
            }

            // Set default value if empty
            if (! empty($current_value)) {
                $pp_color = $current_value;
            } else {
                $pp_color = self::DEFAULT_COLOR;
            }

            $color_picker = '<input type="text" aria-required="true" size="7" maxlength="7" name="' . $fieldname . '" value="' . $pp_color . '" class="pp-color-picker" ' . $attributes . ' data-default-color="' . $pp_color . '" />';

            return $color_picker;
        }

        /**
         * Generate the dropdown for dashicons
         * $current_value   Selected icon for the status
         * fieldname        The name for the <select> field
         * $attributes      Insert attributes different to name. For example: 'class="something"', 'id="something"'
         */
        public function dropdown_icons($current_value = '', $fieldname = 'icon', $attributes = '')
        {
            $pp_icons_dropdown = '';

            $pp_icons_list = [
                'edit',
                'menu',
                'admin-site',
                'dashboard',
                'admin-media',
                'admin-page',
                'admin-comments',
                'admin-appearance',
                'admin-plugins',
                'admin-users',
                'admin-tools',
                'admin-settings',
                'admin-network',
                'admin-generic',
                'admin-home',
                'admin-collapse',
                'filter',
                'admin-customizer',
                'admin-multisite',
                'admin-links',
                'format-links',
                'admin-post',
                'format-standard',
                'format-image',
                'format-gallery',
                'format-audio',
                'format-video',
                'format-chat',
                'format-status',
                'format-aside',
                'format-quote',
                'welcome-edit-page',
                'welcome-write-blog',
                'welcome-add-page',
                'welcome-view-site',
                'welcome-widgets-menus',
                'welcome-comments',
                'welcome-learn-more',
                'image-crop',
                'image-rotate',
                'image-rotate-left',
                'image-rotate-right',
                'image-flip-vertical',
                'image-flip-horizontal',
                'image-filter',
                'undo',
                'redo',
                'editor-bold',
                'editor-italic',
                'editor-ul',
                'editor-ol',
                'editor-quote',
                'editor-alignleft',
                'editor-aligncenter',
                'editor-alignright',
                'editor-insertmore',
                'editor-spellcheck',
                'editor-distractionfree',
                'editor-expand',
                'editor-contract',
                'editor-kitchensink',
                'editor-underline',
                'editor-justify',
                'editor-textcolor',
                'editor-paste-word',
                'editor-paste-text',
                'editor-removeformatting',
                'editor-video',
                'editor-customchar',
                'editor-outdent',
                'editor-indent',
                'editor-help',
                'editor-strikethrough',
                'editor-unlink',
                'editor-rtl',
                'editor-break',
                'editor-code',
                'editor-paragraph',
                'editor-table',
                'align-left',
                'align-right',
                'align-center',
                'align-none',
                'lock',
                'unlock',
                'calendar',
                'calendar-alt',
                'visibility',
                'hidden',
                'post-status',
                'post-trash',
                'trash',
                'sticky',
                'external',
                'arrow-up',
                'arrow-down',
                'arrow-left',
                'arrow-right',
                'arrow-up-alt',
                'arrow-down-alt',
                'arrow-left-alt',
                'arrow-right-alt',
                'arrow-up-alt2',
                'arrow-down-alt2',
                'arrow-left-alt2',
                'arrow-right-alt2',
                'leftright',
                'sort',
                'randomize',
                'list-view',
                'excerpt-view',
                'exerpt-view',
                'grid-view',
                'move',
                'hammer',
                'art',
                'migrate',
                'performance',
                'universal-access',
                'universal-access-alt',
                'tickets',
                'nametag',
                'clipboard',
                'heart',
                'megaphone',
                'schedule',
                'wordpress',
                'wordpress-alt',
                'pressthis',
                'update',
                'screenoptions',
                'cart',
                'feedback',
                'cloud',
                'translation',
                'tag',
                'category',
                'archive',
                'tagcloud',
                'text',
                'media-archive',
                'media-audio',
                'media-code',
                'media-default',
                'media-document',
                'media-interactive',
                'media-spreadsheet',
                'media-text',
                'media-video',
                'playlist-audio',
                'playlist-video',
                'controls-play',
                'controls-pause',
                'controls-forward',
                'controls-skipforward',
                'controls-back',
                'controls-skipback',
                'controls-repeat',
                'controls-volumeon',
                'controls-volumeoff',
                'yes',
                'no',
                'no-alt',
                'plus',
                'plus-alt',
                'plus-alt2',
                'minus',
                'dismiss',
                'marker',
                'star-filled',
                'star-half',
                'star-empty',
                'flag',
                'info',
                'warning',
                'share',
                'share1',
                'share-alt',
                'share-alt2',
                'twitter',
                'rss',
                'email',
                'email-alt',
                'facebook',
                'facebook-alt',
                'networking',
                'googleplus',
                'location',
                'location-alt',
                'camera',
                'images-alt',
                'images-alt2',
                'video-alt',
                'video-alt2',
                'video-alt3',
                'vault',
                'shield',
                'shield-alt',
                'sos',
                'search',
                'slides',
                'analytics',
                'chart-pie',
                'chart-bar',
                'chart-line',
                'chart-area',
                'groups',
                'businessman',
                'id',
                'id-alt',
                'products',
                'awards',
                'forms',
                'testimonial',
                'portfolio',
                'book',
                'book-alt',
                'download',
                'upload',
                'backup',
                'clock',
                'lightbulb',
                'microphone',
                'desktop',
                'laptop',
                'tablet',
                'smartphone',
                'phone',
                'smiley',
                'index-card',
                'carrot',
                'building',
                'store',
                'album',
                'palmtree',
                'tickets-alt',
                'money',
                'thumbs-up',
                'thumbs-down',
                'layout',
                'paperclip',
            ];

            $pp_icons_dropdown .= '<select class="pp-icons-dropdown" name="' . esc_attr(
                    $fieldname
                ) . '" ' . $attributes . '>';

            foreach ($pp_icons_list as $pp_icon) {
                // Set selected value if exist
                if ('dashicons-' . $pp_icon == $current_value) {
                    $pp_icon_selected = ' selected';
                } else {
                    $pp_icon_selected = '';
                }

                $pp_icons_dropdown .= '<option value="dashicons-' . esc_attr(
                        $pp_icon
                    ) . '"' . $pp_icon_selected . '>dashicons-' . esc_html__($pp_icon) . '</option>';
            }

            $pp_icons_dropdown .= '</select>';

            return $pp_icons_dropdown;
        }

        /**
         * Check whether custom status stuff should be loaded on this page
         *
         * @todo migrate this to the base module class
         */
        public function is_whitelisted_page()
        {
            global $pagenow;

            if (! in_array($this->get_current_post_type(), $this->get_post_types_for_module($this->module))) {
                return false;
            }

            $post_type_obj = get_post_type_object($this->get_current_post_type());

            if (! current_user_can($post_type_obj->cap->edit_posts)) {
                return false;
            }

            // Disable the scripts for the post page if the plugin Visual Composer is enabled.
            if (isset($_GET['vcv-action']) && $_GET['vcv-action'] === 'frontend') {
                return false;
            }

            // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
            return in_array(
                $pagenow,
                ['post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php']
            );
        }

        /**
         * If the $only_basic_data argument is true, we do not load color and icon information, to save resources.
         * That information is not required in the front-end for example.
         *
         * @param bool $only_basic_data
         *
         * @return array
         */
        protected function get_core_statuses($only_basic_data = false)
        {
            $all_statuses = [];

            // Draft
            $status = (object)[
                'term_id' => self::STATUS_DRAFT,
                'name' => __('Draft', 'publishpress'),
                'slug' => 'draft',
                'description' => '-',
                'color' => '',
                'icon' => '',
                'position' => 4,
            ];

            if (! $only_basic_data) {
                $status->color = get_option('psppno_status_draft_color', '#f91d84');
                $status->icon = get_option('psppno_status_draft_icon', 'dashicons-media-default');
                $status->position = get_option('psppno_status_draft_position', 4);
            }

            $all_statuses[] = $status;

            // Pending Review
            $status = (object)[
                'term_id' => self::STATUS_PENDING,
                'name' => __('Pending review', 'publishpress'),
                'slug' => 'pending',
                'description' => '-',
                'color' => '',
                'icon' => '',
                'position' => 5,
            ];

            if (! $only_basic_data) {
                $status->color = get_option('psppno_status_pending_color', '#d87200');
                $status->icon = get_option('psppno_status_pending_icon', 'dashicons-clock');
                $status->position = get_option('psppno_status_pending_position', 5);
            }

            $all_statuses[] = $status;

            // Scheduled
            $status = (object)[
                'term_id' => self::STATUS_SCHEDULED,
                'name' => __('Scheduled', 'publishpress'),
                'slug' => 'future',
                'description' => '-',
                'color' => '',
                'icon' => '',
                'position' => 7,
            ];

            if (! $only_basic_data) {
                $status->color = get_option('psppno_status_future_color', self::DEFAULT_COLOR);
                $status->icon = get_option('psppno_status_future_icon', 'dashicons-calendar-alt');
                $status->position = get_option('psppno_status_future_position', 7);
            }

            $all_statuses[] = $status;

            // Privately Published
            $status = (object)[
                'term_id' => self::STATUS_PRIVATE,
                'name' => __('Privately Published', 'publishpress'),
                'slug' => 'private',
                'description' => '-',
                'color' => '',
                'icon' => '',
                'position' => 8,
            ];

            if (! $only_basic_data) {
                $status->color = get_option('psppno_status_private_color', '#000000');
                $status->icon = get_option('psppno_status_private_icon', 'dashicons-lock');
                $status->position = get_option('psppno_status_private_position', 8);
            }

            $all_statuses[] = $status;

            // Published
            $status = (object)[
                'term_id' => self::STATUS_PUBLISH,
                'name' => __('Published', 'publishpress'),
                'slug' => 'publish',
                'description' => '-',
                'color' => '',
                'icon' => '',
                'position' => 9,
            ];

            if (! $only_basic_data) {
                $status->color = get_option('psppno_status_publish_color', self::DEFAULT_COLOR);
                $status->icon = get_option('psppno_status_publish_icon', 'dashicons-yes');
                $status->position = get_option('psppno_status_publish_position', 9);
            }

            $all_statuses[] = $status;

            return $all_statuses;
        }

        /**
         * Adds all necessary javascripts to make custom statuses work
         *
         * @todo Support private and future posts on edit.php view
         */
        public function post_admin_header()
        {
            global $post, $publishpress, $pagenow, $current_user;
            if ($this->disable_custom_statuses_for_post_type()) {
                return;
            }

            // Get current user
            wp_get_current_user();

            if ($this->is_whitelisted_page()) {
                $post_type_obj = get_post_type_object($this->get_current_post_type());
                $custom_statuses = $this->get_custom_statuses();
                $selected = null;
                $selected_name = __('Draft', 'publishpress');

                $custom_statuses = apply_filters('pp_custom_status_list', $custom_statuses, $post);

                // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
                if (! empty($post)) {
                    //get raw post so custom post status is included
                    $post = get_post($post);
                    // Get the status of the current post
                    if ($post->ID == 0 || $post->post_status == 'auto-draft' || $pagenow == 'edit.php') {
                        // TODO: check to make sure that the default exists
                        $selected = self::DEFAULT_STATUS;
                    } else {
                        $selected = $post->post_status;
                    }

                    if (empty($selected)) {
                        $selected = self::DEFAULT_STATUS;
                    }

                    // Get the current post status name

                    foreach ($custom_statuses as $status) {
                        if ($status->slug == $selected) {
                            $selected_name = $status->name;
                        }
                    }
                }

                $all_statuses = [];

                // Load the custom statuses
                foreach ($custom_statuses as $status) {
                    $all_statuses[] = [
                        'name' => esc_js($this->get_status_property($status, 'name')),
                        'slug' => esc_js($this->get_status_property($status, 'slug')),
                        'description' => esc_js($this->get_status_property($status, 'description')),
                        'color' => esc_js($this->get_status_property($status, 'color')),
                        'icon' => esc_js($this->get_status_property($status, 'icon')),

                    ];
                }

                $always_show_dropdown = ($this->module->options->always_show_dropdown == 'on') ? 1 : 0;

                // TODO: Move this to a script localization method. ?>
                <script type="text/javascript">
                    var pp_text_no_change = '<?php echo esc_js(__("&mdash; No Change &mdash;")); ?>';
                    var label_save = '<?php echo __('Save'); ?>';
                    var pp_default_custom_status = '<?php echo esc_js(self::DEFAULT_STATUS); ?>';
                    var current_status = '<?php echo esc_js($selected); ?>';
                    var current_status_name = '<?php echo esc_js($selected_name); ?>';
                    var custom_statuses = <?php echo json_encode($all_statuses); ?>;
                    var current_user_can_publish_posts = <?php echo current_user_can(
                        $post_type_obj->cap->publish_posts
                    ) ? 1 : 0; ?>;
                    var current_user_can_edit_published_posts = <?php echo current_user_can(
                        $post_type_obj->cap->edit_published_posts
                    ) ? 1 : 0; ?>;
                    var status_dropdown_visible = <?php echo esc_js($always_show_dropdown); ?>;
                </script>
                <?php
            }
        }

        private function get_status_property($status, $property)
        {
            if (! isset($status->$property)) {
                if ($property === 'color') {
                    $value = self::DEFAULT_COLOR;
                } else {
                    $value = '';
                }
            } else {
                $value = $status->$property;
            }

            return $value;
        }

        /**
         * Adds a new custom status as a term in the wp_terms table.
         * Basically a wrapper for the wp_insert_term class.
         *
         * The arguments decide how the term is handled based on the $args parameter.
         * The following is a list of the available overrides and the defaults.
         *
         * 'description'. There is no default. If exists, will be added to the database
         * along with the term. Expected to be a string.
         *
         * 'slug'. Expected to be a string. There is no default.
         *
         * @param int|string $term The status to add or update
         * @param array|string $args Change the values of the inserted term
         *
         * @return array|WP_Error $response The Term ID and Term Taxonomy ID
         */
        private function add_custom_status($term, $args = [])
        {
            $slug = (! empty($args['slug'])) ? $args['slug'] : sanitize_title($term);
            unset($args['slug']);
            $encoded_description = $this->get_encoded_description($args);
            $response = wp_insert_term(
                $term,
                self::taxonomy_key,
                ['slug' => $slug, 'description' => $encoded_description]
            );

            // Reset our internal object cache
            $this->custom_statuses_cache = [];

            // Set permissions for the base roles
            $roles = ['administrator', 'editor', 'author', 'contributor'];
            foreach ($roles as $roleSlug) {
                $role = get_role($roleSlug);
                if (! empty($role)) {
                    $role->add_cap('status_change_' . str_replace('-', '_', $slug));
                }
            }

            return $response;
        }

        /**
         * Update an existing custom status
         *
         * @param int @status_id ID for the status
         * @param array $args Any arguments to be updated
         *
         * @return object|WP_Error|false $updated_status Newly updated status object
         */
        private function update_custom_status($status_id, $args = [])
        {
            $old_status = $this->get_custom_status_by('id', $status_id);
            if (! $old_status || is_wp_error($old_status)) {
                return new WP_Error('invalid', __("Custom status doesn't exist.", 'publishpress'));
            }

            // Reset our internal object cache
            $this->custom_statuses_cache = [];

            if (isset($args['slug'])) {
                $args['slug'] = sanitize_title($args['slug']);

                // If the slug is empty we need to defined one
                if (empty($args['slug'])) {
                    $args['slug'] = sanitize_title($args['name']);
                }
            }

            // Reassign posts to new status slug if the slug changed and isn't restricted
            if (isset($args['slug']) && $args['slug'] != $old_status->slug && ! $this->is_restricted_status(
                    $old_status->slug
                )) {
                $this->reassign_post_status($old_status->slug, $args['slug']);
            }

            $slug = isset($args['slug']) ? $args['slug'] : $old_status->slug;

            $updatedStatusId = $status_id;
            if (is_numeric($status_id)) {
                // We're encoding metadata that isn't supported by default in the term's description field
                $args_to_encode = [];
                $args_to_encode['description'] = (isset($args['description'])) ? sanitize_textarea_field($args['description']) : $old_status->description;
                $args_to_encode['position'] = (isset($args['position'])) ? (int)$args['position'] : $old_status->position;
                $args_to_encode['color'] = (isset($args['color'])) ? sanitize_text_field($args['color']) : $old_status->color;
                $args_to_encode['icon'] = (isset($args['icon'])) ? sanitize_text_field($args['icon']) : $old_status->icon;

                $encoded_description = $this->get_encoded_description($args_to_encode);
                $args['description'] = $encoded_description;

                $updated_status_array = wp_update_term($status_id, self::taxonomy_key, $args);

                if (is_wp_error($updated_status_array)) {
                    return $updated_status_array;
                }

                if (! is_array($updated_status_array) || !isset($updated_status_array['term_id'])) {
                    error_log(
                        sprintf(
                            '[PUBLISHPRESS] Error updating the status term. $status_id: %s, taxonomy: %s, $args: %s',
                            $status_id,
                            self::taxonomy_key,
                            print_r($args, true)
                        )
                    );

                    return new WP_Error('custom-status-term_id', esc_html__('Error while updating the status', 'publishpress'));
                }

                $updatedStatusId = $updated_status_array['term_id'];
            } elseif (isset($args['position'])) {
                $slug = sanitize_key($slug);

                $args['position'] = (int)$args['position'];

                update_option('psppno_status_' . $slug . '_position', $args['position']);
            }

            return $this->get_custom_status_by('id', $updatedStatusId);
        }

        /**
         * Deletes a custom status from the wp_terms table.
         *
         * Partly a wrapper for the wp_delete_term function.
         * BUT, also reassigns posts that currently have the deleted status assigned.
         */
        private function delete_custom_status($status_id, $args = [], $reassign = '')
        {
            global $publishpress;
            // Reassign posts to alternate status

            // Get slug for the old status
            $old_status = $this->get_custom_status_by('id', $status_id)->slug;

            if ($reassign == $old_status) {
                return new WP_Error('invalid', __('Cannot reassign to the status you want to delete', 'publishpress'));
            }

            // Reset our internal object cache
            $this->custom_statuses_cache = [];

            if (! $this->is_restricted_status($old_status)) {
                $default_status = self::DEFAULT_STATUS;
                // If new status in $reassign, use that for all posts of the old_status
                if (! empty($reassign)) {
                    $new_status = $this->get_custom_status_by('id', $reassign)->slug;
                } else {
                    $new_status = $default_status;
                }
                if ($old_status == $default_status && $this->get_custom_status_by(
                        'slug',
                        'draft'
                    )) { // Deleting default status
                    $new_status = 'draft';
                    $publishpress->update_module_option($this->module->name, 'default_status', $new_status);
                }

                $this->reassign_post_status($old_status, $new_status);

                return wp_delete_term($status_id, self::taxonomy_key, $args);
            } else {
                return new WP_Error(
                    'restricted',
                    __('Restricted status ', 'publishpress') . '(' . $this->get_custom_status_by(
                        'id',
                        $status_id
                    )->name . ')'
                );
            }
        }

        /**
         * Get all custom statuses as an ordered array
         *
         * @param array|string $statuses
         * @param array $args
         * @param bool $only_basic_info
         *
         * @return array $statuses All of the statuses
         */
        public function get_custom_statuses($args = [], $only_basic_info = false)
        {
            if ($this->disable_custom_statuses_for_post_type() || 'off' === $this->module->options->enabled) {
                return $this->get_core_post_statuses();
            }

            // Internal object cache for repeat requests
            $arg_hash = md5(serialize($args));
            if (! empty($this->custom_statuses_cache[$arg_hash])) {
                return $this->custom_statuses_cache[$arg_hash];
            }

            if (! taxonomy_exists(self::taxonomy_key)) {
                $this->register_custom_statuses();
            }

            // Handle if the requested taxonomy doesn't exist
            $args = array_merge(
                [
                    'hide_empty' => false,
                    'taxonomy' => self::taxonomy_key,
                ],
                $args
            );
            $statuses = get_terms(self::taxonomy_key, $args);

            if (is_wp_error($statuses) || empty($statuses)) {
                $statuses = [];
            }

            $default_terms = $this->get_default_terms();

            // Expand and order the statuses
            $orderedStatusList = [];
            $hold_to_end = [];
            foreach ($statuses as $key => $status) {
                // Unencode and set all of our pseudo term meta because we need the position if it exists
                $unencoded_description = $this->get_unencoded_description($status->description);
                if (is_array($unencoded_description)) {
                    foreach ($unencoded_description as $descriptionKey => $value) {
                        $status->$descriptionKey = $value;
                    }
                }
                // We require the position key later on (e.g. management table)
                if (! isset($status->position)) {
                    $slug = $status->slug === 'pending-review' ? 'pending' : $status->slug;
                    if (array_key_exists($slug, $default_terms)) {
                        $status->position = $default_terms[$status->slug]['args']['position'];
                    } else {
                        $status->position = 99;
                    }
                }

                // Remove legacy custom statuses that were stored as custom statuses
                if (in_array($status->slug, ['pending', 'draft'])) {
                    continue;
                }

                $this->addItemToArray($orderedStatusList, (int)$status->position, $status);

                // Check if we need to set default colors and icons for current status
                if (! isset($status->color) || empty($status->color)) {
                    // Set default color
                    if (array_key_exists($status->slug, $default_terms)) {
                        $status->color = $default_terms[$status->slug]['args']['color'];
                    } else {
                        $status->color = self::DEFAULT_COLOR;
                    }
                }

                if (! isset($status->icon) || empty($status->icon)) {
                    // Set default icon
                    if (array_key_exists($status->slug, $default_terms)) {
                        $status->icon = $default_terms[$status->slug]['args']['icon'];
                    } else {
                        $status->icon = 'dashicons-arrow-right-alt2';
                    }
                }
            }

            // Add core statuses, custom properties saved on the config
            $coreStatusList = $this->get_core_statuses($only_basic_info);
            foreach ($coreStatusList as $coreStatus) {
                $this->addItemToArray($orderedStatusList, $coreStatus->position, $coreStatus);
            }

            // Sort the items numerically by key
            ksort($orderedStatusList, SORT_NUMERIC);
            // Append all the statuses that didn't have an existing position
            foreach ($hold_to_end as $unpositioned_status) {
                $orderedStatusList[] = $unpositioned_status;
            }

            $this->custom_statuses_cache[$arg_hash] = $orderedStatusList;

            return $orderedStatusList;
        }

        /**
         * Add item to Array without overwrite any item, in case an item is already set for the position.
         *
         * @param $array
         * @param $position
         * @param $item
         */
        private function addItemToArray(&$array, $position, $item)
        {
            if (isset($array[$position])) {
                $this->addItemToArray($array, $position + 1, $item);
            } else {
                $array[$position] = $item;
            }
        }

        /**
         * Returns the a single status object based on ID, title, or slug
         *
         * @param string|int $string_or_int The status to search for, either by slug, name or ID
         *
         * @return object|WP_Error|false $status The object for the matching status
         */
        public function get_custom_status_by($field, $value)
        {
            if (! in_array($field, ['id', 'slug', 'name'])) {
                return false;
            }

            if ('id' === $field) {
                $field = 'term_id';
            }

            // New and auto-draft do not exists as status. So we map them to draft for now.
            if ('slug' === $field && in_array($value, ['new', 'auto-draft'])) {
                $value = 'draft';
            }

            $custom_statuses = $this->get_custom_statuses();
            $custom_status = wp_filter_object_list($custom_statuses, [$field => $value]);

            if (! empty($custom_status)) {
                return array_shift($custom_status);
            }

            return false;
        }

        /**
         * Assign new statuses to posts using value provided or the default
         *
         * @param string $old_status Slug for the old status
         * @param string $new_status Slug for the new status
         */
        private function reassign_post_status($old_status, $new_status = '')
        {
            global $wpdb;

            if (empty($new_status)) {
                $new_status = self::DEFAULT_STATUS;
            }

            // todo: Why the query argument is %s?
            $result = $wpdb->update(
                $wpdb->posts,
                ['post_status' => $new_status],
                ['post_status' => $old_status],
                ['%s']
            );
        }

        /**
         * Insert new column header for post status after the title column
         *
         * @param array $posts_columns Columns currently shown on the Edit Posts screen
         *
         * @return array Same array as the input array with a "status" column added after the "title" column
         */
        public function _filter_manage_posts_columns($posts_columns)
        {
            // Return immediately if the supplied parameter isn't an array (which shouldn't happen in practice?)
            // http://wordpress.org/support/topic/plugin-publishpress-bug-shows-2-drafts-when-there-are-none-leads-to-error-messages
            if (! is_array($posts_columns)) {
                return $posts_columns;
            }

            // Only do it for the post types this module is activated for
            if (! in_array($this->get_current_post_type(), $this->get_post_types_for_module($this->module))) {
                return $posts_columns;
            }

            $result = [];
            foreach ($posts_columns as $key => $value) {
                if ($key == 'title') {
                    $result[$key] = $value;
                    $result['status'] = __('Status', 'publishpress');
                } else {
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        /**
         * Adds a Post's status to its row on the Edit page
         *
         * @param string $column_name
         **/
        public function _filter_manage_posts_custom_column($column_name)
        {
            if ($column_name == 'status') {
                global $post;
                echo $this->get_post_status_friendly_name($post->post_status);
            }
        }


        /**
         * Determines whether the slug indicated belongs to a restricted status or not
         *
         * @param string $slug Slug of the status
         *
         * @return bool $restricted True if restricted, false if not
         */
        private function is_restricted_status($slug)
        {
            switch ($slug) {
                case 'publish':
                case 'private':
                case 'future':
                case 'pending':
                case 'draft':
                case 'new':
                case 'inherit':
                case 'auto-draft':
                case 'trash':
                    $restricted = true;
                    break;

                default:
                    $restricted = false;
                    break;
            }

            return $restricted;
        }

        /**
         * Handles a form's POST request to add a custom status
         *
         * @since 0.7
         */
        public function handle_add_custom_status()
        {
            // Check that the current POST request is our POST request
            if (! isset($_POST['submit'], $_GET['page'], $_GET['settings_module'], $_POST['action'])
                || ($_GET['page'] != PP_Modules_Settings::SETTINGS_SLUG && $_GET['settings_module'] != self::SETTINGS_SLUG) || $_POST['action'] != 'add-new') {
                return;
            }

            check_admin_referer('custom-status-add-nonce');

            // Validate and sanitize the form data
            $status_name = sanitize_text_field(trim($_POST['status_name']));
            $status_slug = sanitize_title($status_name);
            $status_description = stripslashes(wp_filter_nohtml_kses(trim($_POST['status_description'])));
            $status_color = sanitize_hex_color($_POST['status_color']);
            $status_icon = str_replace('dashicons|', '', $_POST['icon']);

            /**
             * Form validation
             * - Name is required and can't conflict with an existing name or slug
             * - Description is optional
             */
            $_REQUEST['form-errors'] = [];
            // Check if name field was filled in
            if (empty($status_name)) {
                $_REQUEST['form-errors']['name'] = __('Please enter a name for the status', 'publishpress');
            }
            // Check that the name isn't numeric
            if (is_numeric($status_name)) {
                $_REQUEST['form-errors']['name'] = __(
                    'Please enter a valid, non-numeric name for the status.',
                    'publishpress'
                );
            }
            // Check that the status name doesn't exceed 20 chars
            $name_is_valid = true;
            if (function_exists('mb_strlen')) {
                if (mb_strlen($status_name) > 20) {
                    $name_is_valid = false;
                }
            } else {
                if (strlen($status_name) > 20) {
                    $name_is_valid = false;
                }
            }
            if (! $name_is_valid) {
                $_REQUEST['form-errors']['name'] = __(
                    'Status name cannot exceed 20 characters. Please try a shorter name.',
                    'publishpress'
                );
            }

            // Check to make sure the status doesn't already exist as another term because otherwise we'd get a weird slug
            if (term_exists($status_slug, self::taxonomy_key)) {
                $_REQUEST['form-errors']['name'] = __(
                    'Status name conflicts with existing term. Please choose another.',
                    'publishpress'
                );
            }
            // Check to make sure the name is not restricted
            if ($this->is_restricted_status(strtolower($status_slug))) {
                $_REQUEST['form-errors']['name'] = __(
                    'Status name is restricted. Please choose another name.',
                    'publishpress'
                );
            }

            // If there were any form errors, kick out and return them
            if (count($_REQUEST['form-errors'])) {
                $_REQUEST['error'] = 'form-error';

                return;
            }

            // Try to add the status
            $status_args = [
                'description' => $status_description,
                'slug' => $status_slug,
                'color' => $status_color,
                'icon' => $status_icon,
            ];
            $return = $this->add_custom_status($status_name, $status_args);
            if (is_wp_error($return)) {
                wp_die(__('Could not add status: ', 'publishpress') . $return->get_error_message());
            }
            // Redirect if successful
            $redirect_url = $this->get_link(['message' => 'status-added', 'action' => 'add-new']);
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handles a POST request to edit a custom status
         *
         * @since 0.7
         */
        public function handle_edit_custom_status()
        {
            if (! isset($_POST['submit'], $_GET['page'], $_GET['settings_module'], $_GET['action'], $_GET['term-id'])
                || ($_GET['page'] != PP_Modules_Settings::SETTINGS_SLUG && $_GET['settings_module'] != self::SETTINGS_SLUG) || $_GET['action'] != 'edit-status') {
                return;
            }

            check_admin_referer('edit-status');

            if (! current_user_can('manage_options')) {
                wp_die($this->module->messages['invalid-permissions']);
            }

            if (is_numeric($_GET['term-id']) && ! $existing_status = $this->get_custom_status_by(
                    'id',
                    (int)$_GET['term-id']
                )) {
                wp_die($this->module->messages['status-missing']);
            }

            $color = sanitize_hex_color($_POST['color']);
            $icon = sanitize_text_field($_POST['icon']);
            $icon = str_replace('dashicons|', '', $icon);

            if (is_numeric($_GET['term-id'])) {
                $name = sanitize_text_field(trim($_POST['name']));
                $slug = sanitize_text_field(trim($_POST['slug']));
                $description = stripslashes(wp_filter_nohtml_kses(trim($_POST['description'])));

                /**
                 * Form validation for editing custom status
                 *
                 * Details
                 * - 'name' is a required field and can't conflict with existing name or slug
                 * - 'description' is optional
                 */
                $_REQUEST['form-errors'] = [];
                // Check if name field was filled in
                if (empty($name)) {
                    $_REQUEST['form-errors']['name'] = __('Please enter a name for the status', 'publishpress');
                }
                // Check that the name isn't numeric
                if (is_numeric($name)) {
                    $_REQUEST['form-errors']['name'] = __(
                        'Please enter a valid, non-numeric name for the status.',
                        'publishpress'
                    );
                }
                // Check that the status name doesn't exceed 20 chars
                $name_is_valid = true;
                if (function_exists('mb_strlen')) {
                    if (mb_strlen($name) > 20) {
                        $name_is_valid = false;
                    }
                } else {
                    if (strlen($name) > 20) {
                        $name_is_valid = false;
                    }
                }
                if (! $name_is_valid) {
                    $_REQUEST['form-errors']['name'] = __(
                        'Status name cannot exceed 20 characters. Please try a shorter name.',
                        'publishpress'
                    );
                }

                // Check to make sure the status doesn't already exist as another term because otherwise we'd get a weird slug
                $term_exists = term_exists(sanitize_title($name), self::taxonomy_key);

                if (is_array($term_exists)) {
                    $term_exists = (int)$term_exists['term_id'];
                }

                if ($term_exists && $term_exists != $existing_status->term_id) {
                    $_REQUEST['form-errors']['name'] = __(
                        'Status name conflicts with existing term. Please choose another.',
                        'publishpress'
                    );
                }
                // Check to make sure the status doesn't already exist
                $search_status = $this->get_custom_status_by('slug', sanitize_title($name));

                if ($search_status && $search_status->term_id != $existing_status->term_id) {
                    $_REQUEST['form-errors']['name'] = __(
                        'Status name conflicts with existing status. Please choose another.',
                        'publishpress'
                    );
                }
                // Check to make sure the name is not restricted
                if ($this->is_restricted_status(strtolower(sanitize_title($name)))) {
                    $_REQUEST['form-errors']['name'] = __(
                        'Status name is restricted. Please choose another name.',
                        'publishpress'
                    );
                }

                // Kick out if there are any errors
                if (count($_REQUEST['form-errors'])) {
                    $_REQUEST['error'] = 'form-error';

                    return;
                }

                // Try to add the new post status
                $args = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'color' => $color,
                    'icon' => $icon,
                ];

                $return = $this->update_custom_status($existing_status->term_id, $args);

                if (is_wp_error($return)) {
                    wp_die(__('Error updating post status.', 'publishpress'));
                }
            }

            // Saving custom settings for native statuses
            if (! is_numeric($_GET['term-id'])) {
                $slug = sanitize_title($_GET['term-id']);

                update_option("psppno_status_{$slug}_color", $color);
                update_option("psppno_status_{$slug}_icon", $icon);
            }

            $redirect_url = $this->get_link(['message' => 'status-updated', 'action' => 'add-new']);
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Handles a GET request to delete a specific term
         *
         * @since 0.7
         */
        public function handle_delete_custom_status()
        {
            // Check that this GET request is our GET request
            if (! isset($_GET['page'], $_GET['settings_module'], $_GET['action'], $_GET['term-id'], $_GET['_wpnonce'])
                || ($_GET['page'] != PP_Modules_Settings::SETTINGS_SLUG && $_GET['settings_module'] != self::SETTINGS_SLUG) || $_GET['action'] != 'delete-status') {
                return;
            }

            // Check for proper nonce
            check_admin_referer('delete-status');

            // Only allow users with the proper caps
            if (! current_user_can('manage_options')) {
                wp_die(__('Sorry, you do not have permission to edit custom statuses.', 'publishpress'));
            }

            // Check to make sure the status isn't already deleted
            $term_id = (int)$_GET['term-id'];
            $term = $this->get_custom_status_by('id', $term_id);
            if (! $term) {
                wp_die(__('Status does not exist.', 'publishpress'));
            }

            $return = $this->delete_custom_status($term_id);
            if (is_wp_error($return)) {
                wp_die(__('Could not delete the status: ', 'publishpress') . $return->get_error_message());
            }

            $redirect_url = $this->get_link(['message' => 'status-deleted', 'action' => 'add-new']);
            wp_redirect($redirect_url);
            exit;
        }

        /**
         * Generate a link to one of the custom status actions
         *
         * @param array $args (optional) Action and any query args to add to the URL
         *
         * @return string $link Direct link to complete the action
         * @since 0.7
         *
         */
        public function get_link($args = [])
        {
            if (! isset($args['action'])) {
                $args['action'] = '';
            }
            if (! isset($args['page'])) {
                $args['page'] = PP_Modules_Settings::SETTINGS_SLUG;
            }
            if (! isset($args['settings_module'])) {
                $args['settings_module'] = self::SETTINGS_SLUG;
            }

            // Add other things we may need depending on the action
            switch ($args['action']) {
                case 'delete-status':
                    $args['_wpnonce'] = wp_create_nonce($args['action']);
                    break;
                default:
                    break;
            }

            return add_query_arg($args, get_admin_url(null, 'admin.php'));
        }

        /**
         * Handle an ajax request to update the order of custom statuses
         *
         * @since 0.7
         */
        public function handle_ajax_update_status_positions()
        {
            check_ajax_referer('custom-status-sortable');

            if (! current_user_can('manage_options')) {
                $this->print_ajax_response('error', $this->module->messages['invalid-permissions']);
            }

            if (! isset($_POST['status_positions']) || ! is_array($_POST['status_positions'])) {
                $this->print_ajax_response('error', __('Terms not set.', 'publishpress'));
            }

            // Update each custom status with its new position
            foreach ($_POST['status_positions'] as $position => $term_id) {
                // Have to add 1 to the position because the index started with zero
                $args = [
                    'position' => (int)$position + 1,
                ];

                $result = $this->update_custom_status($term_id, $args);

                if (is_wp_error($result)) {
                    $this->print_ajax_response('error', $result->get_error_message());
                }
            }
            $this->print_ajax_response('success', $this->module->messages['status-position-updated']);
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
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
                __('Use on these post types:', 'publishpress'),
                [$this, 'settings_post_types_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );
            add_settings_field(
                'always_show_dropdown',
                __('Show the status dropdown menu on the post editing screen:', 'publishpress'),
                [$this, 'settings_always_show_dropdown_option'],
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
        }

        /**
         * Option for whether the blog admin email address should be always notified or not
         *
         * @since 0.7
         */
        public function settings_always_show_dropdown_option()
        {
            $options = [
                'off' => __('Disabled', 'publishpress'),
                'on' => __('Enabled', 'publishpress'),
            ];
            echo '<select id="always_show_dropdown" name="' . $this->module->options_group_name . '[always_show_dropdown]">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"';
                echo selected($this->module->options->always_show_dropdown, $value);
                echo '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }

        /**
         * Validate input from the end user
         *
         * @since 0.7
         */
        public function settings_validate($new_options)
        {
            // Whitelist validation for the post type options
            if (! isset($new_options['post_types'])) {
                $new_options['post_types'] = [];
            }
            $new_options['post_types'] = $this->clean_post_type_options(
                $new_options['post_types'],
                $this->module->post_type_support
            );

            // Whitelist validation for the 'always_show_dropdown' optoins
            if (! isset($new_options['always_show_dropdown']) || $new_options['always_show_dropdown'] != 'on') {
                $new_options['always_show_dropdown'] = 'off';
            }

            return $new_options;
        }

        /**
         * Primary configuration page for custom status class.
         * Shows form to add new custom statuses on the left and a
         * WP_List_Table with the custom status terms on the right
         */
        public function print_configure_view()
        {
            global $publishpress;

            /** Full width view for editing a custom status **/
            if (isset($_GET['action'], $_GET['term-id']) && $_GET['action'] == 'edit-status'): ?>
                <?php
                // Check whether the term exists
                $term_id = sanitize_text_field($_GET['term-id']);
                $status = $this->get_custom_status_by('id', $term_id);
                if (! $status) {
                    echo '<div class="error"><p>' . $this->module->messages['status-missing'] . '</p></div>';

                    return;
                }
                $edit_status_link = $this->get_link(['action' => 'edit-status', 'term-id' => $term_id]);

                $name = (isset($_POST['name'])) ? sanitize_text_field($_POST['name']) : $status->name;
                $description = (isset($_POST['description'])) ? sanitize_textarea_field($_POST['description'])
                    : $status->description;
                $color = (isset($_POST['color'])) ? sanitize_text_field($_POST['color']) : $status->color;
                $icon = (isset($_POST['icon'])) ? sanitize_text_field($_POST['icon']) : $status->icon;
                $icon = str_replace('dashicons|', '', $icon); ?>

                <div id="ajax-response"></div>
                <form method="post" action="<?php
                echo esc_attr($edit_status_link); ?>">
                    <input type="hidden" name="term-id" value="<?php
                    echo esc_attr($term_id); ?>"/>
                    <?php
                    wp_original_referer_field();
                    wp_nonce_field('edit-status'); ?>
                    <table class="form-table">
                        <tr class="form-field form-required">
                            <th scope="row" valign="top"><label for="name"><?php
                                    _e(
                                        'Custom Status',
                                        'publishpress'
                                    ); ?></label></th>
                            <td><input name="name" id="name"
                                       type="text" <?php
                                if (! is_numeric($term_id)) : echo 'disabled="disabled"';
                                endif; ?> value="<?php
                                echo esc_attr($name); ?>" size="40" aria-required="true"/>
                                <?php
                                $publishpress->settings->helper_print_error_or_description(
                                    'name',
                                    __(
                                        'The name is used to identify the status. (Max: 20 characters)',
                                        'publishpress'
                                    )
                                ); ?>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><?php
                                _e('Slug', 'publishpress'); ?></th>
                            <td>
                                <input type="text" name="slug" id="slug"
                                       value="<?php
                                       echo esc_attr($status->slug); ?>" <?php
                                if (! is_numeric(
                                    $term_id
                                )) : echo 'disabled="disabled"';
                                endif; ?> />
                                <?php
                                $publishpress->settings->helper_print_error_or_description(
                                    'slug',
                                    __(
                                        'The slug is the unique ID for the status and is changed when the name is changed.',
                                        'publishpress'
                                    )
                                ); ?>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="description"><?php
                                    _e(
                                        'Description',
                                        'publishpress'
                                    ); ?></label></th>
                            <td>
                            <textarea name="description" id="description" rows="5"
                                      cols="50" <?php
                            if (! is_numeric($term_id)) : echo 'disabled="disabled"';
                            endif; ?> style="width: 97%;"><?php
                                echo esc_textarea($description); ?></textarea>
                                <?php
                                $publishpress->settings->helper_print_error_or_description(
                                    'description',
                                    __(
                                        'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
                                        'publishpress'
                                    )
                                ); ?>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="color"><?php
                                    _e(
                                        'Color',
                                        'publishpress'
                                    ); ?></label></th>
                            <td>

                                <?php
                                echo $this->pp_color_picker(esc_attr($color), 'color') ?>

                                <?php
                                $publishpress->settings->helper_print_error_or_description(
                                    'color',
                                    __('The color is used to identify the status.', 'publishpress')
                                ); ?>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row" valign="top"><label for="icon"><?php
                                    _e('Icon', 'publishpress'); ?></label>
                            </th>
                            <td>
                                <input class="regular-text" type="hidden" id="status_icon" name="icon"
                                       value="<?php
                                       if (isset($icon)) {
                                           echo esc_attr($icon);
                                       } ?>"/>

                                <div id="icon_picker_wrap" id="icon_picker_button" data-target='#status_icon'
                                     data-preview="#icon_picker_preview" class="button dashicons-picker">
                                    <span id="icon_picker_preview" class="dashicons <?php
                                    echo isset($icon) ? esc_attr($icon) : ''; ?>"></span>
                                    <span class="icon_picker_button_label"><?php
                                        echo __('Select Icon', 'publishpress'); ?></span>
                                </div>

                                <?php
                                $publishpress->settings->helper_print_error_or_description(
                                    'status_icon',
                                    __('The icon is used to visually represent the status.', 'publishpress')
                                ); ?>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <?php
                        submit_button(__('Update Status', 'publishpress'), 'primary', 'submit', false); ?>
                        <a class="cancel-settings-link"
                           href="<?php
                           echo esc_url($this->get_link()); ?>"><?php
                            _e('Cancel', 'publishpress'); ?></a>
                    </p>
                </form>
            <?php
            else: ?>
                <?php
                $wp_list_table = new PP_Custom_Status_List_Table();
                $wp_list_table->prepare_items(); ?>

                <div id='col-right' class='pp-custom-status-wrap'>
                    <div class='col-wrap' style="overflow: auto;">
                        <?php
                        $wp_list_table->display(); ?>
                        <?php
                        wp_nonce_field('custom-status-sortable', 'custom-status-sortable'); ?>
                    </div>
                </div>
                <div id='col-left'>
                    <div class='col-wrap'>
                        <div class='form-wrap'>
                            <h3 class='nav-tab-wrapper'>
                                <a href="<?php
                                echo esc_url($this->get_link()); ?>"
                                   class="nav-tab<?php
                                   if (! isset($_GET['action']) || $_GET['action'] != 'add-new') {
                                       echo ' nav-tab-active';
                                   } ?>"><?php
                                    _e('Options', 'publishpress'); ?></a>
                                <a href="<?php
                                echo esc_url($this->get_link(['action' => 'add-new'])); ?>"
                                   class="nav-tab<?php
                                   if (isset($_GET['action']) && $_GET['action'] == 'add-new') {
                                       echo ' nav-tab-active';
                                   } ?>"><?php
                                    _e('Add New', 'publishpress'); ?></a>
                            </h3>
                            <?php
                            if (isset($_GET['action']) && $_GET['action'] == 'add-new'): ?>
                                <?php
                                /** Custom form for adding a new Custom Status term **/ ?>
                                <form class='add:the-list:' action="<?php
                                echo esc_url($this->get_link()); ?>"
                                      method='post' id='addstatus' name='addstatus'>
                                    <div class='form-field form-required'>
                                        <label for='status_name'><?php
                                            _e('Name', 'publishpress'); ?></label>
                                        <input type="text" aria-required='true' size='20' maxlength='20'
                                               id='status_name' name='status_name'
                                               value="<?php
                                               if (! empty($_POST['status_name'])) {
                                                   echo esc_attr(sanitize_text_field($_POST['status_name']));
                                               } ?>"/>
                                        <?php
                                        $publishpress->settings->helper_print_error_or_description(
                                            'name',
                                            __(
                                                'The name is used to identify the status. (Max: 20 characters)',
                                                'publishpress'
                                            )
                                        ); ?>
                                    </div>
                                    <div class='form-field'>
                                        <label for='status_description'><?php
                                            _e(
                                                'Description',
                                                'publishpress'
                                            ); ?></label>
                                        <textarea cols="40" rows='5' id='status_description'
                                                  name='status_description'><?php
                                            if (! empty($_POST['status_description'])) {
                                                echo esc_textarea(sanitize_textarea_field($_POST['status_description']));
                                            } ?></textarea>
                                        <?php
                                        $publishpress->settings->helper_print_error_or_description(
                                            'description',
                                            __(
                                                'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
                                                'publishpress'
                                            )
                                        ); ?>
                                    </div>
                                    <div class='form-field'>
                                        <label for='status_color'><?php
                                            _e('Color', 'publishpress'); ?></label>

                                        <?php
                                        $status_color = isset($_POST['status_color']) ? sanitize_text_field($_POST['status_color']) : '';
                                        echo $this->pp_color_picker(esc_attr($status_color), 'status_color') ?>

                                        <?php
                                        $publishpress->settings->helper_print_error_or_description(
                                            'color',
                                            __('The color is used to identify the status.', 'publishpress')
                                        ); ?>
                                    </div>
                                    <div class='form-field'>
                                        <label for="status_icon"><?php
                                            _e('Icon', 'publishpress'); ?></label>

                                        <?php
                                        $status_icon = isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : 'dashicons-yes'; ?>
                                        <input class='regular-text' type='hidden' id='status_icon' name='icon'
                                               value="<?php
                                               if (isset($status_icon)) {
                                                   echo 'dashicons ' . esc_attr($status_icon);
                                               } ?>"/>

                                        <div id="icon_picker_wrap" id="icon_picker_button" data-target='#status_icon'
                                             data-preview="#icon_picker_preview" class="button dashicons-picker">
                                            <span id="icon_picker_preview" class="dashicons <?php
                                            echo isset($status_icon) ? esc_attr($status_icon) : ''; ?>"></span>
                                            <span class="icon_picker_button_label"><?php
                                                echo __('Select Icon', 'publishpress'); ?></span>
                                        </div>

                                        <?php
                                        $publishpress->settings->helper_print_error_or_description(
                                            'status_icon',
                                            __(
                                                'The icon is used to visually represent the status.',
                                                'publishpress'
                                            )
                                        ); ?>
                                    </div>
                                    <?php
                                    wp_nonce_field('custom-status-add-nonce'); ?>
                                    <?php
                                    echo '<input id="action" name="action" type="hidden" value="add-new" />'; ?>
                                    <p class='submit'><?php
                                        submit_button(
                                            __('Add New Status', 'publishpress'),
                                            'primary',
                                            'submit',
                                            false
                                        ); ?>&nbsp;</p>
                                </form>
                            <?php
                            else: ?>
                                <form class='basic-settings'
                                      action="<?php
                                      echo esc_url($this->get_link(['action' => 'change-options'])); ?>"
                                      method='post'>
                                    <br/>
                                    <p><?php
                                        echo __(
                                            'Please note that checking a box will apply all statuses to that post type.',
                                            'publishpress'
                                        ); ?>
                                    </p>
                                    <?php
                                    settings_fields($this->module->options_group_name); ?>
                                    <?php
                                    do_settings_sections($this->module->options_group_name); ?>
                                    <?php
                                    echo '<input id="publishpress_module_name" name="publishpress_module_name[]" type="hidden" value="' . esc_attr(
                                            $this->module->name
                                        ) . '" />'; ?>
                                    <?php
                                    submit_button(); ?>

                                    <?php
                                    wp_nonce_field('edit-publishpress-settings'); ?>
                                </form>
                            <?php
                            endif; ?>
                        </div>
                    </div>
                </div>

            <?php
            endif; ?>
            <?php
        }

        /**
         * Another temporary fix until core better supports custom statuses
         *
         * @since 0.7.4
         *
         * The preview link for an unpublished post should always be ?p=
         */
        public function fix_preview_link_part_one($preview_link)
        {
            global $pagenow;

            $post = get_post(get_the_ID());

            // Only modify if we're using a pre-publish status on a supported custom post type
            $status_slugs = wp_list_pluck($this->get_custom_statuses(), 'slug');
            if (! $post
                || ! is_admin()
                || 'post.php' != $pagenow
                || ! in_array($post->post_status, $status_slugs)
                || ! in_array($post->post_type, $this->get_post_types_for_module($this->module))
                || strpos($preview_link, 'preview_id') !== false
                || $post->filter == 'sample') {
                return $preview_link;
            }

            return $this->get_preview_link($post);
        }

        /**
         * Another temporary fix until core better supports custom statuses
         *
         * @param $permalink
         * @param $post
         * @param bool $sample
         * @return string
         * @since 0.7.4
         *
         * The preview link for an unpublished post should always be ?p=
         * The code used to trigger a post preview doesn't also apply the 'preview_post_link' filter
         * So we can't do a targeted filter. Instead, we can even more hackily filter get_permalink
         * @see   http://core.trac.wordpress.org/ticket/19378
         */
        public function fix_preview_link_part_two($permalink, $post, $sample = false)
        {
            global $pagenow;

            if (is_int($post)) {
                $postId = $post;
                $post = get_post($post);
            }

            if (! is_object($post)) {
                //Should we be doing anything at all?
                return $permalink;
            }

            if (! in_array($post->post_type, $this->get_post_types_for_module($this->module))) {
                return $permalink;
            }

            //Is this published?
            if ($status_obj = get_post_status_object($post->post_status)) {
                if (! empty($status_obj->public) || ! empty($status_obj->private)) {
                    return $permalink;
                }
            }

            //Are we overriding the permalink? Don't do anything
            if (isset($_POST['action']) && $_POST['action'] == 'sample-permalink') {
                return $permalink;
            }

            //Are we previewing the post from the normal post screen?
            if (($pagenow == 'post.php' || $pagenow == 'post-new.php')
                && ! isset($_POST['wp-preview'])) {
                return $permalink;
            }

            // If it's a scheduled post, we don't add the preview link
            if ($post->post_status === 'future') {
                return $permalink;
            }

            //If it's a sample permalink, not a preview
            if ($sample) {
                return $permalink;
            }

            return $this->get_preview_link($post);
        }

        /**
         * Fix get_sample_permalink. Previosuly the 'editable_slug' filter was leveraged
         * to correct the sample permalink a user could edit on post.php. Since 4.4.40
         * the `get_sample_permalink` filter was added which allows greater flexibility in
         * manipulating the slug. Critical for cases like editing the sample permalink on
         * hierarchical post types.
         *
         * @param string $permalink Sample permalink
         * @param int $post_id Post ID
         * @param string $title Post title
         * @param string $name Post name (slug)
         * @param WP_Post $post Post object
         *
         * @return string $link Direct link to complete the action
         * @since 0.8.2
         *
         */
        public function fix_get_sample_permalink($permalink, $post_id, $title, $name, $post)
        {
            //Should we be doing anything at all?
            if (! in_array($post->post_type, $this->get_post_types_for_module($this->module))) {
                return $permalink;
            }

            //Is this published?
            if (in_array($post->post_status, $this->published_statuses)) {
                return $permalink;
            }

            //Are we overriding the permalink? Don't do anything
            if (isset($_POST['action']) && $_POST['action'] == 'sample-permalink') {
                return $permalink;
            }

            $original_permalink = $permalink;

            list($permalink, $post_name) = $permalink;

            $post_name = $post->post_name ? $post->post_name : sanitize_title($post->post_title);

            // If the post name is still empty, we can't use it to fix the permalink. So, don't do anything.
            if (empty($post_name)) {
                return $original_permalink;
            }

            // Apply the fix
            $post->post_name = $post_name;

            $ptype = get_post_type_object($post->post_type);

            if ($ptype->hierarchical && !in_array($post->post_status, array( 'draft', 'auto-draft', 'future'))) {
                $post->filter = 'sample';

                $uri = get_page_uri($post->ID) . $post_name;

                if ($uri) {
                    $uri = untrailingslashit($uri);
                    $uri = strrev(stristr(strrev($uri), '/'));
                    $uri = untrailingslashit($uri);
                }

                /** This filter is documented in wp-admin/edit-tag-form.php */
                $uri = apply_filters('editable_slug', $uri, $post);

                if (! empty($uri)) {
                    $uri .= '/';
                }

                $permalink = str_replace('%pagename%', "{$uri}%pagename%", $permalink);
            }

            unset($post->post_name);

            return [$permalink, $post_name];
        }

        /**
         * Temporary fix to work around post status check in get_sample_permalink_html
         *
         *
         * The get_sample_permalink_html checks the status of the post and if it's
         * a draft generates a certain permalink structure.
         * We need to do the same work it's doing for custom statuses in order
         * to support this link
         *
         * @see   https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-admin/includes/post.php#L1296
         *
         * @since 0.8.2
         *
         * @param string $return Sample permalink HTML markup
         * @param int $post_id Post ID
         * @param string $new_title New sample permalink title
         * @param string $new_slug New sample permalink kslug
         * @param WP_Post $post Post object
         */
        public function fix_get_sample_permalink_html($return, $post_id, $new_title, $new_slug, $post)
        {
            $status_slugs = wp_list_pluck($this->get_custom_statuses(), 'slug');

            // Remove publish status
            $publishKey = array_search('publish', $status_slugs);
            if (false !== $publishKey) {
                unset($status_slugs[$publishKey]);
            }

            list($permalink, $post_name) = get_sample_permalink($post->ID, $new_title, $new_slug);

            $view_link = false;
            $preview_target = '';

            if (current_user_can('read_post', $post_id)) {
                if (in_array($post->post_status, $status_slugs)) {
                    $view_link = $this->get_preview_link($post);
                    $postId = esc_attr($post->ID);
                    $preview_target = " target='wp-preview-{$postId}'";
                } else {
                    if ('publish' === $post->post_status || 'attachment' === $post->post_type) {
                        $view_link = get_permalink($post);
                    } else {
                        // Allow non-published (private, future) to be viewed at a pretty permalink.
                        $view_link = str_replace(['%pagename%', '%postname%'], $post->post_name, $permalink);
                    }
                }
            }

            // Permalinks without a post/page name placeholder don't have anything to edit
            if (false === strpos($permalink, '%postname%') && false === strpos($permalink, '%pagename%')) {
                $return = '<strong>' . __('Permalink:') . "</strong>\n";

                if (false !== $view_link) {
                    $display_link = urldecode($view_link);
                    $return .= '<a id="sample-permalink" href="' . esc_url(
                            $view_link
                        ) . '"' . $preview_target . '>' . $display_link . "</a>\n";
                } else {
                    $return .= '<span id="sample-permalink">' . $permalink . "</span>\n";
                }

                // Encourage a pretty permalink setting
                if ('' == get_option('permalink_structure') && current_user_can(
                        'manage_options'
                    ) && ! ('page' == get_option('show_on_front') && $post_id == get_option('page_on_front'))) {
                    $return .= '<span id="change-permalinks"><a href="options-permalink.php" class="button button-small" target="_blank">' . __(
                            'Change Permalinks'
                        ) . "</a></span>\n";
                }
            } else {
                if (function_exists('mb_strlen')) {
                    if (mb_strlen($post_name) > 34) {
                        $post_name_abridged = mb_substr($post_name, 0, 16) . '&hellip;' . mb_substr($post_name, -16);
                    } else {
                        $post_name_abridged = $post_name;
                    }
                } else {
                    if (strlen($post_name) > 34) {
                        $post_name_abridged = substr($post_name, 0, 16) . '&hellip;' . substr($post_name, -16);
                    } else {
                        $post_name_abridged = $post_name;
                    }
                }

                $post_name_html = '<span id="editable-post-name">' . $post_name_abridged . '</span>';
                $display_link = str_replace(['%pagename%', '%postname%'], $post_name_html, urldecode($permalink));

                $return = '<strong>' . __('Permalink:') . "</strong>\n";
                $return .= '<span id="sample-permalink"><a href="' . esc_url(
                        $view_link
                    ) . '"' . $preview_target . '>' . $display_link . "</a></span>\n";
                $return .= '&lrm;'; // Fix bi-directional text display defect in RTL languages.
                $return .= '<span id="edit-slug-buttons"><button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="' . __(
                        'Edit permalink'
                    ) . '">' . __('Edit') . "</button></span>\n";
                $return .= '<span id="editable-post-name-full">' . $post_name . "</span>\n";
            }

            return $return;
        }

        /**
         * Get the proper preview link for a post
         *
         * @since 0.8
         */
        private function get_preview_link($post)
        {
            if ('page' == $post->post_type) {
                $args = [
                    'page_id' => $post->ID,
                ];
            } elseif ('post' == $post->post_type) {
                $args = [
                    'p' => $post->ID,
                    'preview' => 'true',
                ];
            } else {
                $args = [
                    'p' => $post->ID,
                    'post_type' => $post->post_type,
                ];
            }

            $args['preview_id'] = $post->ID;


            return add_query_arg($args, trailingslashit(home_url()));
        }

        /**
         * Another temporary fix until core better supports custom statuses
         *
         * @since 0.7.4
         *
         * The preview link for an unpublished post should always be ?p=, even in the list table
         * @see   http://core.trac.wordpress.org/ticket/19378
         */
        public function fix_post_row_actions($actions, $post)
        {
            global $pagenow;

            // Only modify if we're using a pre-publish status on a supported custom post type
            $status_slugs = wp_list_pluck($this->get_custom_statuses(), 'slug');
            if ('edit.php' != $pagenow
                || ! in_array($post->post_status, $status_slugs)
                || ! in_array($post->post_type, $this->get_post_types_for_module($this->module))
                || in_array($post->post_status, ['publish'])) {
                return $actions;
            }

            // 'view' is only set if the user has permission to post
            if (empty($actions['view'])) {
                return $actions;
            }

            if ('page' == $post->post_type) {
                $args = [
                    'page_id' => $post->ID,
                ];
            } elseif ('post' == $post->post_type) {
                $args = [
                    'p' => $post->ID,
                ];
            } else {
                $args = [
                    'p' => $post->ID,
                    'post_type' => $post->post_type,
                ];
            }
            $args['preview'] = 'true';
            $preview_link = add_query_arg($args, home_url());

            $actions['view'] = '<a href="' . esc_url($preview_link) . '" title="' . esc_attr(
                    sprintf(
                        __('Preview &#8220;%s&#8221;'),
                        $post->post_title
                    )
                ) . '" rel="permalink">' . __('Preview') . '</a>';

            return $actions;
        }

        /**
         * Filters slashed post data just before it is inserted into the database.
         *
         * @param array $data An array of slashed post data.
         * @param array $postarr An array of sanitized, but otherwise unmodified post data.
         *
         * @return array
         */
        public function filter_insert_post_data($data, $postarr)
        {
            // Check if we have a post type which this module is activated, before continue.
            if (! in_array($data['post_type'], $this->get_post_types_for_module($this->module))) {
                return $data;
            }

            /*
             * If status is different from draft, auto-draft, and pending, WordPress will automatically
             * set the post_date_gmt to the current date time, like it was being published. But since
             * we provide other post statuses, this produces wrong date for posts not published yet.
             * They should have the post_date_gmt empty, so they are kept as "publish immediately".
             *
             * As of WordPress 5.3, we can opt out of the date setting by setting date_floating on
             * custom statuses instead.
             */
            if (version_compare(get_bloginfo('version'), '5.3', '<')) {
                if (! in_array($data['post_status'], ['publish', 'future'])) {
                    // Check if the dates are the same, indicating they were auto-set.
                    if (get_gmt_from_date(
                            $data['post_date']
                        ) === $data['post_date_gmt'] && $data['post_modified'] === $data['post_date']) {
                        // Reset the date
                        $data['post_date_gmt'] = '0000-00-00 00:00:00';
                    }
                }
            }

            return $data;
        }

        /**
         * @return  self|null
         * @since   1.20.1
         *
         * @static
         *
         */
        public static function getModuleInstance()
        {
            global $publishpress;

            foreach ($publishpress->modules as $pp_module_name => $pp_module) {
                if ($pp_module_name === PP_Custom_Status::MODULE_NAME) {
                    return $pp_module;
                }
            }

            return null;
        }

        /**
         * @return  bool
         * @since   1.20.1
         *
         * @static
         *
         */
        public static function isModuleEnabled()
        {
            $custom_status_module = self::getModuleInstance();

            return ! is_null($custom_status_module) && $custom_status_module->options->enabled === 'on';
        }

        /**
         * @return  array
         * @since   1.20.1
         *
         * @static
         *
         */
        public static function getCustomStatuses()
        {
            $is_module_enabled = self::isModuleEnabled();
            if (! $is_module_enabled) {
                return [];
            }

            global $publishpress;

            return $publishpress->custom_status->get_custom_statuses();
        }
    }
}

/**
 * Custom Statuses uses WordPress' List Table API for generating the custom status management table
 *
 * @since 0.7
 */
class PP_Custom_Status_List_Table extends WP_List_Table
{
    public $callback_args;

    public $default_status;

    /**
     * Construct the extended class
     */
    public function __construct()
    {
        parent::__construct(
            [
                'plural' => 'statuses',
                'singular' => 'status',
                'ajax' => true,
            ]
        );
    }

    public function get_table_classes()
    {
        $classes_list = parent::get_table_classes();
        $class_to_remove = 'fixed';

        $class_to_remove_index = array_search($class_to_remove, $classes_list);
        if ($class_to_remove_index === false) {
            return $classes_list;
        }

        unset($classes_list[$class_to_remove_index]);

        return $classes_list;
    }

    /**
     * Pull in the data we'll be displaying on the table
     *
     * @since 0.7
     */
    public function prepare_items()
    {
        global $publishpress;

        $columns = $this->get_columns();
        $hidden = [
            'position',
        ];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->items = $publishpress->custom_status->get_custom_statuses();
        $total_items = count($this->items);
        $this->default_status = PP_Custom_Status::DEFAULT_STATUS;

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page' => $total_items,
            ]
        );
    }

    /**
     * Table shows (hidden) position, status name, status description, and the post count for each activated
     * post type
     *
     * @return array $columns Columns to be registered with the List Table
     * @since 0.7
     *
     */
    public function get_columns()
    {
        global $publishpress;

        $columns = [
            'position' => __('Position', 'publishpress'),
            'name' => __('Name', 'publishpress'),
            'description' => __('Description', 'publishpress'),
            'icon' => __('Icon', 'publishpress'),
        ];

        $post_types = get_post_types('', 'objects');
        $supported_post_types = $publishpress->helpers->get_post_types_for_module($publishpress->custom_status->module);
        foreach ($post_types as $post_type) {
            if (in_array($post_type->name, $supported_post_types)) {
                $columns[$post_type->name] = $post_type->label;
            }
        }

        return $columns;
    }

    /**
     * Message to be displayed when there are no custom statuses. Should never be displayed, but we'll customize it
     * just in case.
     *
     * @since 0.7
     */
    public function no_items()
    {
        _e('No custom statuses found.', 'publishpress');
    }

    /**
     * Fallback column callback.
     * Primarily used to display post count for each post type
     *
     * @param object $item Custom status as an object
     * @param string $column_name Name of the column as registered in $this->prepare_items()
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_default($item, $column_name)
    {
        global $publishpress;

        // Handle custom post counts for different post types
        $post_types = get_post_types('', 'names');
        if (in_array($column_name, $post_types)) {
            // @todo Cachify this
            $post_count = wp_cache_get("pp_custom_status_count_$column_name");
            if (false === $post_count) {
                $posts = wp_count_posts($column_name);
                $post_status = $item->slug;
                // To avoid error notices when changing the name of non-standard statuses
                if (isset($posts->$post_status)) {
                    $post_count = $posts->$post_status;
                } else {
                    $post_count = 0;
                }
                //wp_cache_set("pp_custom_status_count_$column_name", $post_count);
            }
            $output = sprintf(
                '<a title="See all %1$ss saved as \'%2$s\'" href="%3$s">%4$s</a>',
                esc_attr($column_name),
                esc_attr($item->name),
                $publishpress->helpers->filter_posts_link($item->slug, $column_name),
                esc_html($post_count)
            );

            return $output;
        }
    }

    /**
     * Prepare and echo a single custom status row
     *
     * @since 0.7
     */
    public function single_row($item)
    {
        static $alternate_class = '';
        $alternate_class = ($alternate_class == '' ? ' alternate' : '');
        $row_class = ' class="term-static' . $alternate_class . '"';

        echo '<tr id="term-' . esc_attr($item->term_id) . '"' . $row_class . '>';
        echo $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     * Hidden column for storing the status position
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_position($item)
    {
        return esc_html($item->position);
    }

    /**
     * Displayed column showing the name of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_name($item)
    {
        global $publishpress;

        $item_edit_link = esc_url(
            $publishpress->custom_status->get_link(
                [
                    'action' => 'edit-status',
                    'term-id' => $item->term_id,
                ]
            )
        );

        $output = '<span class="pp-status-color" style="background:' . esc_attr($item->color) . ';"></span>';

        $output .= '<strong>';
        if (! is_numeric($item->term_id)) {
            $output .= '<em>';
        }
        $output .= '<a href="' . $item_edit_link . '">' . esc_html($item->name) . '</a>';
        if ($item->slug == $this->default_status) {
            $output .= ' - ' . __('Default', 'publishpress');
        }
        if (! is_numeric($item->term_id)) {
            $output .= '</em>';
        }
        $output .= '</strong>';

        // Don't allow for any of these status actions when adding a new custom status
        if (isset($_GET['action']) && $_GET['action'] == 'add') {
            return $output;
        }

        $actions = [];
        $actions['edit'] = "<a href='$item_edit_link'>" . __('Edit', 'publishpress') . "</a>";

        if (is_numeric($item->term_id)) {
            $actions['delete delete-status'] = sprintf(
                '<a href="%1$s">' . __('Delete', 'publishpress') . '</a>',
                $publishpress->custom_status->get_link(['action' => 'delete-status', 'term-id' => $item->term_id])
            );
        }

        $output .= $this->row_actions($actions, false);
        $output .= '<div class="hidden" id="inline_' . esc_attr($item->term_id) . '">';
        $output .= '<div class="name">' . esc_html($item->name) . '</div>';
        $output .= '<div class="description">' . esc_html($item->description) . '</div>';
        $output .= '<div class="color">' . esc_html($item->color) . '</div>';
        $output .= '<div class="icon">' . esc_html($item->icon) . '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Displayed column showing the color of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 1.7.0
     *
     */
    /*public function column_color($item)
    {
        return '<span class="pp-status-color" style="background:' . $item->color . ';"></span>';
    }*/

    /**
     * Displayed column showing the description of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_description($item)
    {
        return esc_html($item->description);
    }

    /**
     * Displayed column showing the icon of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 1.7.0
     *
     */
    public function column_icon($item)
    {
        return '<span class="dashicons ' . esc_html($item->icon) . '"></span>';
    }
}
