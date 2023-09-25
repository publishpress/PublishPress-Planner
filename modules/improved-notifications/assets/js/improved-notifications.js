jQuery(function ($) {
    if (ppNotif.log_url !== '' && $('body').hasClass('post-type-psppnotif_workflow') && $('.wrap .page-title-action').length > 0) {
        var customButton = ' &nbsp; <a href="' + ppNotif.log_url + '" class="page-title-action">' + ppNotif.log_text + ' (' + ppNotif.log_total + ')</a>';
        $('.wrap .page-title-action').after(customButton);
    }
});