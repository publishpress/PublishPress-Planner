<?php
defined('ABSPATH') or die('No direct script access allowed.');

if (!class_exists('Editorial_Metadata_Input_Number_Handler')) {
    require_once 'editorial-metadata-input-text-handler.php';

    class Editorial_Metadata_Input_Number_Handler extends Editorial_Metadata_Input_Text_Handler
    {
        /**
         * Class constructor that defines input type.
         *
         * @since   @todo
         */
        public function __construct()
        {
            $this->type = 'number';
        }

        /**
         * Render input-preview html.
         *
         * @access  protected
         * @since   @todo
         *
         * @param   array   $inputOptions   Input options
         * @param   mixed   $value          Actual input value
         */
        protected function renderInputPreview($inputOptions = array(), $value = null)
        {
            $input_name = isset($inputOptions['name']) ? $inputOptions['name'] : '';

            if (mb_strlen((string)$value) > 0) {
                self::renderInput($inputOptions, $value);
            } else {
                $input_label = isset($inputOptions['label']) ? $inputOptions['label'] : '';
                $input_description = isset($inputOptions['description']) ? $inputOptions['description'] : '';

                self::renderLabel(
                    $input_label . self::generateDescriptionHtml($input_description),
                    $input_name
                );

                self::renderValuePlaceholder();
            }

            printf(
                '<input
                    type="hidden"
                    id="%s"
                    name="%1$s"
                    value="%2$s"
                />',
                $input_name,
                $value
            );
        }
    }
}
