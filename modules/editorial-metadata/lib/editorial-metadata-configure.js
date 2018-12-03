(function ($) {
    $(function () {
        $('.delete-status a').click(function () {
            if (!confirm(objectL10nMetadata.pp_confirm_delete_term_string)) {
                return false;
            }
        });

        /**
         * Instantiate the drag and drop sorting functionality
         */
        $('#the-list').sortable({
            items: 'tr.term-static',
            placeholder: 'test',
            update: function (event, ui) {
                var affected_item = ui.item;

                // Reset the position indicies for all terms
                $('#the-list tr').removeClass('alternate');

                var terms = [];

                $('#the-list tr.term-static').each(function (index, value) {
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

        $('#the-list tr.term-static').disableSelection();
    });
})(jQuery);
