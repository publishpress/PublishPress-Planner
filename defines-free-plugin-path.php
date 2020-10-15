<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2020 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c ) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress and should be automatically loaded by composer.
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option ) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('PUBLISHPRESS_FREE_PLUGIN_PATH')) {
    /*
     * This constant is used by the Pro plugin to know where the Free plugin is installed.
     * This file should be automatically loaded by Composer. We use this file instead of
     * the includes.php file because we want to avoid loading all that file before it is
     * really needed.
     */
    define('PUBLISHPRESS_FREE_PLUGIN_PATH', __DIR__);
}
