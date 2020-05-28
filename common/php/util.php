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

if (!function_exists('pp_draft_or_post_title')) {
    /**
     * Copy of core's _draft_or_post_title without the filters
     *
     * The post title is fetched and if it is blank then a default string is
     * returned.
     *
     * @param int $post_id The post id. If not supplied the global $post is used.
     *
     * @return string The post title if set
     */
    function pp_draft_or_post_title($post_id = 0)
    {
        $post = get_post($post_id);

        return !empty($post->post_title) ? $post->post_title : __('(no title)', 'publishpress');
    }
}

if (!function_exists('pp_convert_date_format_to_jqueryui_datepicker')) {
    /**
     * Converts a given WordPress date format to jQuery UI Datepicker format.
     *
     * @param string $date_format_original
     *
     * @return  string
     * @throws  InvalidArgumentException
     *
     * @author  Denison Martins <contact@denison.me>
     *
     * @see     https://codex.wordpress.org/Formatting_Date_and_Time
     * @see     http://api.jqueryui.com/datepicker
     *
     */
    function pp_convert_date_format_to_jqueryui_datepicker($date_format_original)
    {
        if (!is_string($date_format_original)) {
            throw new InvalidArgumentException('The supplied parameter must be a string.');
        }

        if (!preg_match_all('/([\w])/', $date_format_original, $current_date_format_terms)) {
            return $date_format_original;
        }

        $format_terms_map = [
            'j' => 'd',
            'd' => 'dd',
            'l' => 'DD',
            'n' => 'm',
            'm' => 'mm',
            'F' => 'MM',
            'Y' => 'yy',
            'U' => '@',
        ];

        return array_reduce(
            array_unique($current_date_format_terms[0]),
            function ($new_format, $format_term_needle) use ($format_terms_map) {
                if (!isset($format_terms_map[$format_term_needle])) {
                    return $new_format;
                }

                return str_replace($format_term_needle, $format_terms_map[$format_term_needle], $new_format);
            },
            $date_format_original
        );
    }
}

if (!function_exists('pp_get_users_with_author_permissions')) {
    function pp_get_users_with_author_permissions()
    {
        $author_permissions = [
            'administrator',
            'author',
            'editor',
            'contributor',
        ];

        $authors = (array)get_users(
            [
                'role__in' => $author_permissions,
                'fields'   => ['ID', 'display_name'],
                'orderby'  => 'display_name',
                'order'    => 'ASC'
            ]
        );

        return apply_filters('pp_get_users_eligible_to_be_authors', $authors);
    }
}
