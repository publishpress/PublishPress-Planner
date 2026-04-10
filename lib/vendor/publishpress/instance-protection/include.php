<?php

if (! class_exists('PublishPressInstanceProtection\\InstanceChecker')) {
    if (! class_exists('PublishPressInstanceProtection\\Autoloader')) {
        require_once __DIR__ . '/core/Autoloader.php';
    }

    $autoloader = new PublishPressInstanceProtection\Autoloader();
    $autoloader->register();
    $autoloader->addNamespace('PublishPressInstanceProtection', __DIR__ . '/core');

    add_action('init', function () {
        $mofile = __DIR__ . '/languages/publishpress-instance-protection-' . determine_locale() . '.mo';
        load_textdomain('publishpress-instance-protection', $mofile);
    }, 0);
}
