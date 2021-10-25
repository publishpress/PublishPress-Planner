<?php

use Codeception\Example;
use Codeception\Stub;

require_once __DIR__ . '/../../common/php/class-module.php';

class classModuleCest
{
    public function _before(WpunitTester $I)
    {
    }

    /**
     * @example {"path_base": "c:\\Users\\test\\wp\\wp-content\\plugins\\publishpress", "file_path": "c:\\Users\\test\\wp\\wp-content\\plugins\\publishpress\\modules\\mod1\\module.php", "url": "http://test.dev/wp-content/plugins/publishpress"}
     */
    public function get_module_urlForWindowsPath(WpunitTester $I, Example $example)
    {
        $module = Stub::make(
            'PP_Module',
            [
                'get_path_base' => $example['path_base'],
                'get_publishpress_url' => $example['url'],
                'dirname' => str_replace('module.php', '', $example['file_path']),
            ]
        );

        $moduleUrl = $module->get_module_url($example['file_path']);

        $I->assertEquals($example['url'] . '/modules/mod1/', $moduleUrl);
    }

    /**
     * @example {"plugin_path_base": "/var/www/html/wp-content/plugins/publishpress", "file_path": "/var/www/html/wp-content/plugins/publishpress/modules/mod1/module.php", "url": "http://test.dev/wp-content/plugins/publishpress"}
     * @example {"plugin_path_base": "/Users/anderson/Local Sites/dev/app/public/wp-content/plugins/publishpress", "file_path": "/Users/anderson/Local Sites/dev/app/public/wp-content/plugins/publishpress/modules/mod1/module.php", "url": "http://test.dev/wp-content/plugins/publishpress"}
     */
    public function get_module_urlForUnixPath(WpunitTester $I, Example $example)
    {
        $module = Stub::make(
            'PP_Module',
            [
                'get_path_base' => $example['plugin_path_base'],
                'get_publishpress_url' => $example['url'],
            ]
        );

        $moduleUrl = $module->get_module_url($example['file_path']);

        $I->assertEquals($example['url'] . '/modules/mod1/', $moduleUrl);
    }
}
