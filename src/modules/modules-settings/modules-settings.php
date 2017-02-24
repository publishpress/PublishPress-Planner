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

if (!class_exists('PP_Modules_Settings')) {
    /**
     * class PP_Modules_Settings
     * Threaded commenting in the admin for discussion between writers and editors
     *
     * @author batmoo
     */
    class PP_Modules_Settings extends PP_Module
    {
        const SETTINGS_SLUG = 'pp-modules-settings'; 

        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);
            // Register the module with PublishPress
            $args = array(
                'title'                => __('Settings', 'publishpress'),
                'short_description'    => __('PublishPress is the essential plugin for any site with multiple writers', 'publishpress'),
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-admin-settings',
                'slug'                 => 'modules-settings',
                'default_options'      => array(
                    'enabled'    => 'on',
                ),
                'configure_page_cb'   => 'print_configure_view',
                'autoload'            => false,
                'options_page'        => true,
            );

            $this->module = PublishPress()->register_module('modules_settings', $args);
        }

        /**
         * Initialize the rest of the stuff in the class if the module is active
         */
        public function init()
        {
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
        }

        /**
         * Load any of the admin scripts we need but only on the pages we need them
         */
        public function add_admin_scripts()
        {
            global $pagenow;

            wp_enqueue_script('publishpress-modules-settings', $this->module_url . 'lib/modules-settings.js', array('jquery', 'post'), PUBLISHPRESS_VERSION, true);
            wp_enqueue_style('publishpress-modules-css', $this->module_url . 'lib/modules-settings.css', false, PUBLISHPRESS_VERSION, 'all');
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         *
         * @since 0.7
         * @uses add_settings_section(), add_settings_field()
         */
        public function register_settings()
        {

        }

        /**
         * Validate data entered by the user
         *
         * @since 0.7
         *
         * @param array $new_options New values that have been entered by the user
         * @return array $new_options Form values after they've been sanitized
         */
        public function settings_validate($new_options)
        {

        }

        /**
         * Settings page for editorial comments
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            global $publishpress;
            ?>
            <form class="basic-settings" action="<?php echo esc_url(menu_page_url($this->module->settings_slug, false)); ?>" method="post">
                <?php settings_fields($this->module->options_group_name); ?>
                <?php do_settings_sections($this->module->options_group_name); ?>
                <?php echo '<input id="publishpress_module_name" name="publishpress_module_name" type="hidden" value="' . esc_attr($this->module->name) . '" />'; ?>

                <div id="modules-wrapper">
                <?php
                foreach ($publishpress->modules as $mod_name => $mod_data) {
                    if ($mod_data->autoload || $mod_data->slug === 'modules-settings') {
                        continue;
                    }
                    ?>
                    <div id="<?php echo $mod_data->slug; ?>" class="module-box <?php echo ($mod_data->options->enabled == 'on') ? 'module-enabled' : 'module-disabled'; ?>">
                        <div>
                            <?php
                            if (isset($mod_data->icon_class)) {
                                echo '<span class="' . esc_html($mod_data->icon_class) . ' module-icon"></span>';
                            }
                            ?>
                            <h4><?php echo $mod_data->title; ?></h4>

                            <span>
                                <?php
                                echo '<input type="submit" class="button-primary button enable-disable-publishpress-module"';
                                echo ' data-slug="' . $mod_data->slug . '"';
                                if ($mod_data->options->enabled == 'on') {
                                    echo ' style="display:none;"';
                                }
                                echo ' value="' . __('Enable', 'publishpress') . '" />';
                                echo '<input type="submit" class="button-secondary button-remove button enable-disable-publishpress-module"';
                                echo ' data-slug="' . $mod_data->slug . '"';
                                if ($mod_data->options->enabled == 'off') {
                                    echo ' style="display:none;"';
                                }
                                echo ' value="' . __('Disable', 'publishpress') . '" />';
                                ?>
                            </span>
                        </div>

                        <p><?php echo strip_tags($mod_data->short_description); ?></p>
                    </div>
                    <?php
                }
                ?>
                </div>
                <?php
                wp_nonce_field('change-publishpress-module-nonce', 'change-module-nonce', false);
                ?>
            </form>
            <?php
        }
    }
}
