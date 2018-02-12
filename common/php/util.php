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

if (!function_exists('pp_draft_or_post_title'))
{
    /**
     * Copy of core's _draft_or_post_title without the filters
     *
     * The post title is fetched and if it is blank then a default string is
     * returned.
     *
     * @param int $post_id The post id. If not supplied the global $post is used.
     * @return string The post title if set
     */
    function pp_draft_or_post_title($post_id = 0)
    {
        $post = get_post($post_id);

        return !empty($post->post_title) ? $post->post_title : __('(no title)', 'publishpress');
    }
}
