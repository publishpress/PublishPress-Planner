<?php

if (!class_exists('PP_Settings')) {
    class PP_Settings extends PP_Module
    {

        public $module;

        /**
         * Register the module with PublishPress but don't do anything else
         */
        public function __construct()
        {

            // Register the module with PublishPress
            $this->module_url = $this->get_module_url(__FILE__);
            $args             = array(
                'title'                => __('PublishPress', 'publishpress'),
                'short_description'    => __('PublishPress redefines your WordPress publishing workflow.', 'publishpress'),
                'extended_description' => __('Enable any of the features below to take control of your workflow. Custom statuses, email notifications, editorial comments, and more help you and your team save time so everyone can focus on what matters most: the content.', 'publishpress'),
                'module_url'           => $this->module_url,
                'img_url'              => $this->module_url . 'lib/logo-128.png',
                'slug'                 => 'settings',
                'settings_slug'        => 'pp-settings',
                'default_options'      => array(
                    'enabled' => 'on',
                ),
                'configure_page_cb' => 'print_default_settings',
                'autoload'          => true,
            );
            $this->module = PublishPress()->register_module('settings', $args);
        }

        /**
         * Initialize the rest of the stuff in the class if the module is active
         */
        public function init()
        {
            add_action('admin_init', array($this, 'helper_settings_validate_and_save'), 100);

            add_action('admin_print_styles', array($this, 'action_admin_print_styles'));
            add_action('admin_print_scripts', array($this, 'action_admin_print_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts'));
            add_action('admin_menu', array($this, 'action_admin_menu'));

            add_action('wp_ajax_change_publishpress_module_state', array($this, 'ajax_change_publishpress_module_state'));
        }

        /**
         * Add necessary things to the admin menu
         */
        public function action_admin_menu()
        {
            global $publishpress;

            // Select PublishPress icon
            $pp_logo = 'lib/menu-icon.png';

            add_menu_page($this->module->title, $this->module->title, 'manage_options', $this->module->settings_slug, array($this, 'settings_page_controller'), $this->module->module_url . $pp_logo) ;

            foreach ($publishpress->modules as $mod_name => $mod_data) {
                if (isset($mod_data->options->enabled) && $mod_data->options->enabled == 'on'
                    && $mod_data->configure_page_cb && $mod_name != $this->module->name) {
                    add_submenu_page($this->module->settings_slug, $mod_data->title, $mod_data->title, 'manage_options', $mod_data->settings_slug, array($this, 'settings_page_controller')) ;
                }
            }
        }

        public function action_admin_enqueue_scripts()
        {
            if ($this->is_whitelisted_settings_view()) {
                wp_enqueue_script('publishpress-settings-js', $this->module_url . 'lib/settings.js', array('jquery'), PUBLISHPRESS_VERSION, true);
            }
        }

        /**
         * Add settings styles to the settings page
         */
        public function action_admin_print_styles()
        {
            if ($this->is_whitelisted_settings_view()) {
                wp_enqueue_style('publishpress-settings-css', $this->module_url . 'lib/settings.css', false, PUBLISHPRESS_VERSION);
            }
        }

        /**
         * Extra data we need on the page for transitions, etc.
         *
         * @since 0.7
         */
        public function action_admin_print_scripts()
        {
            ?>
            <script type="text/javascript">
                var pp_admin_url = '<?php echo get_admin_url();
            ?>';
            </script>
            <?php

        }

        public function ajax_change_publishpress_module_state()
        {
            global $publishpress;

            if (!wp_verify_nonce($_POST['change_module_nonce'], 'change-publishpress-module-nonce') || !current_user_can('manage_options')) {
                wp_die(__('Cheatin&#8217; uh?'));
            }

            if (!isset($_POST['module_action'], $_POST['slug'])) {
                die('-1');
            }

            $module_action = sanitize_key($_POST['module_action']);
            $slug          = sanitize_key($_POST['slug']);

            $module = $publishpress->get_module_by('slug', $slug);

            if (!$module) {
                die('-1');
            }

            if ($module_action == 'enable') {
                $return = $publishpress->update_module_option($module->name, 'enabled', 'on');
            } elseif ($module_action == 'disable') {
                $return = $publishpress->update_module_option($module->name, 'enabled', 'off');
            }

            if ($return) {
                die('1');
            } else {
                die('-1');
            }
        }

        /**
         * Handles all settings and configuration page requests. Required element for PublishPress
         */
        public function settings_page_controller()
        {
            global $publishpress;

            $requested_module = $publishpress->get_module_by('settings_slug', $_GET['page']);
            if (!$requested_module) {
                wp_die(__('Not a registered PublishPress module', 'publishpress'));
            }
            $configure_callback    = $requested_module->configure_page_cb;
            $requested_module_name = $requested_module->name;

            // Don't show the settings page for the module if the module isn't activated
            if (!$this->module_enabled($requested_module_name)) {
                echo '<div class="message error"><p>' . sprintf(__('Module not enabled. Please enable it from the <a href="%1$s">PublishPress settings page</a>.', 'publishpress'), PUBLISHPRESS_SETTINGS_PAGE) . '</p></div>';
                return;
            }

            $this->print_default_header($requested_module);
            $publishpress->$requested_module_name->$configure_callback();
            $this->print_default_footer($requested_module);
        }

        /**
         *
         */
        public function print_default_header($current_module)
        {
            // If there's been a message, let's display it
            if (isset($_GET['message'])) {
                $message = $_GET['message'];
            } elseif (isset($_REQUEST['message'])) {
                $message = $_REQUEST['message'];
            } elseif (isset($_POST['message'])) {
                $message = $_POST['message'];
            } else {
                $message = false;
            }
            if ($message && isset($current_module->messages[$message])) {
                $display_text = '<span class="publishpress-updated-message publishpress-message">' . esc_html($current_module->messages[$message]) . '</span>';
            }

            // If there's been an error, let's display it
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
            } elseif (isset($_REQUEST['error'])) {
                $error = $_REQUEST['error'];
            } elseif (isset($_POST['error'])) {
                $error = $_POST['error'];
            } else {
                $error = false;
            }
            if ($error && isset($current_module->messages[$error])) {
                $display_text = '<span class="publishpress-error-message publishpress-message">' . esc_html($current_module->messages[$error]) . '</span>';
            }

            if ($current_module->img_url) {
                $page_icon = '<img src="' . esc_url($current_module->img_url) . '" class="module-icon icon32" />';
            } else {
                $page_icon = '<div class="icon32" id="icon-options-general"><br/></div>';
            }
            ?>
            <div class="wrap publishpress-admin">
                <?php if ($current_module->name != 'settings'): ?>
                <?php echo $page_icon;
            ?>
                <h2><a href="<?php echo PUBLISHPRESS_SETTINGS_PAGE;
            ?>"><?php _e('PublishPress', 'publishpress') ?></a>:&nbsp;<?php echo $current_module->title;
            ?><?php if (isset($display_text)) {
        echo $display_text;
    }
            ?></h2>
                <?php else: ?>
                <?php echo $page_icon;
            ?>
                <h2><?php _e('PublishPress', 'publishpress') ?><?php if (isset($display_text)) {
        echo $display_text;
    }
            ?></h2>
                <?php endif;
            ?>

                <div class="explanation">
                    <?php if ($current_module->short_description): ?>
                    <h3><?php echo $current_module->short_description;
            ?></h3>
                    <?php endif;
            ?>
                    <?php if ($current_module->extended_description): ?>
                    <p><?php echo $current_module->extended_description;
            ?></p>
                    <?php endif;
            ?>
                </div>
            <?php

        }

        /**
         * Adds Settings page for PublishPress.
         */
        public function print_default_settings()
        {
            ?>
            <div class="publishpress-modules">
                <?php $this->print_modules();
            ?>
            </div>
            <?php

        }

        public function print_default_footer($current_module)
        {

        }

        public function print_modules()
        {
            global $publishpress;

            if (!count($publishpress->modules)) {
                echo '<div class="message error">' . __('There are no PublishPress modules registered', 'publishpress') . '</div>';
            } else {
                foreach ($publishpress->modules as $mod_name => $mod_data) {
                    if ($mod_data->autoload) {
                        continue;
                    }

                    $classes = array(
                    'publishpress-module',
                );
                    if ($mod_data->options->enabled == 'on') {
                        $classes[] = 'module-enabled';
                    } elseif ($mod_data->options->enabled == 'off') {
                        $classes[] = 'module-disabled';
                    }
                    if ($mod_data->configure_page_cb) {
                        $classes[] = 'has-configure-link';
                    }
                    echo '<div class="' . implode(' ', $classes) . '" id="' . $mod_data->slug . '">';
                    if ($mod_data->img_url) {
                        echo '<img src="' . esc_url($mod_data->img_url) . '" height="24px" width="24px" class="float-right module-icon" />';
                    }
                    echo '<form method="get" action="' . get_admin_url(null, 'options.php') . '">';
                    echo '<h4>' . esc_html($mod_data->title) . '</h4>';
                    if ('on' == $mod_data->options->enabled) {
                        echo '<p>' . wp_kses($mod_data->short_description, 'a') . '</p>';
                    } else {
                        echo '<p>' . strip_tags($mod_data->short_description) . '</p>';
                    }
                    echo '<p class="publishpress-module-actions">';
                    if ($mod_data->configure_page_cb) {
                        $configure_url = add_query_arg('page', $mod_data->settings_slug, get_admin_url(null, 'admin.php'));
                        echo '<a href="' . $configure_url . '" class="configure-publishpress-module button button-primary';
                        if ($mod_data->options->enabled == 'off') {
                            echo ' hidden" style="display:none;';
                        }
                        echo '">' . $mod_data->configure_link_text . '</a>';
                    }
                    echo '<input type="submit" class="button-primary button enable-disable-publishpress-module"';
                    if ($mod_data->options->enabled == 'on') {
                        echo ' style="display:none;"';
                    }
                    echo ' value="' . __('Enable', 'publishpress') . '" />';
                    echo '<input type="submit" class="button-secondary button-remove button enable-disable-publishpress-module"';
                    if ($mod_data->options->enabled == 'off') {
                        echo ' style="display:none;"';
                    }
                    echo ' value="' . __('Disable', 'publishpress') . '" />';
                    echo '</p>';
                    wp_nonce_field('change-publishpress-module-nonce', 'change-module-nonce', false);
                    echo '</form>';
                    echo '</div>';
                }
            }
        }

        /**
         * Given a form field and a description, prints either the error associated with the field or the description.
         *
         * @since 0.7
         *
         * @param string $field The form field for which to check for an error
         * @param string $description Unlocalized string to display if there was no error with the given field
         */
        public function helper_print_error_or_description($field, $description)
        {
            if (isset($_REQUEST['form-errors'][$field])): ?>
                <div class="form-error">
                    <p><?php echo esc_html($_REQUEST['form-errors'][$field]);
            ?></p>
                </div>
            <?php else: ?>
                <p class="description"><?php echo esc_html($description);
            ?></p>
            <?php endif;
        }

        /**
         * Generate an option field to turn post type support on/off for a given module
         *
         * @param object $module PublishPress module we're generating the option field for
         * @param {missing}
         *
         * @since 0.7
         */
        public function helper_option_custom_post_type($module, $args = array())
        {
            $all_post_types = array(
                'post' => __('Posts'),
                'page' => __('Pages'),
            );
            $custom_post_types = $this->get_supported_post_types_for_module();
            if (count($custom_post_types)) {
                foreach ($custom_post_types as $custom_post_type => $args) {
                    $all_post_types[$custom_post_type] = $args->label;
                }
            }

            foreach ($all_post_types as $post_type => $title) {
                echo '<label for="' . esc_attr($post_type) . '">';
                echo '<input id="' . esc_attr($post_type) . '" name="'
                    . $module->options_group_name . '[post_types][' . esc_attr($post_type) . ']"';
                if (isset($module->options->post_types[$post_type])) {
                    checked($module->options->post_types[$post_type], 'on');
                }
                // Defining post_type_supports in the functions.php file or similar should disable the checkbox
                disabled(post_type_supports($post_type, $module->post_type_support), true);
                echo ' type="checkbox" />&nbsp;&nbsp;&nbsp;' . esc_html($title) . '</label>';
                // Leave a note to the admin as a reminder that add_post_type_support has been used somewhere in their code
                if (post_type_supports($post_type, $module->post_type_support)) {
                    echo '&nbsp&nbsp;&nbsp;<span class="description">' . sprintf(__('Disabled because add_post_type_support(\'%1$s\', \'%2$s\') is included in a loaded file.', 'publishpress'), $post_type, $module->post_type_support) . '</span>';
                }
                echo '<br />';
            }
        }

        /**
         * Validation and sanitization on the settings field
         * This method is called automatically/ doesn't need to be registered anywhere
         *
         * @since 0.7
         */
        public function helper_settings_validate_and_save()
        {
            if (!isset($_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['publishpress_module_name'], $_POST['submit']) || !is_admin()) {
                return false;
            }

            global $publishpress;
            $module_name = sanitize_key($_POST['publishpress_module_name']);

            if ($_POST['action'] != 'update'
                || $_POST['option_page'] != $publishpress->$module_name->module->options_group_name) {
                return false;
            }

            if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], $publishpress->$module_name->module->options_group_name . '-options')) {
                wp_die(__('Cheatin&#8217; uh?'));
            }

            $new_options = (isset($_POST[$publishpress->$module_name->module->options_group_name])) ? $_POST[$publishpress->$module_name->module->options_group_name] : array();

            // Only call the validation callback if it exists?
            if (method_exists($publishpress->$module_name, 'settings_validate')) {
                $new_options = $publishpress->$module_name->settings_validate($new_options);
            }

            // Cast our object and save the data.
            $new_options = (object)array_merge((array)$publishpress->$module_name->module->options, $new_options);
            $publishpress->update_all_module_options($publishpress->$module_name->module->name, $new_options);

            // Redirect back to the settings page that was submitted without any previous messages
            $goback = add_query_arg('message', 'settings-updated',  remove_query_arg(array('message'), wp_get_referer()));
            wp_safe_redirect($goback);
            exit;
        }
    }
}
