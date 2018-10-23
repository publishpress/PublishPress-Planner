jQuery(document).ready(function () {
    jQuery('ul#pp-post_following_users li').quicksearch({
        position: 'before',
        attached: 'ul#pp-post_following_users',
        loaderText: '',
        delay: 100
    });
    jQuery('#pp-usergroup-users ul').listFilterizer();

    jQuery('.delete-usergroup a').click(function () {
        if (!confirm(objectL10nUsergroups.pp_confirm_delete_usergroup_string))
            return false;
    });
});
