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

namespace PublishPress\Utility;

use DateTimeZone;

class Date
{
    public function getTimezoneOffset()
    {
        $offset = get_option('gmt_offset', '0');

        if (empty($offset)) {
            $offset = '0';
        }

        return $offset;
    }

    /**
     * @param string $offset
     *
     * @return string
     */
    public function formatTimezoneOffset($offset)
    {
        $offset = (float)$offset;

        if (0.0 === $offset) {
            return '+0000';
        }

        $signal = $offset >= 0 ? '+' : '-';
        $offset = abs($offset);

        $whole = (int)$offset;
        $decimal = $offset - $whole;

        $formattedOffset = $signal . str_pad($whole, 2, '0', STR_PAD_LEFT);
        $formattedOffset .= (0.0 === $decimal) ? '00' : '30';

        return $formattedOffset;
    }

    public function getTimezoneString()
    {
        $timezoneString = get_option('timezone_string');

        if (empty($timezoneString)) {
            $offset = $this->formatTimezoneOffset(get_option('gmt_offset', '0'));

            $timezoneString = new DateTimeZone($offset);
            $timezoneString = $timezoneString->getName();
        }

        return $timezoneString;
    }
}
