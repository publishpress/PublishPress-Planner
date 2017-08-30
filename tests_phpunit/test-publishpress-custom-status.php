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

class WP_Test_PublishPress_Custom_Status extends WP_UnitTestCase
{

    protected static $admin_user_id;
    protected static $PP_Custom_Status;

    public static function wpSetUpBeforeClass($factory)
    {
        self::$admin_user_id = $factory->user->create(array('role' => 'administrator'));

        self::$PP_Custom_Status = new PP_Custom_Status();
        self::$PP_Custom_Status->install();
        self::$PP_Custom_Status->init();
    }

    public static function wpTearDownAfterClass()
    {
        self::delete_user(self::$admin_user_id);
        self::$PP_Custom_Status = null;
    }

    public function setUp()
    {
        parent::setUp();

        global $pagenow;
        $pagenow = 'post.php';
    }

    public function tearDown()
    {
        parent::tearDown();

        global $pagenow;
        $pagenow = 'index.php';
    }

    protected function getFactory()
    {
        if (isset($this->factory) && is_object($this->factory)) {
            return $this->factory;
        } else {
            return self::factory();
        }
    }

    protected function setPermalinkStructure($structure)
    {
        global $wp_rewrite;

        if (method_exists($this, 'set_permalink_structure')) {
            return $this->set_permalink_structure($structure);
        }

        if (method_exists($wp_rewrite, 'set_permalink_structure')) {
            return $wp_rewrite->set_permalink_structure($structure);
        }

        throw new \Exception("Method undefined: set_permalink_structure", 1);
    }

    /**
     * Test that a published post post_date_gmt is not altered
     */
    public function test_insert_post_publish_respect_post_date_gmt()
    {
        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'publish',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date_gmt' => '2016-04-29 12:00:00',
        );

        $id = wp_insert_post($post);

