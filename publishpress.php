<?php
/**
 * Plugin Name: PublishPress
 * Plugin URI: https://publishpress.com/
 * Description: The essential plugin for any WordPress site with multiple writers
 * Author: PublishPress
 * Author URI: https://publishpress.com
 * Version: 1.14.1
 * Text Domain: publishpress
 * Domain Path: /languages
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
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     PublishPress
 * @category    Core
 * @author      PublishPress
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 */

use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\PublishPress_Module;

require_once 'includes.php';

// Core class
class publishpress
{
    use Dependency_Injector, PublishPress_Module;

    // Unique identified added as a prefix to all options
    /**
     * @var PublishPress The one true PublishPress
     */
    private static $instance;

    public $options_group = 'publishpress_';

    public $options_group_name = 'publishpress_options';

    protected $added_menu_page = false;

    protected $menu_slug;

    /**
     * Main PublishPress Instance
     *
     * Insures that only one instance of PublishPress exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @return The one true PublishPress
     */
    public static function instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new publishpress();
            self::$instance->setup_globals();
            self::$instance->setup_actions();
            // Backwards compat for when we promoted use of the $publishpress global
            global $publishpress;
            $publishpress = self::$instance;
        }

        return self::$instance;
    }

    private function setup_globals()
    {
        $this->modules = new stdClass();
    }

    /**
     * Setup the default hooks and actions
     *
     * @since  PublishPress 0.7.4
     * @access private
     * @uses   add_action() To add various actions
     */
    private function setup_actions()
    {
        add_action('init', array($this, 'action_init'), 1000);
        add_action('init', array($this, 'action_init_after'), 1100);

        add_action('init', array($this, 'action_ini_for_admin'), 1010);
        add_action('admin_menu', array($this, 'action_admin_menu'), 9);

        add_action('admin_enqueue_scripts', [$this, 'register_scripts_and_styles']);

        // Fix the order of the submenus
        add_filter('custom_menu_order', [$this, 'filter_custom_menu_order']);

        do_action_ref_array('publishpress_after_setup_actions', array(&$this));
    }

    /**
     * The capabilities need to be set before the modules are loaded,
     * so the submenu items can be displayed correctly right after activate.
     * Otherwise we only see the submenus after visiting the PublishPress settings
     * menu for the first time.
     *
     */
    public static function activation_hook()
    {
        // Add necessary capabilities to allow management of calendar, content overview, notifications
        $genericCaps = array('pp_view_calendar', 'pp_view_content_overview', 'edit_post_subscriptions');

        $roles = array(
            'administrator' => $genericCaps,
            'editor'        => $genericCaps,
            'author'        => $genericCaps,
            'contributor'   => $genericCaps,
        );

        foreach ($roles as $role => $caps)
        {
            PublishPress\Legacy\Util::add_caps_to_role($role, $caps);
        }

        // Additional capabilities
        $roles = array(
            'administrator' => array(apply_filters('pp_manage_roles_cap', 'pp_manage_roles')),
        );

        foreach ($roles as $role => $caps)
        {
            PublishPress\Legacy\Util::add_caps_to_role($role, $caps);
        }
    }

    /**
     * Inititalizes the PublishPresss!
     * Loads options for each registered module and then initializes it if it's active
     */
    public function action_init()
    {
        $this->deactivate_editflow();

        load_plugin_textdomain('publishpress', null, dirname(plugin_basename(__FILE__)) . '/languages/');

        $this->load_modules();

        // Load all of the module options
        $this->load_module_options();

        // Load all of the modules that are enabled.
        // Modules won't have an options value if they aren't enabled
        foreach ($this->modules as $mod_name => $mod_data)
        {
            if (isset($mod_data->options->enabled) && $mod_data->options->enabled == 'on')
            {
                $this->$mod_name->init();
            }
        }

        do_action('pp_init');
    }

    public function deactivate_editflow()
    {
        try
        {
            if (!function_exists('get_plugins'))
            {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $all_plugins = get_plugins();

            // Check if Edit Flow is installed. The folder changes sometimes.
            foreach ($all_plugins as $pluginFile => $data)
            {
                if (isset($data['TextDomain']) && 'edit-flow' === $data['TextDomain'])
                {
                    // Is it activated?
                    if (is_plugin_active($pluginFile))
                    {
                        deactivate_plugins($pluginFile);
                        add_action('admin_notices', array($this, 'notice_editflow_deactivated'));
                    }
                }
            }
        } catch (Exception $e)
        {
        }
    }

    /**
     * Include the common resources to PublishPress and dynamically load the modules
     */
    private function load_modules()
    {
        // We use the WP_List_Table API for some of the table gen
        if (!class_exists('WP_List_Table'))
        {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }

        // PublishPress base module
        if (!class_exists('PP_Module'))
        {
            require_once(PUBLISHPRESS_BASE_PATH . '/common/php/class-module.php');
        }

        // Scan the modules directory and include any modules that exist there
        // $module_dirs = scandir(PUBLISHPRESS_BASE_PATH . '/modules/');
        $default_module_dirs = array(
            'modules-settings'       => PUBLISHPRESS_BASE_PATH,
            'calendar'               => PUBLISHPRESS_BASE_PATH,
            'editorial-metadata'     => PUBLISHPRESS_BASE_PATH,
            'notifications'          => PUBLISHPRESS_BASE_PATH,
            'content-overview'       => PUBLISHPRESS_BASE_PATH,
            'custom-status'          => PUBLISHPRESS_BASE_PATH,
            'roles'                  => PUBLISHPRESS_BASE_PATH,
            'improved-notifications' => PUBLISHPRESS_BASE_PATH,
            'async-notifications'    => PUBLISHPRESS_BASE_PATH,
            'user-groups'            => PUBLISHPRESS_BASE_PATH,

            // @TODO: Move for settings, and remove after cleanup
            'dashboard'              => PUBLISHPRESS_BASE_PATH,
            'editorial-comments'     => PUBLISHPRESS_BASE_PATH,
            'settings'               => PUBLISHPRESS_BASE_PATH,
            'efmigration'            => PUBLISHPRESS_BASE_PATH,
        );

        // Add filters to extend the modules
        $module_dirs = apply_filters('pp_module_dirs', $default_module_dirs);

        // Add add-ons as the last tab
        $module_dirs['addons'] = PUBLISHPRESS_BASE_PATH;

        $class_names = array();

        foreach ($module_dirs as $module_dir => $base_path)
        {
            if (file_exists("{$base_path}/modules/{$module_dir}/{$module_dir}.php"))
            {
                include_once "{$base_path}/modules/{$module_dir}/{$module_dir}.php";

                // Prepare the class name because it should be standardized
                $tmp        = explode('-', $module_dir);
                $class_name = '';
                $slug_name  = '';

                foreach ($tmp as $word)
                {
                    $class_name .= ucfirst($word) . '_';
                    $slug_name  .= $word . '_';
                }

                $slug_name               = rtrim($slug_name, '_');
                $class_names[$slug_name] = 'PP_' . rtrim($class_name, '_');
            }
        }

        // Instantiate PP_Module as $helpers for back compat and so we can
        // use it in this class
        $this->helpers = new PP_Module();

        // Other utils
        require_once(PUBLISHPRESS_BASE_PATH . '/common/php/util.php');

        // Instantiate all of our classes onto the PublishPress object
        // but make sure they exist too
        foreach ($class_names as $slug => $class_name)
        {
            if (class_exists($class_name))
            {
                $slug        = PublishPress\Legacy\Util::sanitize_module_name($slug);
                $this->$slug = new $class_name();
            }
        }

        $this->class_names = $class_names;

        // Supplementary plugins can hook into this, include their own modules
        // and add them to the $publishpress object
        do_action('pp_modules_loaded');
    }

    /**
     * Load all of the module options from the database
     * If a given option isn't yet set, then set it to the module's default (upgrades, etc.)
     */
    public function load_module_options()
    {
        foreach ($this->modules as $mod_name => $mod_data)
        {
            $this->modules->$mod_name->options = get_option($this->options_group . $mod_name . '_options', new stdClass());
            foreach ($mod_data->default_options as $default_key => $default_value)
            {
                if (!isset($this->modules->$mod_name->options->$default_key))
                {
                    $this->modules->$mod_name->options->$default_key = $default_value;
                }
            }
            $this->$mod_name->module = $this->modules->$mod_name;
        }

        do_action('pp_module_options_loaded');
    }

    /**
     * Initialize the plugin for the admin
     */
    public function action_ini_for_admin()
    {
        // Upgrade if need be but don't run the upgrade if the plugin has never been used
        $previous_version = get_option($this->options_group . 'version');
        if ($previous_version && version_compare($previous_version, PUBLISHPRESS_VERSION, '<'))
        {
            foreach ($this->modules as $mod_name => $mod_data)
            {
                if (method_exists($this->$mod_name, 'upgrade'))
                {
                    $this->$mod_name->upgrade($previous_version);
                }
            }
        }

        update_option($this->options_group . 'version', PUBLISHPRESS_VERSION);

        // For each module that's been loaded, auto-load data if it's never been run before
        foreach ($this->modules as $mod_name => $mod_data)
        {
            // If the module has never been loaded before, run the install method if there is one
            if (!isset($mod_data->options->loaded_once) || !$mod_data->options->loaded_once)
            {
                if (method_exists($this->$mod_name, 'install'))
                {
                    $this->$mod_name->install();
                }
                $this->update_module_option($mod_name, 'loaded_once', true);
            }
        }
    }

	/**
     * Update the $publishpress object with new value and save to the database
     */
    public function update_module_option($mod_name, $key, $value)
    {
        if (false === $this->modules->$mod_name->options)
        {
            $this->modules->$mod_name->options = new stdClass();
        }

        $this->modules->$mod_name->options->$key = $value;
        $this->$mod_name->module                 = $this->modules->$mod_name;

        return update_option($this->options_group . $mod_name . '_options', $this->modules->$mod_name->options);
    }

	/**
	 * @param        $page_title
	 * @param        $menu_title
	 * @param        $capability
	 * @param        $menu_slug
	 * @param string $function
	 * @param string $icon_url
	 * @param null   $position
	 */
    public function add_menu_page( $page_title, $capability, $menu_slug, $function = '' ) {
        if ( $this->added_menu_page ) {
            return;
        }

	    add_menu_page(
		    $page_title,
		    esc_html__('PublishPress', 'publishpress'),
		    $capability,
		    $menu_slug,
		    $function,
		    '',
		    26
	    );

	    $this->added_menu_page = true;
	    $this->menu_slug = $menu_slug;
    }

	/**
	 * Returns true if the menu page was already created. Returns false if not.
     *
     * @return bool
	 */
    public function is_menu_page_created() {
        return (bool) $this->added_menu_page;
    }

	/**
     * Returns the menu slug for the menu page.
     *
	 * @return string
	 */
    public function get_menu_slug() {
        return $this->menu_slug;
    }

    /**
     * Add the menu page and call an action for modules add submenus
     */
    public function action_admin_menu()
    {
	    /**
	     * Filters the menu slug. By default, each filter should only set a menu slug if it is empty.
	     * To determine the precedence of menus, use different priorities among the filters.
         *
         * @param string $menu_slug
	     */
        $this->menu_slug = apply_filters('publishpress_admin_menu_slug', $this->menu_slug);

	    /**
	     * Action for adding menu pages.
	     */
	    do_action('publishpress_admin_menu_page');

	    /**
	     * @deprecated
	     */
        do_action('publishpress_admin_menu');

	    /**
	     * Action for adding submenus.
	     */
	    do_action('publishpress_admin_submenu');
    }

    /**
     * Register a new module with PublishPress
     */
    public function register_module($name, $args = array())
    {
        // A title and name is required for every module
        if (!isset($args['title'], $name))
        {
            return false;
        }

        $defaults = array(
            'title'                => '',
            'short_description'    => '',
            'extended_description' => '',
            'icon_class'           => 'dashicons dashicons-admin-generic',
            'slug'                 => '',
            'post_type_support'    => '',
            'default_options'      => array(),
            'options'              => false,
            'configure_page_cb'    => false,
            'configure_link_text'  => __('Configure', 'publishpress'),
            // These messages are applied to modules and can be overridden if custom messages are needed
            'messages'             => array(
                'form-error'          => __('Please correct your form errors below and try again.', 'publishpress'),
                'nonce-failed'        => __('Cheatin&#8217; uh?', 'publishpress'),
                'invalid-permissions' => __('You do not have necessary permissions to complete this action.', 'publishpress'),
                'missing-post'        => __('Post does not exist', 'publishpress'),
            ),
            'autoload'             => false, // autoloading a module will remove the ability to enable or disable it
        );
        if (isset($args['messages']))
        {
            $args['messages'] = array_merge((array)$args['messages'], $defaults['messages']);
        }
        $args                       = array_merge($defaults, $args);
        $args['name']               = $name;
        $args['options_group_name'] = $this->options_group . $name . '_options';

        if (!isset($args['settings_slug']))
        {
            $args['settings_slug'] = 'pp-' . $args['slug'] . '-settings';
        }

        if (empty($args['post_type_support']))
        {
            $args['post_type_support'] = 'pp_' . $name;
        }

        // If there's a Help Screen registered for the module, make sure we
        // auto-load it
        if (!empty($args['settings_help_tab']))
        {
            add_action('load-publishpress_page_' . $args['settings_slug'], array(&$this->$name, 'action_settings_help_menu'));
        }

        $this->modules->$name = (object)$args;
        do_action('pp_module_registered', $name);

        return $this->modules->$name;
    }

    /**
     * Load the post type options again so we give add_post_type_support() a chance to work
     *
     * @see https://publishpress.com/2011/11/17/publishpress-v0-7-alpha2-notes/#comment-232
     */
    public function action_init_after()
    {
        foreach ($this->modules as $mod_name => $mod_data)
        {
            if (isset($this->modules->$mod_name->options->post_types))
            {
                $this->modules->$mod_name->options->post_types = $this->helpers->clean_post_type_options($this->modules->$mod_name->options->post_types, $mod_data->post_type_support);
            }

            $this->$mod_name->module = $this->modules->$mod_name;
        }
    }

    /**
     * Get a module by one of its descriptive values
     */
    public function get_module_by($key, $value)
    {
        $module = false;
        foreach ($this->modules as $mod_name => $mod_data)
        {
            if ($key == 'name' && $value == $mod_name)
            {
                $module = $this->modules->$mod_name;
            } else
            {
                foreach ($mod_data as $mod_data_key => $mod_data_value)
                {
                    if ($mod_data_key == $key && $mod_data_value == $value)
                    {
                        $module = $this->modules->$mod_name;
                    }
                }
            }
        }

        return $module;
    }

    public function update_all_module_options($mod_name, $new_options)
    {
        if (is_array($new_options))
        {
            $new_options = (object)$new_options;
        }

        $this->modules->$mod_name->options = $new_options;
        $this->$mod_name->module           = $this->modules->$mod_name;

        return update_option($this->options_group . $mod_name . '_options', $this->modules->$mod_name->options);
    }

    /**
     * Registers commonly used scripts + styles for easy enqueueing
     *
     * @var  string $hook
     */
    public function register_scripts_and_styles($hook)
    {
        wp_register_style('pp-remodal', PUBLISHPRESS_URL . 'common/css/remodal.css', false, PUBLISHPRESS_VERSION, 'all');
        wp_register_style('pp-remodal-default-theme', PUBLISHPRESS_URL . 'common/css/remodal-default-theme.css', array('pp-remodal'), PUBLISHPRESS_VERSION, 'all');
        wp_register_style('jquery-listfilterizer', PUBLISHPRESS_URL . 'common/css/jquery.listfilterizer.css', false, PUBLISHPRESS_VERSION, 'all');
	    wp_enqueue_style( 'multiple-authors-css', plugins_url( 'common/libs/chosen/chosen.min.css', __FILE__ ), false,
		    PUBLISHPRESS_VERSION, 'all' );

        wp_enqueue_style('pressshack-admin-css', PUBLISHPRESS_URL . 'common/css/pressshack-admin.css', array('pp-remodal', 'pp-remodal-default-theme'), PUBLISHPRESS_VERSION, 'all');
        wp_enqueue_style('pp-admin-css', PUBLISHPRESS_URL . 'common/css/publishpress-admin.css', array('pressshack-admin-css'), PUBLISHPRESS_VERSION, 'all');

	    wp_enqueue_script( 'multiple-authors-chosen',
		    plugins_url( 'common/libs/chosen/chosen.jquery.min.js', __FILE__ ), [ 'jquery' ], PUBLISHPRESS_VERSION );
        wp_register_script('pp-remodal', PUBLISHPRESS_URL . 'common/js/remodal.min.js', array('jquery'), PUBLISHPRESS_VERSION, true);
        wp_register_script('jquery-listfilterizer', PUBLISHPRESS_URL . 'common/js/jquery.listfilterizer.js', array('jquery'), PUBLISHPRESS_VERSION, true);
        wp_register_script('jquery-quicksearch', PUBLISHPRESS_URL . 'common/js/jquery.quicksearch.js', array('jquery'), PUBLISHPRESS_VERSION, true);



        // @compat 3.3
        // Register jQuery datepicker plugin if it doesn't already exist. Datepicker plugin was added in WordPress 3.3
        global $wp_scripts;
        if (!isset($wp_scripts->registered['jquery-ui-datepicker']))
        {
            wp_register_script('jquery-ui-datepicker', PUBLISHPRESS_URL . 'common/js/jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core'), '1.8.16', true);
        }
    }

    public function filter_custom_menu_order($menu_ord)
    {
        global $submenu;
        global $publishpress;

        $menu_slug = $publishpress->get_menu_slug();

        if (isset($submenu[$menu_slug]))
        {
            $submenu_pp  = $submenu[$menu_slug];
            $new_submenu = array();

            // Get the index for the menus.
            $relevantMenus = array(
                'pp-calendar'                           => null,
                'pp-content-overview'                   => null,
                'pp-addons'                             => null,
                'pp-manage-roles'                       => null,
                'pp-manage-capabilities'                => null,
                'edit-tags.php?taxonomy=author'         => null,
                'pp-modules-settings'                   => null,
                'edit.php?post_type=psppnotif_workflow' => null,
            );

            foreach ($submenu_pp as $index => $item)
            {
                if (array_key_exists($item[2], $relevantMenus))
                {
                    $relevantMenus[$item[2]] = $index;
                }
            }

            // Calendar
            if (!is_null($relevantMenus['pp-calendar']))
            {
                $new_submenu[] = $submenu_pp[$relevantMenus['pp-calendar']];

                unset($submenu_pp[$relevantMenus['pp-calendar']]);
            }

            // Content Overview
            if (!is_null($relevantMenus['pp-content-overview']))
            {
                $new_submenu[] = $submenu_pp[$relevantMenus['pp-content-overview']];

                unset($submenu_pp[$relevantMenus['pp-content-overview']]);
            }

            // Notifications
            if (!is_null($relevantMenus['edit.php?post_type=psppnotif_workflow']))
            {
                $new_submenu[] = $submenu_pp[$relevantMenus['edit.php?post_type=psppnotif_workflow']];

                unset($submenu_pp[$relevantMenus['edit.php?post_type=psppnotif_workflow']]);
            }

	        // Roles
	        if (!is_null($relevantMenus['pp-manage-roles']))
	        {
		        $new_submenu[] = $submenu_pp[$relevantMenus['pp-manage-roles']];

		        unset($submenu_pp[$relevantMenus['pp-manage-roles']]);
	        }

	        // Permissions
	        if (!is_null($relevantMenus['pp-manage-capabilities']))
	        {
		        $new_submenu[] = $submenu_pp[$relevantMenus['pp-manage-capabilities']];

		        unset($submenu_pp[$relevantMenus['pp-manage-capabilities']]);
	        }

	        // Authors
	        if (!is_null($relevantMenus['edit-tags.php?taxonomy=author']))
	        {
		        $new_submenu[] = $submenu_pp[$relevantMenus['edit-tags.php?taxonomy=author']];

		        unset($submenu_pp[$relevantMenus['edit-tags.php?taxonomy=author']]);
	        }

            // Check if we have other menu items, except settings and add-ons. They will be added to the end.
            if (count($submenu_pp) > 2)
            {
                // Add the additional items
                foreach ($submenu_pp as $index => $item)
                {
                    if (!in_array($index, $relevantMenus))
                    {
                        $new_submenu[] = $item;
                        unset($submenu_pp[$index]);
                    }
                }
            }

            // Settings
            if (!is_null($relevantMenus['pp-modules-settings']))
            {
                $new_submenu[] = $submenu_pp[$relevantMenus['pp-modules-settings']];

                unset($submenu_pp[$relevantMenus['pp-modules-settings']]);
            }

            // Add-ons
            if (!is_null($relevantMenus['pp-addons']))
            {
                $new_submenu[] = $submenu_pp[$relevantMenus['pp-addons']];

                unset($submenu_pp[$relevantMenus['pp-addons']]);
            }

            $submenu[$menu_slug] = $new_submenu;
        }

        return $menu_ord;
    }

    public function notice_editflow_deactivated()
    {
        ?>
        <div class="updated notice">
            <p><?php _e('Edit Flow was deactivated by PublishPress. If you want to activate it, deactive PublishPress first.', 'publishpress'); ?></p>
        </div>
        <?php
    }
}

