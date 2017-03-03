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

if (!class_exists('PP_Featured')) {
    /**
     * class PP_Featured
     * Threaded commenting in the admin for discussion between writers and editors
     *
     * @author batmoo
     */
    class PP_Featured extends PP_Module
    {
        const SETTINGS_SLUG = 'pp-featured'; 

        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = array(
                'title'                => __('Featured', 'publishpress'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-admin-settings',
                'slug'                 => 'featured',
                'default_options'      => array(
                    'enabled'    => 'on',
                ),
                'configure_page_cb'   => 'print_configure_view',
                'autoload'            => true,
                'options_page'        => true,
            );

            $this->module = PublishPress()->register_module('featured', $args);
        }

        /**
         * Initialize the rest of the stuff in the class if the module is active
         */
        public function init()
        {
            add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
        }

        /**
         * Load any of the admin scripts we need but only on the pages we need them
         */
        public function add_admin_scripts()
        {
            wp_enqueue_style('publishpress-featured-css', $this->module_url . 'lib/featured.css', false, PUBLISHPRESS_VERSION, 'all');
        }

        /**
         * Settings page for editorial comments
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            global $publishpress;

            $countEnabled = 0;
            ?>
            <div class="pp-block-items">
                <?php if ($publishpress->calendar->module->options->enabled == 'on') : ?>
                    <?php $countEnabled++; ?>
                    <a href="index.php?page=calendar" class="pp-block-item">
                        <div class="pp-block-item-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="pp-block-item-description">
                            <h3><?php echo __('Calendar', 'publishpress'); ?></h3>
                            <p><?php echo __('Click here to see a calendar of when all your content is published.', 'publishpress'); ?></p>
                                    
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($publishpress->story_budget->module->options->enabled == 'on') : ?>
                    <?php $countEnabled++; ?>

                    <a href="index.php?page=story-budget" class="pp-block-item spaced">
                        <div class="pp-block-item-icon">
                            <span class="dashicons dashicons-list-view"></span>
                        </div>
                        <div class="pp-block-item-description">
                            <h3><?php echo __('Overview', 'publishpress'); ?></h3>
                            <p><?php echo __('Click here to see a list of all your content, organized by status, category or author.', 'publishpress'); ?></p>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if (empty($countEnabled)) : ?>
                    <p><?php echo __('No featured modules found.', 'publishpress'); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}
