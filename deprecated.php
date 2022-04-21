<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
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

namespace PublishPress;

/**
 * Deprecated classes, for temporary backward compatibility.
 */

/**
 * Class Util. Use PublishPress\Legacy\Util instead.
 *
 * @package PublishPress
 * @deprecated
 */
class Util extends Legacy\Util
{
}

/**
 * Class Auto_loader. Use PublishPress\Legacy\Auto_loader instead.
 *
 * @package PublishPress
 * @deprecated
 */
class Auto_loader extends Legacy\Auto_loader
{
}

class_alias(
    'PublishPress\\Notifications\\Workflow\\Step\\Event\\Post_StatusTransition',
    'PublishPress\\Notifications\\Workflow\\Step\\Event\\Post_Save'
);