function PublishPress()
{
    if (!defined('PUBLISHPRESS_NOTIF_LOADED'))
    {
        require __DIR__ . '/includes_notifications.php';

        // Load the improved notifications
        if (defined('PUBLISHPRESS_NOTIF_LOADED'))
        {
            $plugin = new PublishPress\Notifications\Plugin();
            $plugin->init();
        }
    }

    return publishpress::instance();
}

/**
 * Registered here so the Notifications submenu is displayed right after the
 * plugin is activate.
 *
 * @since 1.9.8
 */
function publishPressRegisterCustomPostTypes()
{
    global $publishpress;

    // Check if the notification module is enabled, before register the post type.
	$options = get_option( 'publishpress_improved_notifications_options', null );

	if ( ! is_object( $options ) ) {
	    return;
    }

    if ( ! isset( $options->enabled ) || $options->enabled !== 'on' ) {
	    return;
    }

    // Create the post type if not exists
    if (!post_type_exists(PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW))
    {
        // Notification Workflows
        register_post_type(
            PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW,
            array(
                'labels'              => array(
                    'name'               => __('Notification Workflows', 'publishpress'),
                    'singular_name'      => __('Notification Workflow', 'publishpress'),
                    'add_new_item'       => __('Add New Notification Workflow', 'publishpress'),
                    'edit_item'          => __('Edit Notification Workflow', 'publishpress'),
                    'search_items'       => __('Search Workflows', 'publishpress'),
                    'menu_name'          => __('Notifications', 'publishpress'),
                    'name_admin_bar'     => __('Notification Workflow', 'publishpress'),
                    'not_found'          => __('No Workflow found', 'publishpress'),
                    'not_found_in_trash' => __('No Workflow found', 'publishpress'),
                ),
                'public'              => false,
                'publicly_queryable'  => false,
                'has_archive'         => false,
                'rewrite'             => array('slug' => 'notification-workflows'),
                'show_ui'             => true,
                'query_var'           => true,
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'can_export'          => true,
                'show_in_admin_bar'   => true,
                'exclude_from_search' => true,
                'show_in_menu'        => $publishpress->get_menu_slug(),
                'menu_position'       => '20',
                'supports'            => array(
                    'title',
                ),
            )
        );
    }
}

add_action('plugins_loaded', 'PublishPress');
add_action('publishpress_admin_menu_page', 'publishPressRegisterCustomPostTypes', 1001);
register_activation_hook(__FILE__, array('publishpress', 'activation_hook'));
