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

/**
 * Tests to test that that testing framework is testing tests. Meta, huh?
 * @package wordpress-plugins-tests
 * @author mbijon
 */
class WP_Test_PublishPress_Starter_Tests extends WP_UnitTestCase
{

    /**
     * Run a simple test to ensure that the tests are running
     */
    public function test_publishpress_exists()
    {
        $this->assertTrue(class_exists('publishpress'));
    }

    /**
     * Verify a minimum version of WordPress is installed
     */
    public function test_wp_version()
    {
        $minimum_version = '3.4.0';
        $running_version = get_bloginfo('version');

        //trunk is always "master" in github terms, but WordPress has a specific way of describing it
        //grab the exact version number to verify that we're on trunk
        if ($running_version == 'master' || $running_version == 'trunk') {
            $file = file_get_contents('https://raw.github.com/WordPress/WordPress/master/wp-includes/version.php');
            preg_match('#\$wp_version = \'([^\']+)\';#', $file, $matches);
            $running_version = $matches[1];
        }

        $this->assertTrue(version_compare($running_version, $minimum_version, '>='));
    }

    /**
     * Test modules loading
     */
    public function test_publishpress_register_module()
    {
        $PublishPress = PublishPress();

        $module_real = strtolower('calendar');
        $module_args = array('title' => $module_real);
        $module_return = $PublishPress->register_module($module_real, $module_args);
        $this->assertTrue($module_real == $module_return->name);
    }
}
