(function ($) {

    inlineEditCustomStatus = {

        init: function () {
            var t = this, row = $('#inline-edit');

            t.what = '#term-';

            $(document).on('click', '.editinline', function () {
                inlineEditCustomStatus.edit(this);

                return false;
            });

            // prepare the edit row
            row.on('keyup', function (e) {
                if (e.which == 27) return inlineEditCustomStatus.revert();
            });

            $('a.cancel', row).on('click', function () {
                return inlineEditCustomStatus.revert();
            });
            $('a.save', row).on('click', function () {
                return inlineEditCustomStatus.save(this);
            });
            $('input, select', row).on('keydown', function (e) {
                if (e.which == 13) return inlineEditCustomStatus.save(this);
            });

            $('#posts-filter input[type="submit"]').on('mousedown', function (e) {
                t.revert();
            });
        },

        toggle: function (el) {
            var t = this;
            $(t.what + t.getId(el)).css('display') == 'none' ? t.revert() : t.edit(el);
        },

        edit: function (id) {
            var t = this, editRow;
            t.revert();

            if (typeof (id) == 'object')
                id = t.getId(id);

            editRow = $('#inline-edit').clone(true), rowData = $('#inline_' + id);
            $('td', editRow).attr('colspan', $('.widefat:first thead th:visible').length);

            if ($(t.what + id).hasClass('alternate'))
                $(editRow).addClass('alternate');

            $(t.what + id).hide().after(editRow);

            $(':input[name="name"]', editRow).val($('.name', rowData).text());
            $(':input[name="description"]', editRow).val($('.description', rowData).text());
            $(':input[name="color"]', editRow).val($('.color', rowData).text());
            $(':input[name="icon"]', editRow).val($('.icon', rowData).text());

            $(editRow).attr('id', 'edit-' + id).addClass('inline-editor').show();
            $('.ptitle', editRow).eq(0).focus();

            return false;
        },

        save: function (id) {
            var params, fields, tax = $('input[name="taxonomy"]').val() || '';

            if (typeof (id) == 'object')
                id = this.getId(id);

            $('table.widefat .inline-edit-save .waiting').show();

            params = {
                action: 'inline_save_status',
                status_id: id
            };

            fields = $('#edit-' + id + ' :input').serialize();
            params = fields + '&' + $.param(params);

            // make ajax request
            $.post(ajaxurl, params,
                function (r) {
                    var row, new_id;
                    $('table.widefat .inline-edit-save .waiting').hide();

                    if (r) {
                        if (-1 != r.indexOf('<tr')) {
                            $(inlineEditCustomStatus.what + id).remove();
                            new_id = $(r).attr('id');

                            $('#edit-' + id).before(r).remove();
                            row = new_id ? $('#' + new_id) : $(inlineEditCustomStatus.what + id);
                            row.hide().fadeIn();
                        } else
                            $('#edit-' + id + ' .inline-edit-save .error').html(r).show();
                    } else
                        $('#edit-' + id + ' .inline-edit-save .error').html(inlineEditL10n.error).show();
                }
            );
            return false;
        },

        revert: function () {
            var id = $('table.widefat tr.inline-editor').attr('id');

            if (id) {
                $('table.widefat .inline-edit-save .waiting').hide();
                $('#' + id).remove();
                id = id.substr(id.lastIndexOf('-') + 1);
                $(this.what + id).show();
            }

            return false;
        },

        getId: function (o) {
            var id = o.tagName == 'TR' ? o.id : $(o).parents('tr').attr('id'), parts = id.split('-');
            return parts[parts.length - 1];
        }
    };

    $(document).ready(function () {
        inlineEditCustomStatus.init();

        $('.delete-status a').on('click', function () {
            if (!confirm(objectL10ncustomstatus.pp_confirm_delete_status_string))
                return false;
        });

        /**
         * Instantiate the drag and drop sorting functionality
         */
        $('#the-list').sortable({
            items: 'tr.term-static',
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
                    action: 'update_status_positions',
                    status_positions: terms,
                    _wpnonce: $('#custom-status-sortable').val()
                };
                // Inform WordPress of our updated positions
                jQuery.post(ajaxurl, params, function (retval) {
                    $('.notice').remove();
                    // If there's a success message, print it. Otherwise we assume we received an error message
                    if (retval.status == 'success') {
                        var message = '<div class="is-dismissible notice notice-success"><p>' + retval.message + '</p></div>';
                    } else {
                        var message = '<div class="is-dismissible notice notice-error"><p>' + retval.message + '</p></div>';
                    }
                    $('.publishpress-admin header').after(message);
                }).fail(function() {
                    var message = '<div class="is-dismissible notice notice-error"><p>Error</p></div>';
                    $('.publishpress-admin header').after(message);
                });
            }
        });
        $('#the-list tr.term-static').disableSelection();
    });
})(jQuery);
