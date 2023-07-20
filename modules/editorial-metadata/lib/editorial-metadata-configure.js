(function ($) {
    $(function () {
        $('.delete-status a').on('click', function () {
            if (!confirm(objectL10nMetadata.pp_confirm_delete_term_string)) {
                return false;
            }
        });

        /**
         * Instantiate the drag and drop sorting functionality
         */
        $('.pp-editorial-metadata-wrap #the-list').sortable({
            items: 'tr.term-static',
            placeholder: 'test',
            update: function (event, ui) {
                var affected_item = ui.item;

                // Reset the position indicies for all terms
                $('.pp-editorial-metadata-wrap #the-list tr').removeClass('alternate');

                var terms = [];

                $('.pp-editorial-metadata-wrap #the-list tr.term-static').each(function (index, value) {
                    var term_id = $(this).attr('id').replace('term-', '');
                    terms[index] = term_id;

                    $('td.position', this).html(index + 1);
                    // Update the WP core design for alternating rows
                    if (index % 2 == 0)
                        $(this).addClass('alternate');
                });

                // Prepare the POST
                var params = {
                    action: 'update_term_positions',
                    term_positions: terms,
                    editorial_metadata_sortable_nonce: $('#editorial-metadata-sortable').val()
                };

                // Inform WordPress of our updated positions
                $.post(ajaxurl, params, function (retval) {
                    $('.notice').remove();

                    // If there's a success message, print it. Otherwise we assume we received an error message
                    if (retval.status == 'success') {
                        var message = '<div class="is-dismissible notice notice-success"><p>' + retval.message + '</p></div>';
                    } else {
                        var message = '<div class="is-dismissible notice notice-error"><p>' + retval.message + '</p></div>';
                    }

                    $('.publishpress-admin header').after(message);
                });
            },
            sort: function (e, ui) {
                // Fix the issue with the width of the line while draging, due to the hidden column
                ui.placeholder.find('td').each(function (key, value) {
                    if (ui.helper.find('td').eq(key).is(':visible')) $(this).show();
                    else $(this).hide();
                });
            }
        });
      
        /**
         * clone select type drop down field
         */
        $(document).on('click', '.pp-add-new-option', function (e) {
          e.preventDefault();
          var new_option_html = $('.pp-select-options-wrap .pp-select-options-box:first-child').clone();
          new_option_html.find('.entry-field').val('');
          new_option_html.find('.select_dropdown_default').removeAttr('checked').val($('.pp-select-options-wrap .pp-select-options-box').length);
          new_option_html.find('.delete-button').css('visibility', 'visible');
          new_option_html.find('.delete-button').css('display', '');
          $(".pp-select-options-wrap").append(new_option_html);
          setSelectOptionChanges();
        });
      
        /**
         * delete select type fields
         */
        $(document).on('click', '.pp-select-options-box .delete-button', function (e) {
          e.preventDefault();
          $(this).closest('.pp-select-options-box').remove();
          setSelectOptionChanges();
        });
      
        /**
         * show matched fields when meta data type is changed
         */
        $(document).on('change', '#metadata_type', function (e) {
          var selected_type = $(this).val();
          //hide all optional field
          $('.pp-editorial-optional-field').hide();
          //show type applicable field
          $('.pp-editorial-'+ selected_type +'-field').show();
        });
        if ($('#metadata_type').length > 0) {
          var selected_type = $('#metadata_type').val();
          //hide all optional field
          $('.pp-editorial-optional-field').hide();
          //show type applicable field
          $('.pp-editorial-'+ selected_type +'-field').show();
        }

      /**
       * disable table static term selection
       */
        $('.pp-editorial-metadata-wrap #the-list tr.term-static').disableSelection();
        
        /**
         * load select2
         */
        if ($('.pp_editorial_meta_user').length > 0) {
          $('.pp_editorial_meta_user select').select2();
        }
      
        if ($('.pp_editorial_single_select2').length > 0) {
          $('.pp_editorial_single_select2').select2(
            {
              allowClear: true,
              placeholder: function(){
                $(this).data('placeholder');
              }
            }
          );
        }
      
        if ($('.pp_editorial_meta_multi_select2').length > 0) {
          $('.pp_editorial_meta_multi_select2').select2({
            placeholder: function(){
              $(this).data('placeholder');
            },
            multiple: true
          });
        }
      
      /**
       * make dropdown options sortable
       */
      if ($('.pp-select-options-wrap').length > 0) {
        $(".pp-select-options-wrap").sortable({
          axis: "y",
        }).on("sortupdate", function (event, ui) { setSelectOptionChanges(); } );
      }

      /**
       * Set options after position change
       */
      function setSelectOptionChanges() {
        $('.pp-select-options-wrap .pp-select-options-box').each(function (index) {
          if (index === 0) {
            $(this).find('.delete-button').css('visibility', 'hidden');
            $(this).find('.delete-button').css('display', '');
          } else {
            $(this).find('.delete-button').css('visibility', 'visible');
            $(this).find('.delete-button').css('display', '');
          }
          $(this).find('.select_dropdown_default').val(index);
        });
      }
    });
})(jQuery);
