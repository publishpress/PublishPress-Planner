<?php

require 'vendor/autoload.php';

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends PressShack\Builder\Base
{
    public function __construct() {
        $this->plugin_name      = 'publishpress';
        $this->version_constant = 'PUBLISHPRESS_VERSION';
    }
}
