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

class WP_Test_PublishPress_Class_Module extends WP_UnitTestCase {

    protected static $admin_user_id;
    protected static $PublishPressModule;

    public static function wpSetUpBeforeClass($factory) {
        self::$admin_user_id = $factory->user->create(array('role' => 'administrator'));
    }

    function _flush_roles() {
        // we want to make sure we're testing against the db, not just in-memory data
        // this will flush everything and reload it from the db
        unset($GLOBALS['wp_user_roles']);
        global $wp_roles;
        if (is_object($wp_roles)) {
            $wp_roles->_init();
        }
    }

    function setUp() {
        parent::setUp();

        self::$PublishPressModule = new PP_Module();

        $this->_flush_roles();
    }

    function tearDown() {
        self::$PublishPressModule = null;
    }

    function test_add_caps_to_role() {
        $usergroup_roles = array(
            'administrator' => array('edit_usergroups'),
        );

        foreach($usergroup_roles as $role => $caps) {
            self::$PublishPressModule->add_caps_to_role($role, $caps);
        }

        $user = new WP_User(self::$admin_user_id);

        //Verify before flush
        $this->assertTrue($user->has_cap('edit_usergroups'), 'User did not have role edit_usergroups');

        $this->_flush_roles();

        $this->assertTrue($user->has_cap('edit_usergroups'), 'User did not have role edit_usergroups');
    }

    function test_current_post_type_post_type_set() {
        $_REQUEST['post_type'] = 'not-real';

        $this->assertEquals('not-real', self::$PublishPressModule->get_current_post_type());
    }

    function test_current_post_type_post_screen() {
        set_current_screen('post.php');

        $post_id = $this->factory->post->create(array (
            'post_author' => self::$admin_user_id
        ));

        $_REQUEST['post'] = $post_id;

        $this->assertEquals('post', self::$PublishPressModule->get_current_post_type());

        unset($_REQUEST['post_type']);
        set_current_screen('front');
    }

    function test_current_post_type_edit_screen() {
        set_current_screen('edit.php');

        $this->assertEquals('post', self::$PublishPressModule->get_current_post_type());

        set_current_screen('front');
    }

    function test_current_post_type_custom_post_type() {
        register_post_type('content');
        set_current_screen('content');

        $this->assertEquals('content', self::$PublishPressModule->get_current_post_type());

        _unregister_post_type('content');
        set_current_screen('front');
    }

    public static function wpTearDownAfterClass() {
        self::delete_user(self::$admin_user_id);
    }
}
