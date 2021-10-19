<?php
/**
 * This class can be customized to quickly add a review request system.
 *
 * It includes:
 * - Multiple trigger groups which can be ordered by priority.
 * - Multiple triggers per group.
 * - Customizable messaging per trigger.
 * - Link to review page.
 * - Request reviews on a per user basis rather than per site.
 * - Allows each user to dismiss it until later or permanently seamlessly via AJAX.
 * - Integrates with attached tracking server to keep anonymous records of each triggers effectiveness.
 *   - Tracking Server API: https://gist.github.com/danieliser/0d997532e023c46d38e1bdfd50f38801
 *
 * Original Author: danieliser
 * Original Author URL: https://danieliser.com
 * URL: https://github.com/danieliser/WP-Product-In-Dash-Review-Requests
 */

use PublishPress\Legacy\Auto_loader;
use PublishPress\Reviews\ReviewsController;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class PP_Reviews
 *
 * This class adds a review request system for your plugin or theme to the WP dashboard.
 */
class PP_Reviews extends PP_Module
{
    /**
     * Tracking API Endpoint.
     *
     * @var string
     */
    public static $api_url = '';

    public $module_name = 'reviews';

    public $module_url = '';

    /**
     * @var ReviewController
     */
    private $reviewController;

    /**
     * The constructor
     */
    public function __construct()
    {
        global $publishpress;

        $this->module_url = $this->get_module_url(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title' => __('Reviews', 'publishpress'),
            'short_description' => false,
            'extended_description' => false,
            'module_url' => $this->module_url,
            'icon_class' => 'dashicons dashicons-feedback',
            'slug' => 'reviews',
            'default_options' => [
                'enabled' => 'on',
            ],
            'general_options' => false,
            'autoload' => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = apply_filters(
            'pp_reviews_default_options',
            $args['default_options']
        );

        $this->module = $publishpress->register_module(
            PublishPress\Legacy\Util::sanitize_module_name($this->module_name),
            $args
        );

        parent::__construct();

        Auto_loader::register('\\PublishPress\\Reviews\\', __DIR__ . '/library');

        $this->reviewController = new ReviewsController('publishpress', 'PublishPress');
    }

    /**
     *
     */
    public function init()
    {
        $this->reviewController->init();
    }
}
