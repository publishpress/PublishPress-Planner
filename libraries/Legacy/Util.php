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

namespace PublishPress\Legacy;

class Util
{
    /**
     * Checks for the current post type
     *
     * @return string|null $post_type The post type we've found, or null if no post type
     */
    public static function get_current_post_type()
    {
        global $post, $typenow, $pagenow, $current_screen;

        // get_post() needs a variable
        $post_id = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : false;

        if ($post && $post->post_type) {
            $post_type = $post->post_type;
        } elseif ($typenow) {
            $post_type = $typenow;
        } elseif ($current_screen && ! empty($current_screen->post_type)) {
            $post_type = $current_screen->post_type;
        } elseif (isset($_REQUEST['post_type'])) {
            $post_type = sanitize_key($_REQUEST['post_type']);
        } elseif ('post.php' == $pagenow
                  && $post_id
                  && ! empty(get_post($post_id)->post_type)) {
            $post_type = get_post($post_id)->post_type;
        } elseif ('edit.php' == $pagenow && empty($_REQUEST['post_type'])) {
            $post_type = 'post';
        } else {
            $post_type = null;
        }

        return $post_type;
    }

    /**
     * Collect all of the active post types for a given module
     *
     * @param object $module Module's data
     *
     * @return array $post_types All of the post types that are 'on'
     */
    public static function get_post_types_for_module($module)
    {
        $post_types = [];

        if (isset($module->options->post_types) && is_array($module->options->post_types)) {
            foreach ($module->options->post_types as $post_type => $value) {
                if ('on' == $value) {
                    $post_types[] = $post_type;
                }
            }
        }

        return $post_types;
    }

    /**
     * Sanitizes the module name, making sure we always have only
     * valid chars, replacing - with _.
     *
     * @param  string $name
     *
     * @return string
     */
    public static function sanitize_module_name($name)
    {
        return str_replace('-', '_', $name);
    }

    /**
     * Adds an array of capabilities to a role.
     *
     * @since 1.9.8
     *
     * @param string $role A standard WP user role like 'administrator' or 'author'
     * @param array  $caps One or more user caps to add
     */
    public static function add_caps_to_role($role, $caps)
    {
        // In some contexts, we don't want to add caps to roles
        if (apply_filters('pp_kill_add_caps_to_role', false, $role, $caps)) {
            return;
        }

        global $wp_roles;

        if ($wp_roles->is_role($role)) {
            $role = get_role($role);

            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * @return bool
     */
    public static function isGutenbergEnabled()
    {
        $isEnabled = defined('GUTENBERG_VERSION');

        // Is WordPress 5?
        if ( ! $isEnabled) {
            $wpVersion = get_bloginfo('version');

            $isEnabled = version_compare($wpVersion, '5.0', '>=');
        }

        return $isEnabled;
    }

    public static function hasAnyValidLicenseKeySet()
    {
        $addons = apply_filters('allex_addons', [], 'publishpress');

        if (empty($addons)) {
            return false;
        }

        foreach ($addons as $addon) {
            if ( ! empty($addon['license_key']) && $addon['license_status'] === 'valid') {
                return true;
            }
        }

        return false;
    }
}
