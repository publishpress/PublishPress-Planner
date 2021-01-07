jQuery(document).ready(function ($) {

    $('a.show-more').on('click', function () {
        var parent = $(this).closest('td.day-unit');
        $('ul li', parent).removeClass('hidden');
        $(this).hide();
        return false;
    });

    $(document).on('click', function (event) {
        // Did we click on a list item? How do we figure that out?
        // First let's see if we directly clicked on a .day-item
        var target = $(event.target);

        // Case where we've clicked on the list item directly
        if (target.hasClass('day-item')) {
            if (target.hasClass('active')) {
                return;
            } else if (target.hasClass('post-insert-overlay')) {
                return;
            } else {
                publishpress_calendar_close_overlays();

                return;
            }
        }

        // Case where we've clicked in the list item
        target = target.closest('.day-item');
        if (target.length) {
            if (target.hasClass('day-item')) {
                if (target.hasClass('active')) {
                    return;
                } else if (target.hasClass('post-insert-overlay')) {
                    return;
                } else {
                    publishpress_calendar_close_overlays();

                    return;
                }
            }
        }

        target = $(event.target).closest('#ui-datepicker-div');
        if (target.length) {
            return;
        }

        target = $(event.target).closest('.post-insert-dialog');
        if (target.length) {
            return;
        }

        publishpress_calendar_close_overlays();
    });

    /**
     * Listen for click event and substitute correct type of replacement
     * html given the input type
     */
    $('.day-unit').on('click', '.editable-value', function (event) {
        // Reset anything that was currently being edited.
        reset_editorial_metadata();
        var t = this,
            $editable_el = $(this).addClass('hidden').next('.editable-html');

        if ($editable_el.children().first().is('select')) {
            $editable_el.find('option')
                .each(function () {
                    if ($(this).text() == $(t).text()) {
                        $(this).prop('selected', true);
                    }
                });
        }

        $editable_el.removeClass('hidden')
            .addClass('editing')
            .closest('.day-item')
            .find('.item-actions .save')
            .removeClass('hidden');

    });

    // Save the editorial metadata we've changed
    $('.day-unit').on('click', 'a#save-editorial-metadata', function () {
        var post_id = $(this).attr('class').replace('post-', '');
        save_editorial_metadata(post_id);
        return false;
    });

    function reset_editorial_metadata () {
        $('.editing').removeClass('editing')
            .addClass('hidden')
            .prev()
            .removeClass('hidden');

        $('.item-actions .save').addClass('hidden');
    }

    /**
     * save_editorial_metadata
     * Save the editorial metadata that's been edited (whatever is marked '#actively-editing').
     *
     * @param post_id Id of post we're editing
     */
    function save_editorial_metadata (post_id) {
        var metadata_info = {
            action: 'pp_calendar_update_metadata',
            nonce: $('#pp-calendar-modify').val(),
            metadata_type: $('.editing').data('type'),
            metadata_value: $('.editing').children().first().val(),
            metadata_term: $('.editing').data('metadataterm'),
            post_id: post_id
        };

        $('.editing').addClass('hidden')
            .after($('<div class="spinner spinner-calendar"></div>').show());

        // Send the request
        jQuery.ajax({
            type: 'POST',
            url: (ajaxurl) ? ajaxurl : wpListL10n.url,
            data: metadata_info,
            success: function (x) {
                var val = $('.editing').children().first();
                if (val.is('select')) {
                    val = val.find('option:selected').text();
                } else {
                    val = val.val();
                }

                $('.editing').next()
                    .remove();

                $('.editing').addClass('hidden')
                    .removeClass('editing')
                    .prev()
                    .removeClass('hidden')
                    .text(val);

                reset_editorial_metadata();
            },
            error: function (r) {
                $('.editing').next('.spinner').replaceWith('<div class="is-dismissible notice notice-error"><p>Error saving metadata.</p></div>');
            }
        });

    }

    // Hide a message. Used by setTimeout()
    function publishpress_calendar_hide_message () {
        $('.notice').fadeOut(function () {
            $(this).remove();
        });
    }

    // Close out all of the overlays with your escape key,
    // or by clicking anywhere other than inside an existing overlay
    $(document).on('keydown', function (event) {
        if (event.keyCode == '27') {
            publishpress_calendar_close_overlays();
        }
    });

    function publishpress_calendar_day_item_click (event) {

        // Ignores the event if it was a click inside the overlay window
        if ($(event.target).parents('div.item-overlay').length > 0) {
            return;
        }

        var $this = $(this);

        publishpress_calendar_close_overlays();

        $this.addClass('active');
        $this.parents('td').addClass('popup-loaded');

        var overlay = $this.find('.item-static');

        overlay.removeClass('item-static');
        overlay.addClass('item-overlay');
    }

    $('.day-item').on('click', publishpress_calendar_day_item_click);

    function publishpress_calendar_close_overlays () {
        reset_editorial_metadata();
        $('.day-item.active').removeClass('active')
            .find('.item-overlay').removeClass('item-overlay')
            .addClass('item-static');

        $('.post-insert-overlay').remove();

        $('.popup-loaded').removeClass('popup-loaded');
    }

    /**
     * Instantiates drag and drop sorting for posts on the calendar
     */
    $('td.day-unit ul').sortable({
        items: 'li.day-item.sortable',
        connectWith: 'td.day-unit ul',
        placeholder: 'ui-state-highlight',
        start: function (event, ui) {
            $(this).disableSelection();
            publishpress_calendar_close_overlays();
            $('td.day-unit ul li').unbind('click.pp-calendar-show-overlay');
            $(this).css('cursor', 'move');
        },
        sort: function (event, ui) {
            $('td.day-unit').removeClass('ui-wrapper-highlight');
            $('.ui-state-highlight').closest('td.day-unit').addClass('ui-wrapper-highlight');
        },
        stop: function (event, ui) {
            $(this).css('cursor', 'auto');
            $('td.day-unit').removeClass('ui-wrapper-highlight');
            // Only do a POST request if we moved the post off today
            if ($(this).closest('.day-unit').attr('id') != $(ui.item).closest('.day-unit').attr('id')) {
                var post_id = $(ui.item).attr('id').split('-');
                post_id = post_id[post_id.length - 1];
                var prev_date = $(this).closest('.day-unit').attr('id');
                var next_date = $(ui.item).closest('.day-unit').attr('id');
                var nonce = $(document).find('#pp-calendar-modify').val();
                $('.notice').remove();
                $('li.ajax-actions .waiting').show();
                // make ajax request
                var params = {
                    action: 'pp_calendar_drag_and_drop',
                    post_id: post_id,
                    prev_date: prev_date,
                    next_date: next_date,
                    nonce: nonce
                };
                var self = this;
                jQuery.post(ajaxurl, params,
                    function (response) {
                        $('li.ajax-actions .waiting').hide();

                        if (response.status == 'error') {
                            $('header').append($('<div class="is-dismissible notice notice-error"><p>' + response.message + '</p></div>'));
                            setTimeout(publishpress_calendar_hide_message, 10000);
                            $(self).sortable("cancel");
                            return;
                        }

                        // Update the icon in case it switched between publish and future
                        var data = response.data,
                            $icon_span = $(ui.item).find('.dashicons');

                        if (data.post_status === 'future' || data.post_status === 'publish') {
                            $icon_span.removeClass();
                            $icon_span.addClass('dashicons');

                            if (data.post_status === 'future') {
                                $icon_span.addClass('dashicons-calendar-alt');
                            } else if (data.post_status === 'publish') {
                                $icon_span.addClass('dashicons-yes');
                            }
                        }

                    }
                );
            }
            $(this).enableSelection();
        }
    });

    // Enables quick creation/edit of drafts on a particular date from the calendar
    var EFQuickPublish = {
        /**
         * When user clicks the '+' on an individual calendar date or
         * double clicks on a calendar square pop up a form that allows
         * them to create a post for that date
         */
        init: function () {

            var $day_units = $('td.day-unit');

            // Bind the click on the calendar square
            $day_units.on('click.publishPress.quickPublish', EFQuickPublish.open_quickpost_dialogue);
            $day_units.on('dblclick.publishPress.quickPublish', EFQuickPublish.open_quickpost_dialogue);
            $day_units.on('mouseover', EFQuickPublish.show_quickpost_label);
            $day_units.on('mouseout', EFQuickPublish.hide_quickpost_label);
            // $day_units.find('li.day-item').on('mouseenter', EFQuickPublish.hide_quickpost_label);
        }, // init

        /**
         * When user hovers the day's cell, displays the label about
         * click to create new content.
         */
        show_quickpost_label: function (e) {
            var $this = $(this),
                target = e.srcElement || e.target;
            $target = $(target);

            e.preventDefault();
            e.stopPropagation();

            if ($target.is('td.day-unit') || $target.is('.post-list')) {
                $('.schedule-new-post-label').stop().hide();
                $this.find('.schedule-new-post-label').stop().show();
            }
        },

        /**
         * When user gets out of the day's cell, hides the label about
         * click to create new content.
         */
        hide_quickpost_label: function (e) {
            $this = $(this);

            // Is it another day cell?
            if ($(e.toElement).is('td') && e.toElement !== this) {
                $this.find('.schedule-new-post-label').stop().hide();
            }

            if (!$(e.toElement).is('ul.post-list')
                && !$(e.toElement).is('.schedule-new-post-label')
                && !$(e.toElement).is('form')
                && !$(e.toElement).is('div.day-unit-label')
                && !($(e.toElement).is('td') && e.toElement == this)
            ) {
                $this.find('.schedule-new-post-label').stop().hide();
            }
        },

        /**
         * Callback for click and double click events that open the
         * quickpost dialogue
         *
         * @param  Event e The user interaction event
         */
        open_quickpost_dialogue: function (e) {
            // Ignore if the clicked element is a link
            if ($(e.target).is('a') || $(e.target).is('input')) {
                return true;
            }

            if (!$(e.target).is('.schedule-new-post-label')
                && !$(e.target).is('div.day-unit-label')
                && !$(e.target).is('.day-unit')
                && !$(e.target).is('.post-list')
            ) {
                return false;
            }

            e.preventDefault();
            e.stopPropagation();

            $this = $(this);

            // Close other overlays
            publishpress_calendar_close_overlays();

            // Add the CSS class to sinalize the day which was clicked
            $this.addClass('popup-loaded');

            // Get the current calendar square
            EFQuickPublish.$current_date_square = $this;

            // Get our form content
            var $new_post_form_content = EFQuickPublish.$current_date_square.find('.post-insert-dialog');

            // Inject the form (it will automatically be removed on click-away because of its 'item-overlay' class)
            EFQuickPublish.$new_post_form = $new_post_form_content.clone().addClass('item-overlay post-insert-overlay').appendTo(EFQuickPublish.$current_date_square);

            // Get the inputs and controls for this injected form and focus the cursor on the post title box
            var $edit_post_link = EFQuickPublish.$new_post_form.find('.post-insert-dialog-edit-post-link');
            EFQuickPublish.$post_type_input = EFQuickPublish.$new_post_form.find('.post-insert-dialog-post-type');
            EFQuickPublish.$post_title_input = EFQuickPublish.$new_post_form.find('.post-insert-dialog-post-title').focus();
            EFQuickPublish.$post_content_input = EFQuickPublish.$new_post_form.find('.post-insert-dialog-post-content');
            EFQuickPublish.$post_author_input = EFQuickPublish.$new_post_form.find('.post-insert-dialog-post-author');
            EFQuickPublish.$post_publish_time = EFQuickPublish.$new_post_form.find('.post-insert-dialog-post-publish-time');
            EFQuickPublish.$post_status_input = EFQuickPublish.$new_post_form.find('.post-insert-dialog-post-status');

            // Setup the ajax mechanism for form submit
            EFQuickPublish.$new_post_form.on('submit', function (e) {
                e.preventDefault();
                EFQuickPublish.ajax_pp_create_post(false);
            });

            // Setup direct link to new draft
            $edit_post_link.on('click', function (e) {
                e.preventDefault();
                EFQuickPublish.ajax_pp_create_post(true);
            });

            $('select.post-insert-dialog-post-type', $this).pp_select2();
            $('select.post-insert-dialog-post-status', $this).pp_select2();

            $('select.post-insert-dialog-post-author', $this).pp_select2({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    data: function (params) {
                        return {
                            action: 'publishpress_calendar_search_authors',
                            nonce: pp_calendar_params.nonce,
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: false
                }
            });

            return false; // prevent bubbling up
        },

        /**
         * Sends an ajax request to create a new post
         *
         * @param  bool redirect_to_draft Whether or not we should be redirected to the post's edit screen on success
         */
        ajax_pp_create_post: function (redirect_to_draft) {

            // Get some of the form elements for later use
            var $submit_controls = EFQuickPublish.$new_post_form.find('.post-insert-dialog-controls');
            var $spinner = EFQuickPublish.$new_post_form.find('.spinner');

            // Set loading animation
            $submit_controls.hide();
            $spinner.css('visibility', 'visible');

            // Delay submit to prevent spinner flashing
            setTimeout(function () {

                jQuery.ajax({

                    type: 'POST',
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'pp_insert_post',
                        pp_insert_type: EFQuickPublish.$post_type_input.val(),
                        pp_insert_date: EFQuickPublish.$new_post_form.find('input.post-insert-dialog-post-date').val(),
                        pp_insert_publish_time: EFQuickPublish.$post_publish_time.val(),
                        pp_insert_title: EFQuickPublish.$post_title_input.val(),
                        pp_insert_content: EFQuickPublish.$post_content_input.val(),
                        pp_insert_author: EFQuickPublish.$post_author_input.val(),
                        pp_insert_status: EFQuickPublish.$post_status_input.val(),
                        nonce: $(document).find('#pp-calendar-modify').val()
                    },
                    success: function (response, textStatus, XMLHttpRequest) {

                        if (response.status == 'success') {

                            // The response message on success is the html for the a post list item
                            var $new_post = $(response.message);

                            if (redirect_to_draft) {
                                // If user clicked on the 'edit post' link, let's send them to the new post
                                var edit_url = $new_post.find('.item-actions .edit a').attr('href');
                                window.location = edit_url;
                            } else {
                                // Otherwise, inject the new post and bind the appropriate click event
                                $new_post.appendTo(EFQuickPublish.$current_date_square.find('ul.post-list'));
                                publishpress_calendar_close_overlays();
                            }

                            // Add the event to display the quick post form
                            $('.day-item').on('click', publishpress_calendar_day_item_click);
                        } else {
                            EFQuickPublish.display_errors(EFQuickPublish.$new_post_form, response.message);
                        }
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        EFQuickPublish.display_errors(EFQuickPublish.$new_post_form, errorThrown);
                    }

                }); // .ajax

                return false; // prevent bubbling up

            }, 200); // setTimout

        }, // ajax_pp_create_post

        /**
         * Displays form errors and resets the UI
         *
         * @param  jQueryObj $form The form to display the errors in
         * @param  str error_msg Error message
         */
        display_errors: function ($form, error_msg) {

            $form.find('.error').remove(); // clear out old errors
            $form.find('.spinner').css('visibility', 'hidden'); // stop the loading animation

            // show submit controls and the error
            $form.find('.post-insert-dialog-controls').show().before('<div class="error">Error: ' + error_msg + '</div>');

        } // display_errors

    };

    if (pp_calendar_params.can_add_posts === 'true') {
        EFQuickPublish.init();
    }

    $('#pp-calendar-filters select').on('change', function () {
        $(this).closest('form').trigger('submit');
    });

    // Hide empty tag filter
    if ($('select#tag').children().length === 0) {
        $('select#tag').hide();
    }

    $('#calendar-filter select#post_status').pp_select2();
    $('#calendar-filter select#type').pp_select2();
    $('#calendar-filter select#weeks').pp_select2();

    $('#calendar-filter select#filter_category').pp_select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 0,
            data: function (params) {
                return {
                    action: 'publishpress_calendar_search_categories',
                    nonce: pp_calendar_params.nonce,
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: false
        }
    });

    $('#calendar-filter select#filter_tag').pp_select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 0,
            data: function (params) {
                return {
                    action: 'publishpress_calendar_search_tags',
                    nonce: pp_calendar_params.nonce,
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: false
        }
    });

    $('#calendar-filter select#filter_author').pp_select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 0,
            data: function (params) {
                return {
                    action: 'publishpress_calendar_search_authors',
                    nonce: pp_calendar_params.nonce,
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: false
        }
    });

    function updateParam (url, paramToUpdate, newValue) {
        var parts = url.split('?'),
            query,
            param,
            paramFound = false,
            newQuery = [],
            newUrl = parts[0];

        if (parts.length === 1) {
            parts[1] = '';
        }

        query = parts[1].split('&');

        // Update the param in the query, building a new query
        if (query.length > 0) {

            for (var i = 0; i < query.length; i++) {
                param = query[i].split('=');

                if (param[0] === paramToUpdate) {
                    param[1] = newValue;
                    paramFound = true;
                }

                newQuery.push(param);
            }

            if (!paramFound) {
                newQuery.push([paramToUpdate, newValue]);
            }
        }

        // Convert the new query into a string
        if (newQuery.length > 0) {
            newUrl += '?';

            for (var i = 0; i < newQuery.length; i++) {
                param = newQuery[i];

                if (i > 0) {
                    newUrl += '&';
                }

                newUrl += param[0] + '=' + param[1];
            }
        }

        return newUrl;
    }

    $('#publishpress-calendar-ics-subs #publishpress-start-date').on('change', function () {
        var buttonDownload = document.getElementById('publishpress-ics-download'),
            buttonCopy = document.getElementById('publishpress-ics-copy');

        // Get the URL
        var url = buttonDownload.href;

        url = updateParam(url, 'start', $(this).val());

        buttonDownload.href = url;
        buttonCopy.dataset.clipboardText = url;
    });

    $('#publishpress-calendar-ics-subs #publishpress-end-date').on('change', function () {
        var buttonDownload = document.getElementById('publishpress-ics-download'),
            buttonCopy = document.getElementById('publishpress-ics-copy');

        // Get the URL
        var url = buttonDownload.href;

        url = updateParam(url, 'end', $(this).val());

        buttonDownload.href = url;
        buttonCopy.dataset.clipboardText = url;
    });

    new Clipboard('#publishpress-ics-copy');
});
