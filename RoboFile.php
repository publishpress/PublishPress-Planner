<?php

/**
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     PublishPress
 * @author      PublishPress
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 */

class RoboFile extends \PublishPressBuilder\PackageBuilderTasks
{
    public function __construct()
    {
        parent::__construct();

        $this->setVersionConstantName('PUBLISHPRESS_VERSION');
        $this->setVersionConstantFiles(['includes.php']);
        $this->appendToFileToIgnore(
            [
                'psalm.xml',
                'jest.config.ts',
                '.phpcs.xml',
                'builder.yml.dist',
                'vendor/publishpress/wordpress-reviews/phpcs.xml.dist',
            ]
        );
    }
}
