<?php

/*****************************************************************
 * This file is generated on composer update command by
 * a custom script. 
 * 
 * Do not edit it manually!
 ****************************************************************/

namespace PublishPress\WordpressVersionNotices;

use function add_action;
use function do_action;

if (! function_exists('add_action')) {
    return;
}

if (! function_exists(__NAMESPACE__ . '\register2Dot1Dot3')) {
    if (! defined('PUBLISHPRESS_WORDPRESS_VERSION_NOTICES_INCLUDED')) {
        define('PUBLISHPRESS_WORDPRESS_VERSION_NOTICES_INCLUDED', __DIR__);
    }
        
    if (! class_exists('PublishPress\WordpressVersionNotices\Versions')) {
        require_once __DIR__ . '/Versions.php';

        add_action('plugins_loaded', [Versions::class, 'initializeLatestVersion'], -150, 0);
    }

    add_action('plugins_loaded', __NAMESPACE__ . '\register2Dot1Dot3', -190, 0);

    function register2Dot1Dot3()
    {
        if (! class_exists('PublishPress\WordpressVersionNotices\ServicesProvider')) {
            $versions = Versions::getInstance();
            $versions->register('2.1.3', __NAMESPACE__ . '\initialize2Dot1Dot3');
        }
    }

    function initialize2Dot1Dot3()
    {
        require_once __DIR__ . '/autoload.php';
        
        if (! defined('PUBLISHPRESS_WORDPRESS_VERSION_NOTICES_VERSION')) {
            define('PUBLISHPRESS_WORDPRESS_VERSION_NOTICES_VERSION', '2.1.3');
        }
        
        do_action('publishpress_wordpress_version_notices_2Dot1Dot3_initialized');
    }
}
