// Content Overview specific JS, assumes that pp_date.js has already been included

jQuery(document).ready(function ($) {
    // Hide all post details when directed
    $('#toggle_details').on('click', function () {
        $('.post-title > p').toggle('hidden');
    });

    // Make print link open up print dialog
    $('#print_link').on('click', function () {
        window.print();
        return false;
    });

    // Hide a single section when directed
    $('h3.hndle,div.handlediv').on('click', function () {
        $(this).parent().children('div.inside').toggle();
    });

    // Change number of columns when choosing a new number from Screen Options
    $('input[name=pp_story_budget_screen_columns]').on('click', function () {
        var numColumns = $(this).val();

        $('.postbox-container').css('width', (100 / numColumns) + '%');
    });

    $('h2 a.change-date').on('click', function () {
        $(this).hide();
        $('h2 form .form-value').hide();
        $('h2 form input').show();
        $('h2 form a.change-date-cancel').show();

        var use_today_as_start_date_input = $('#pp-content-overview-range-use-today');
        if (use_today_as_start_date_input.length > 0) {
            use_today_as_start_date_input.val(0);
        }

        return false;
    });

    $('h2 form a.change-date-cancel').on('click', function () {
        $(this).hide();
        $('h2 form .form-value').show();
        $('h2 form input').hide();
        $('h2 form a.change-date').show();

        return false;
    });

    $('#pp-content-filters select').on('change', function () {
        $(this).closest('form').trigger('submit');
    });

    $('#pp-content-overview-range-today-btn').on('click', function (event) {
        var start_date_input = $('#pp-content-overview-start-date');
        var use_today_as_start_date_input = $('#pp-content-overview-range-use-today');

        if (
            start_date_input.length === 0
            || !start_date_input.is(':visible')
            || use_today_as_start_date_input.length === 0
        ) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }

        use_today_as_start_date_input.val(1);
    });

    $('#pp-content-filters select#filter_author').pp_select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 0,
            data: function (params) {
                return {
                    action: 'publishpress_content_overview_search_authors',
                    nonce: PPContentOverview.nonce,
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

    $('#pp-content-filters select#filter_category').pp_select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 0,
            data: function (params) {
                return {
                    action: 'publishpress_content_overview_search_categories',
                    nonce: PPContentOverview.nonce,
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

    $('#pp-content-filters select#post_status').pp_select2();
    $('#pp-content-filters select#filter_post_type').pp_select2();
});
