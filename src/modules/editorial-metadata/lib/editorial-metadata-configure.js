
(function($) {

jQuery(document).ready(function(){

    jQuery('.delete-status a').click(function(){
        if (!confirm(objectL10nMetadata.pp_confirm_delete_term_string))
            return false;
    });

    /**
     * Instantiate the drag and drop sorting functionality
     */
    jQuery("#the-list").sortable({
        items: 'tr.term-static',
        update: function(event, ui) {
            var affected_item = ui.item;
            // Reset the position indicies for all terms
            jQuery('#the-list tr').removeClass('alternate');
            var terms = new Array();
            jQuery('#the-list tr.term-static').each(function(index, value){
                var term_id = jQuery(this).attr('id').replace('term-','');
                terms[index] = term_id;
                jQuery('td.position', this).html(index + 1);
                // Update the WP core design for alternating rows
                if (index%2 == 0)
                    jQuery(this).addClass('alternate');
            });
            // Prepare the POST
            var params = {
                action: 'update_term_positions',
                term_positions: terms,
                editorial_metadata_sortable_nonce: jQuery('#editorial-metadata-sortable').val(),
            };
            // Inform WordPress of our updated positions
            jQuery.post(ajaxurl, params, function(retval){
                jQuery('.notice').remove();
                // If there's a success message, print it. Otherwise we assume we received an error message
                if (retval.status == 'success') {
                    var message = '<div class="is-dismissible notice notice-success"><p>' + retval.message + '</p></div>';
                } else {
                    var message = '<div class="is-dismissible notice notice-error"><p>' + retval.message + '</p></div>';
                }
                jQuery('.publishpress-admin header').after(message);
                // Set a timeout to eventually remove it
                setTimeout(publishpress_hide_message, 8000);
            });
        },
    });
    jQuery("#the-list tr.term-static").disableSelection();

});