        $out = get_post($id);

        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals(get_date_from_gmt($post['post_date_gmt']), $out->post_date) ;
        $this->assertEquals($post['post_date_gmt'], $out->post_date_gmt);
    }

    /**
     * Test that when post is published, post_date_gmt is set to post_date
     */
    public function test_insert_post_publish_post_date_set()
    {
        $past_date = strftime("%Y-%m-%d %H:%M:%S", strtotime('-1 second'));

        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'publish',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date' => $past_date,
            'post_date_gmt' => ''
        );

        $id = wp_insert_post($post);

        $out = get_post($id);

        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals($out->post_date_gmt, $past_date);
        $this->assertEquals($out->post_date, $past_date);
    }


    /**
     * Test that post_date_gmt is unset when using 'draft' status
     */
    public function test_insert_post_draft_post_date_gmt_empty()
    {
        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'draft',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date_gmt' => ''
        );

        $id = wp_insert_post($post);

        $out = get_post($id);

        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals($out->post_date_gmt, '0000-00-00 00:00:00');
        $this->assertNotEquals($out->post_date, '0000-00-00 00:00:00');
    }


    /**
     * Test that post_date_gmt is unset when using 'pending' status
     */
    public function test_insert_post_pending_post_date_gmt_unset()
    {
        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'pending',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date_gmt' => ''
        );

        $id = wp_insert_post($post);

        $out = get_post($id);

        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals($out->post_date_gmt, '0000-00-00 00:00:00');
        $this->assertNotEquals($out->post_date, '0000-00-00 00:00:00');
    }

    /**
     * Test that post_date_gmt is unset when using 'pitch' status
     */
    public function test_insert_post_pitch_post_date_gmt_unset()
    {
        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'pitch',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date_gmt' => ''
        );

        $id = wp_insert_post($post);

        $out = get_post($id);

        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals($out->post_date_gmt, '0000-00-00 00:00:00');
        $this->assertNotEquals($out->post_date, '0000-00-00 00:00:00');
    }


    /**
     * When a post_date is in the future check that post_date_gmt
     * is not set when the status is not 'future'
     */
    public function test_insert_scheduled_post_gmt_set()
    {
        $future_date = strftime("%Y-%m-%d %H:%M:%S", strtotime('+1 day'));

        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'draft',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date'  => $future_date,
            'post_date_gmt' => ''
        );

        $id = wp_insert_post($post);


        // fetch the post and make sure it matches
        $out = get_post($id);


        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals($out->post_date_gmt, '0000-00-00 00:00:00');
        $this->assertEquals($post['post_date'], $out->post_date);
    }

    /**
     * A post with 'future' status should correctly set post_date_gmt from post_date
     */
    public function test_insert_draft_to_future_post_date_gmt_set()
    {
        $future_date = strftime("%Y-%m-%d %H:%M:%S", strtotime('+1 day'));

        $post = array(
            'post_author' => self::$admin_user_id,
            'post_status' => 'future',
            'post_content' => rand_str(),
            'post_title' => rand_str(),
            'post_date'  => $future_date,
            'post_date_gmt' => ''
        );

        $id = wp_insert_post($post);


        // fetch the post and make sure it matches
        $out = get_post($id);

        $this->assertEquals($post['post_content'], $out->post_content);
        $this->assertEquals($post['post_title'], $out->post_title);
        $this->assertEquals($out->post_date_gmt, $future_date);
        $this->assertEquals($post['post_date'], $out->post_date);
    }

    public function test_fix_sample_permalink_html_on_pitch_when_pretty_permalinks_are_disabled()
    {
        global $pagenow;
        wp_set_current_user(self::$admin_user_id);

        $p = $this->getFactory()->post->create(array(
            'post_status' => 'pitch',
            'post_author' => self::$admin_user_id
        ));

        $pagenow = 'index.php';

        $found = get_sample_permalink_html($p);
        $post = get_post($p);
        $message = 'Pending post';

        $preview_link = get_permalink($post->ID);
        $preview_link = add_query_arg('preview', 'true', $preview_link);

        $this->assertContains('href="' . esc_url($preview_link) . '"', $found, $message);
    }

    public function test_fix_sample_permalink_html_on_pitch_when_pretty_permalinks_are_enabled()
    {
        global $pagenow;

        $this->setPermalinkStructure('/%postname%/');

        $p = $this->getFactory()->post->create(array(
            'post_status' => 'pending',
            'post_name' => 'baz-صورة',
            'post_author' => self::$admin_user_id
        ));

        wp_set_current_user(self::$admin_user_id);

        $pagenow = 'index.php';

        $found = get_sample_permalink_html($p);
        $post = get_post($p);
        $message = 'Pending post';

        $preview_link = get_permalink($post->ID);
        $preview_link = add_query_arg('preview', 'true', $preview_link);

        $this->assertContains('href="' . esc_url($preview_link) . '"', $found, $message);
    }

    public function test_fix_sample_permalink_html_on_publish_when_pretty_permalinks_are_enabled()
    {
        $this->setPermalinkStructure('/%postname%/');

        // Published posts should use published permalink
        $p = $this->getFactory()->post->create(array(
            'post_status' => 'publish',
            'post_name' => 'foo-صورة',
            'post_author' => self::$admin_user_id
        ));

        wp_set_current_user(self::$admin_user_id);

        $found = get_sample_permalink_html($p, null, 'new_slug-صورة');
        $post = get_post($p);
        $message = 'Published post';

        $this->assertContains('href="' . get_option('home') . "/" . $post->post_name . '/"', $found, $message);
        $this->assertContains('>new_slug-صورة<', $found, $message);
    }

    public function test_fix_get_sample_permalink_should_respect_pitch_pages()
    {
        $this->setPermalinkStructure('/%postname%/');

        $page = $this->getFactory()->post->create(array(
            'post_type'  => 'page',
            'post_title' => 'Pitch Page',
            'post_status' => 'pitch',
            'post_author' => self::$admin_user_id
        ));

        $actual = get_sample_permalink($page);
        $this->assertSame(home_url() . '/%pagename%/', $actual[0]);
        $this->assertSame('pitch-page', $actual[1]);
    }

    public function test_fix_get_sample_permalink_should_respect_hierarchy_of_pitch_pages()
    {
        $this->setPermalinkStructure('/%postname%/');

        $parent = $this->getFactory()->post->create(array(
            'post_type'  => 'page',
            'post_title' => 'Parent Page',
            'post_status' => 'publish',
            'post_author' => self::$admin_user_id,
            'post_name' => 'parent-page'
        ));

        $child = $this->getFactory()->post->create(array(
            'post_type'   => 'page',
            'post_title'  => 'Child Page',
            'post_parent' => $parent,
            'post_status' => 'pitch',
            'post_author' => self::$admin_user_id,
        ));


        $actual = get_sample_permalink($child);
        $this->assertSame(home_url() . '/parent-page/%pagename%/', $actual[0]);
        $this->assertSame('child-page', $actual[1]);
    }

    public function test_fix_get_sample_permalink_should_respect_hierarchy_of_publish_pages()
    {
        $this->setPermalinkStructure('/%postname%/');

        $parent = $this->getFactory()->post->create(array(
            'post_type'  => 'page',
            'post_title' => 'Publish Parent Page',
            'post_author' => self::$admin_user_id
        ));

        $child = $this->getFactory()->post->create(array(
            'post_type'   => 'page',
            'post_title'  => 'Child Page',
            'post_parent' => $parent,
            'post_status' => 'publish',
            'post_author' => self::$admin_user_id
        ));

        $actual = get_sample_permalink($child);
        $this->assertSame(home_url() . '/publish-parent-page/%pagename%/', $actual[0]);
        $this->assertSame('child-page', $actual[1]);
    }
}
