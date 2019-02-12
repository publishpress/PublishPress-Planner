<?php
defined('ABSPATH') or die('No direct script access allowed.');

if (!class_exists('Editorial_Metadata_Input_Location_Handler')) {
    require_once 'editorial-metadata-input-text-handler.php';

    class Editorial_Metadata_Input_Location_Handler extends Editorial_Metadata_Input_Text_Handler
    {
        /**
         * Class constructor that defines input type.
         *
         * @since   @todo
         */
        public function __construct()
        {
            $this->type = 'location';
        }

        /**
         * Render input html.
         *
         * @access  protected
         * @since   @todo
         *
         * @param   array   $inputOptions   Input options
         * @param   mixed   $value          Actual input value
         */
        protected function renderInput($inputOptions = array(), $value = null)
        {
            parent::renderInput($inputOptions, $value);

            if (!empty($value)) {
                echo self::generateMapLinkWithLocation($value);
            }
        }

        private static function generateMapLinkWithLocation($location)
        {
            return sprintf(
                '<div>
                    <a href="%s" target="_blank">%s</a>
                </div>',
                "http://maps.google.com/?q={$location}&t=m",
                sprintf(
                    __('View &#8220;%s&#8221; on Google Maps', 'publishpress'),
                    $location
                )
            );
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
            $input_label = isset($inputOptions['label']) ? $inputOptions['label'] : '';
            $input_description = isset($inputOptions['description']) ? $inputOptions['description'] : '';

            self::renderLabel(
                $input_label . self::generateDescriptionHtml($input_description),
                $input_name
            );

            if (mb_strlen((string)$value) > 0) {
                printf(
                    '<span class="pp_editorial_metadata_value">%s</span>',
                    $value
                );

                echo self::generateMapLinkWithLocation($value);
            } else {
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
