<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \PublishPressBuilder\PackageBuilderTasks
{
    public function __construct()
    {
        parent::__construct();

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
