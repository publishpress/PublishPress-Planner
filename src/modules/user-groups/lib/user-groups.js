jQuery(document).ready(function () {
    jQuery('ul#pp-post_following_users li').quicksearch({
        position: 'before',
        attached: 'ul#pp-post_following_users',
        loaderText: '',
        delay: 100
    })
    jQuery('#pp-usergroup-users ul').listFilterizer();
});
