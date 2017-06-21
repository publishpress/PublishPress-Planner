<?php
defined('ABSPATH') or die("No direct script access allowed.");

/**
 * @package     EmbedPress
 * @author      PressShack <help@pressshack.com>
 * @copyright   Copyright (C) 2017 PressShack. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.7.1
 */

// Create a helper function for easy SDK access.
function pp_fs() {
    global $pp_fs;

    if ( ! isset( $pp_fs ) ) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/freemius/start.php';

        $pp_fs = fs_dynamic_init( array(
            'id'                  => '984',
            'slug'                => 'publishpress',
            'type'                => 'plugin',
            'public_key'          => 'pk_e6bd6e574d5d8ca753f61e1a2d43c',
            'is_premium'          => false,
            'has_addons'          => false,
            'has_paid_plans'      => false,
            'menu'                => array(
                'slug'           => 'pp-modules-settings',
                'first-path'     => 'admin.php?page=pp-modules-settings',
                'account'        => false,
                'support'        => false,
            ),
        ) );
    }

    return $pp_fs;
}

// Init Freemius.
pp_fs();
// Signal that SDK was initiated.
do_action( 'pp_fs_loaded' );
