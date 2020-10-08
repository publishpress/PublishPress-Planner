jQuery(function ($) {
    $('select', '#pp_post_notify_users_box').pp_select2(
        {
            placeholder: '',
            width: '95%',
            tags: true,
            allowClear: true
        }
    );
});
