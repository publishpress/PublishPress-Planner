// Hide a given message
function publishpress_hide_message() {
    jQuery('.notice').fadeOut(function(){ jQuery(this).remove(); });
}

jQuery(document).ready(function(){
    // Set auto-removal to 8 seconds
    if (jQuery('.notice').length > 0) {
        setTimeout(publishpress_hide_message, 8000);
    }
});
