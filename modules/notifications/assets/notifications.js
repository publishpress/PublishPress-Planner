jQuery(document).ready(function($) {
    $('#pp-post_following_users_box ul').listFilterizer();

    var params = {
        action: 'save_notifications',
        post_id: $('#post_ID').val(),
    };

    $(document).on('click','.pp-post_following_list li input:checkbox, .pp-following_usergroups li input:checkbox', function() {
        var user_group_ids = [];
        var parent_this = $(this);
        params.pp_notifications_name = $(this).attr('name');
        params._nonce = $("#pp_notifications_nonce").val();

        $(this)
            .parent()
            .parent()
            .parent()
            .find('input:checked')
            .map(function(){
                user_group_ids.push($(this).val());
            })

        params.user_group_ids = user_group_ids;

        $.ajax({
            type : 'POST',
            url : (ajaxurl) ? ajaxurl : wpListL10n.url,
            data : params,
            success : function(x) {
                var backgroundColor = parent_this.css('background-color');
                $(parent_this.parent().parent())
                    .animate({ 'backgroundColor':'#CCEEBB' }, 200)
                    .animate({ 'backgroundColor':backgroundColor }, 200);
            },
            error : function(r) {
                $('#pp-post_following_users_box').prev().append(' <p class="error">There was an error. Please reload the page.</p>');
            }
        });
    });
});
