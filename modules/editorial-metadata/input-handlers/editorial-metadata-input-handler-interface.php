<?php

defined('ABSPATH') or die('No direct script access allowed.');

if (!class_exists('Editorial_Metadata_Input_Handler_Contract')) {
    interface Editorial_Metadata_Input_Handler_Contract
    {
        /**
         * Register a new handle to the chain.
         *
         * @param Editorial_Metadata_Input_Handler_Contract $handler
         *
         * @return  void
         * @since   1.20.0
         *
         */
        public function registerHandler($handler);

        /**
         * Iterate through the chain until a node handles the action and render
         * the appropriated input based on $type.
         *
         * @param string $type Input type
         * @param array $inputOptions Input options
         * @param mixed $value Actual input value
         * @since   1.20.0
         *
         */
        public function handleHtmlRendering($type, $inputOptions = array(), $value = null);

        /**
         * Iterate through the chain until a node handles the action and render
         * the appropriated input-preview based on $type.
         *
         * @param string $type Input type
         * @param array $inputOptions Input options
         * @param mixed $value Actual input value
         * @since   1.20.0
         *
         */
        public function handlePreviewRendering($type, $inputOptions = array(), $value = null);

        /**
         * Iterate through the chain until a node handles the action and render
         * the appropriated meta-input raw value based on $type.
         *
         * @param string $type Input type
         * @param mixed $value Actual input value
         * @since   1.20.0
         *
         */
        public function handleMetaValueHtmling($type, $value = null);

        /**
         * Get input-handler type.
         *
         * @return  string
         * @since   1.20.0
         *
         */
        public function getType();
    }
}
