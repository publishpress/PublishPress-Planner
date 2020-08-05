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
 * @copyright   Copyright (C) 2020 PublishPress. All rights reserved.
 */

class RoboFile extends \PublishPressBuilder\PackageBuilderTasks
{
    public function __construct()
    {
        parent::__construct();

        $this->setVersionConstantName('PUBLISHPRESS_VERSION');
        $this->appendToFileToIgnore(
            [
                'webpack.config.js',
                'legacy-tests',
                'src/assets/lib/chosen-v1.8.3/docsupport',
                'src/assets/lib/chosen-v1.8.3/bower.json',
                'src/assets/lib/chosen-v1.8.3/create-example.jquery.html',
                'src/assets/lib/chosen-v1.8.3/create-example.proto.html',
                'src/assets/lib/chosen-v1.8.3/index.proto.html',
                'src/assets/lib/chosen-v1.8.3/options.html',
                'src/assets/lib/chosen-v1.8.3/package.json',
                'vendor/pimple/pimple/.gitignore',
                'vendor/pimple/pimple/.php_cs.dist',
                'vendor/psr/container/.gitignore',
                'vendor/publishpress/wordpress-version-notices/.gitignore',
                'vendor/publishpress/wordpress-version-notices/README.md',
                'vendor/publishpress/wordpress-version-notices/bin',
                'vendor/publishpress/wordpress-version-notices/codeception.dist.yml',
                'vendor/publishpress/wordpress-version-notices/codeception.yml',
                'vendor/publishpress/wordpress-version-notices/tests',
            ]
        );
    }
}
