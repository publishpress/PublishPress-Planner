jQuery(function ($) {
    $('#upstream-debug-data textarea').focus(function () {
        var $this = $(this);

        $this.select();

        $this.mouseup(function () {
            $this.unbind('mouseup');
            return false;
        });
    });
});
