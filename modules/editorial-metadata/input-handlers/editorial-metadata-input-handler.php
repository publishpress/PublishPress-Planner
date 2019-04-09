<?php
defined('ABSPATH') or die('No direct script access allowed.');

if (!class_exists('Editorial_Metadata_Input_Handler')) {
    require_once 'editorial-metadata-input-handler-interface.php';

    abstract class Editorial_Metadata_Input_Handler implements Editorial_Metadata_Input_Handler_Contract
    {
        /**
         * Next node in the chain.
         *
         * @access  private
         * @since   1.20.0
         *
         * @var     Editorial_Metadata_Input_Handler_Contract   $nextHandler
         */
        private $nextHandler = null;

        /**
         * Input type.
         *
         * @access  protected
         * @since   1.20.0
         *
         * @var     string  $type
         */
        protected $type = null;

        /**
         * Obligates derived classes to have their own constructors where they
         * might define its $type.
         *
         * @abstract
         * @since   1.20.0
         */
        abstract public function __construct();

        /**
         * Render input html.
         *
         * @abstract
         * @access  protected
         * @since   1.20.0
         *
         * @param   array   $inputOptions   Input options
         * @param   mixed   $value          Actual input value
         */
        abstract protected function renderInput($inputOptions = array(), $value = null);

        /**
         * Render input-preview html.
         *
         * @abstract
         * @access  protected
         * @since   1.20.0
         *
         * @param   array   $inputOptions   Input options
         * @param   mixed   $value          Actual input value
         */
        abstract protected function renderInputPreview($inputOptions = array(), $value = null);

        /**
         * Get meta-input value html formatted.
         *
         * @abstract
         * @access  protected
         * @since   1.20.0
         *
         * @param   mixed   $value  Actual input value
         *
         * @return  string
         */
        abstract public static function getMetaValueHtml($value = null);

        /**
         * Check if the input can handle a given action based on $type.
         *
         * @final
         * @since   1.20.0
         *
         * @param   string  $type   Input type
         *
         * @return  bool
         */
        final public function canHandle($type)
        {
            return $type === $this->type;
        }

        /**
         * Register a new handler in the chain.
         *
         * @final
         * @since   1.20.0
         *
         * @throws \TypeError
         *
         * @param   Editorial_Metadata_Input_Handler    $handler An input handler instance
         */
        final public function registerHandler($handler)
        {
            if (!($handler instanceof Editorial_Metadata_Input_Handler)) {
                throw new \TypeError('Invalid type for handler parameter.');
            }

            if (!is_null($this->nextHandler)) {
                return $this->nextHandler->registerHandler($handler);
            }

            $this->nextHandler = $handler;
        }

        /**
         * Iterate through the chain until a node handles the action and render
         * the appropriated input based on $type.
         *
         * @final
         * @since   1.20.0
         *
         * @param   string  $type           Input type
         * @param   array   $inputOptions   Input options
         * @param   mixed   $value          Actual input value
         */
        final public function handleHtmlRendering($type, $inputOptions = array(), $value = null)
        {
            if (!$this->canHandle($type)) {
                return !is_null($this->nextHandler)
                    ? $this->nextHandler->handleHtmlRendering($type, $inputOptions, $value)
                    : printf("<p>" . __('This editorial metadata type is not yet supported.', 'publishpress') . "</p>");
            }

            return $this->renderInput($inputOptions, $value);
        }

        /**
         * Iterate through the chain until a node handles the action and render
         * the appropriated input-preview based on $type.
         *
         * @final
         * @since   1.20.0
         *
         * @param   string  $type           Input type
         * @param   array   $inputOptions   Input options
         * @param   mixed   $value          Actual input value
         */
        final public function handlePreviewRendering($type, $inputOptions = array(), $value = null)
        {
            if (!$this->canHandle($type)) {
                return !is_null($this->nextHandler)
                    ? $this->nextHandler->handlePreviewRendering($type, $inputOptions, $value)
                    : printf("<p>" . __('This editorial metadata type is not yet supported.', 'publishpress') . "</p>");
            }

            return $this->renderInputPreview($inputOptions, $value);
        }

        /**
         * Iterate through the chain until a node handles the action and render
         * the appropriated meta-input raw value based on $type.
         *
         * @since   1.20.0
         *
         * @param   string  $type           Input type
         * @param   mixed   $value          Actual input value
         */
        final public function handleMetaValueHtmling($type, $value = null)
        {
            if (!$this->canHandle($type)) {
                return !is_null($this->nextHandler)
                    ? $this->nextHandler->handleMetaValueHtmling($type, $value)
                    : printf("<p>" . __('This editorial metadata type is not yet supported.', 'publishpress') . "</p>");
            }

            return static::getMetaValueHtml($value);
        }

        /**
         * Get input-handler type.
         *
         * @since   1.20.0
         *
         * @return  string
         */
        public function getType() {
            return $this->type;
        }

        /**
         * Return the input type in case the class is treated as string.
         *
         * @since   1.20.0
         *
         * @return  string
         */
        public function __toString()
        {
            return $this->type;
        }

        /**
         * Echoes a Label html element.
         *
         * @static
         * @access  protected
         * @since   1.20.0
         *
         * @param   string  $content            Label content
         * @param   string  $related_input_id   Related input id
         */
        protected static function renderLabel($content, $related_input_id = null)
        {
            printf(
                '<label for="%s">%s</label>',
                $related_input_id,
                $content
            );
        }

        /**
         * Returns a Description html string.
         *
         * @static
         * @access  protected
         * @since   1.20.0
         *
         * @param   string  $description    The description content
         *
         * @return  string
         */
        protected static function generateDescriptionHtml($description)
        {
            if (mb_strlen($description) === 0) {
                return '';
            }

            return sprintf(
                '<span class="%s">%s</span>',
                'description',
                $description
            );
        }

        /**
         * Echoes a Description html element.
         *
         * @static
         * @access  protected
         * @since   1.20.0
         *
         * @param   string  $description    The description content
         */
        protected static function renderDescription($description)
        {
            echo self::generateDescriptionHtml($description);
        }

        /**
         * Render a default placeholder for when there's no value to be shown.
         *
         * @static
         * @access  protected
         * @since   1.20.0
         */
        protected static function renderValuePlaceholder()
        {
            echo '<span class="pp_editorial_metadata_not_set">';
            esc_html_e('Not set', 'default');
            echo '</span>';
        }
    }
}
