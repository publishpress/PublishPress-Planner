<?php

use PublishPress\WordpressVersionNotices\Autoloader;
use PublishPress\WordpressVersionNotices\ServicesProvider;
use PublishPress\Pimple\Container;

if (! function_exists('untrailingslashit') || ! function_exists('plugin_dir_url')) {
    return;
}

add_action('plugins_loaded', function () {
    if (! defined('PP_VERSION_NOTICES_LOADED')) {
        define('PP_VERSION_NOTICES_VERSION', '2.1.3');
        define('PP_VERSION_NOTICES_BASE_PATH', __DIR__ . '/../');
        define('PP_VERSION_NOTICES_SRC_PATH', __DIR__);

        // @deprecated
        define('PP_VERSION_NOTICES_BASE_URL', untrailingslashit(plugin_dir_url(__FILE__)));

        if (! class_exists('PublishPress\\WordpressVersionNotices\\Autoloader')) {
            require_once __DIR__ . '/Autoloader.php';
        }

        Autoloader::register();

        if (! class_exists('PPVersionNotices\\Autoloader')) {
            require_once __DIR__ . '/deprecated.php';
        }

        $container = new Container();
        $container->register(new ServicesProvider());

        // Load the modules
        $module = $container['module_top_notice'];
        $module->init();

        $module = $container['module_menu_link'];
        $module->init();

        define('PP_VERSION_NOTICES_LOADED', true);
    }
}, -125, 0);
