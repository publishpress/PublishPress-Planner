// Content Overview specific JS, assumes that pp_date.js has already been included

jQuery(document).ready(function ($) {
    // Hide all post details when directed
    $('#toggle_details').click(function () {
        $('.post-title > p').toggle('hidden');
    });

    // Make print link open up print dialog
    $('#print_link').click(function () {
        window.print();
        return false;
    });

    // Hide a single section when directed
    $('h3.hndle,div.handlediv').click(function () {
        $(this).parent().children('div.inside').toggle();
    });

    // Change number of columns when choosing a new number from Screen Options
    $('input[name=pp_story_budget_screen_columns]').click(function () {
        var numColumns = $(this).val();

        jQuery('.postbox-container').css('width', (100 / numColumns) + '%');
    });

    jQuery('h2 a.change-date').click(function () {
        jQuery(this).hide();
        jQuery('h2 form .form-value').hide();
        jQuery('h2 form input').show();
        jQuery('h2 form a.change-date-cancel').show();

        var use_today_as_start_date_input = $('#pp-content-overview-range-use-today');
        if (use_today_as_start_date_input.length > 0) {
            use_today_as_start_date_input.val(0);
        }

        return false;
    });

    jQuery('h2 form a.change-date-cancel').click(function () {
        jQuery(this).hide();
        jQuery('h2 form .form-value').show();
        jQuery('h2 form input').hide();
        jQuery('h2 form a.change-date').show();

        return false;
    });

    $('#pp-content-filters select').change(function () {
        $(this).closest('form').trigger('submit');
    });

    $('#pp-content-overview-range-today-btn').click(function (event) {
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
});
