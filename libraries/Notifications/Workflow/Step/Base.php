<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step;

use Exception;
use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Notifications\Traits\Metadata;

class Base
{
    use Dependency_Injector, Metadata;

    /**
     * The name of the step. Should be URL safe.
     *
     * @var string
     */
    protected $name = 'generic';

    /**
     * The label for the field in the workflow.
     *
     * @var string
     */
    protected $label = 'Base';

    /**
     * The prefix used on field attributes
     *
     * @var string
     */
    protected $attr_prefix = 'base';

    /**
     * An array with the loaded metadata
     *
     * @var array
     */
    protected $cache_metadata;

    /**
     * Cache for the list of filters
     *
     * @param array
     */
    protected $cache_filters = [];

    /**
     * The constructor
     */
    public function __construct()
    {
        // Add the filter to render the metabox section
        add_filter("publishpress_notif_render_metabox_section_{$this->attr_prefix}", [$this, 'render_metabox_section']);

        // Add the action to save the metabox data
        add_action('publishpress_notif_save_workflow_metadata', [$this, 'save_metabox_data'], 10, 2);
    }

    /**
     * Action to display the metabox
     *
     * @param string $html
     *
     * @return string
     *
     * @throws Exception
     */
    public function render_metabox_section($html)
    {
        if (empty($this->twig_template)) {
            throw new Exception('Undefined twig template for the workflow metabox: ' . $this->name);
        }

        $template_context = [
            'name'  => esc_attr("publishpress_notif[{$this->attr_prefix}_{$this->name}]"),
            'id'    => esc_attr("publishpress_notif_{$this->attr_prefix}_{$this->name}"),
            'value' => esc_attr($this->name),
            'label' => esc_html($this->label),
        ];

        /**
         * Filters the template context for the twig template which will be
         * rendered in the metabox.
         *
         * @param array $template_context
         */
        $template_context = apply_filters(
            "publishpress_notif_workflow_metabox_context_{$this->attr_prefix}_{$this->name}",
            $template_context
        );

        $html .= $this->get_service('twig')->render($this->twig_template, $template_context);

        return $html;
    }

    /**
     * Method called when a notification workflow is saved.
     *
     * @param int $id
     * @param WP_Post $workflow
     */
    public function save_metabox_data($id, $workflow)
    {
        return;
    }

    /**
     * Method to return a list of fields to display in the filter area
     *
     * @param array
     *
     * @return array
     */
    protected function get_filters($filters = [])
    {
        if (!empty($this->cache_filters)) {
            return $this->cache_filters;
        }

        /**
         * Filters the list of filters for the event Comment in the workflow.
         *
         * @param array $filters
         */
        $this->cache_filters = apply_filters("publishpress_notif_workflow_event_{$this->name}_filters", $filters);

        return $this->cache_filters;
    }
}
