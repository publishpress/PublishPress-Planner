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

class WP_Test_PublishPress_Dashboard_Note extends WP_UnitTestCase {

    function test_register_dashboard_note_post_type() {
        //As part of the Edit Flow initialziation process
        //PP_Dashboard_Notepad_Widget should have already
        //created the dashboard-note post type
        $pobj = get_post_type_object('dashboard-note');
        $this->assertEquals('dashboard-note', $pobj->name);

        //Testing PP_Dashboard_Notepad_Widget::init explicitly
        _unregister_post_type('dashboard-note');

        $PublishPressDashboardNote = new PP_Dashboard_Notepad_Widget();

        $this->assertNull(get_post_type_object('dashboard-note'));

        //Should create the post type 'dashboard-note'
        $PublishPressDashboardNote->init();

        $pobj = get_post_type_object('dashboard-note');
        $this->assertEquals('dashboard-note', $pobj->name);
    }

}
