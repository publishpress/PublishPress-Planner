<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Option;

use PublishPress\Notifications\Workflow\Workflow;
use Twig_Environment;
use WP_Post;

abstract class OptionCheckboxAbstract
{
    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var WP_Post
     */
    protected $post;

    public function __construct()
    {
        $this->addHooks();
    }

    protected function setTwig($twig)
    {
        $this->twig = $twig;
    }

    protected function setPost($post)
    {
        $this->post = $post;
    }

    protected function getPost()
    {
        return $this->post;
    }

    public function addHooks()
    {
        add_filter('publishpress_notif_workflow_options', [$this, 'addToOptionsList'], 10, 3);
        add_filter('publishpress_notif_workflow_option', [$this, 'getWorkflowOption'], 10, 3);

        add_action('publishpress_notif_save_workflow_metadata', [$this, 'setValue'], 10, 2);
    }

    public function addToOptionsList($options, $post, $twig)
    {
        $this->setTwig($twig);
        $this->setPost($post);

        $options[] = $this;

        return $options;
    }

    protected function getName()
    {
        return '';
    }

    protected function getFieldName()
    {
        return '';
    }

    protected function getLabel()
    {
        return '';
    }

    protected function getDescription()
    {
        return '';
    }

    protected function getValue()
    {
        return '';
    }

    public function getWorkflowOption($value, $option, Workflow $workflow)
    {
        $this->setPost($workflow->workflow_post);

        if (!substr_count($option, '_psppno_option_')) {
            $option = '_psppno_option_' . $option;
        }

        if ($option === $this->getName()) {
            $value = $this->getValue();
        }

        return $value;
    }

    public function render()
    {
        $context = [
            'name'        => $this->getFieldName(),
            'label'       => $this->getLabel(),
            'description' => $this->getDescription(),
            'value'       => $this->getValue(),
        ];

        return $this->twig->render('workflow_metabox_option_checkbox.twig', $context);
    }

    public function setValue($postId, $post)
    {
    }
}
