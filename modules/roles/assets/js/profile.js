jQuery(function($) {
    // Check if the Role field is available. If not, abort.
    if (!$('.user-role-wrap select#role').length) {
        return;
    }

    var $field = $('.user-role-wrap select#role');

    // Convert the roles field into multiselect
    $field.attr('multiple', 'multiple');

    $field.chosen({
        'width': '25em'
    });
});
