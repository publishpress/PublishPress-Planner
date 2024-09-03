(function ($) {
    'use strict';
  
    /**
     * All of the code for admin-facing JavaScript source
     * should reside in this file.
     */
  
    $(document).ready(function () {

        initToolTips();
        function initToolTips() {
            $('.pp-title-tooltip').each(function() {
                var $this = $(this);
                var titleText = $this.attr('title');
        
                if (titleText && titleText !== '') {
                    $this.removeAttr('title');
        
                    var $tooltip = $('<div class="pp-title-tooltip-text"></div>').text(titleText);
                    $('body').append($tooltip);
        
                    $this.hover(function() {
                        $tooltip.show();
        
                        // Adjust the tooltip position to account for the arrow
                        var tooltipTop = $this.offset().top - $tooltip.outerHeight() - 10; // Position 10px above the element
                        var tooltipLeft = $this.offset().left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2);
        
                        $tooltip.css({
                            top: tooltipTop + 'px',
                            left: tooltipLeft + 'px',
                            position: 'absolute'
                        });
                    }, function() {
                        $tooltip.hide();
                    });
                }
            });
        }
  
    });
  
  })(jQuery);  