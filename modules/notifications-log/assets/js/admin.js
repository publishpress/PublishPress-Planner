jQuery(function ($) {
  $('.filter-posts').pp_select2({
    placeholder: ppNotifLog.text.allPosts,
    allowClear: true,
    containerCssClass: 'filter-posts',
    ajax: {
      url: ajaxurl,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        var query = {
          search: params.term,
          page: params.page || 1,
          action: 'publishpress_search_post',
          nonce: ppNotifLog.nonce
        };

        return query;
      }
    }
  });

  $('.filter-workflows').pp_select2({
    placeholder: ppNotifLog.text.allWorkflows,
    allowClear: true,
    containerCssClass: 'filter-workflows',
    ajax: {
      url: ajaxurl,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        var query = {
          search: params.term,
          page: params.page || 1,
          action: 'publishpress_search_workflow',
          nonce: ppNotifLog.nonce
        };

        return query;
      }
    }
  });

  $('.filter-actions').pp_select2({
    placeholder: ppNotifLog.text.allActions,
    allowClear: true,
    containerCssClass: 'filter-actions'
  });

  $('.filter-channels').pp_select2({
    placeholder: ppNotifLog.text.allChannels,
    allowClear: true,
    containerCssClass: 'filter-channels'
  });

  $('.filter-statuses').pp_select2({
    placeholder: ppNotifLog.text.allStatuses,
    allowClear: true,
    containerCssClass: 'filter-statuses'
  });

  $('.view-log').click(function (event) {
    event.preventDefault();
    var $dialog = $('<div class="notif-log-modal">Test!</div>');
    var notificationId = $(event.target).data('id');

    $('body').append($dialog);

    $dialog.text(ppNotifLog.text.loading);

    $dialog.dialog({
      title: ppNotifLog.text.dialogTitle + ': ' + notificationId,
      dialogClass: 'wp-dialog',
      autoOpen: false,
      draggable: true,
      width: 'auto',
      modal: true,
      resizable: false,
      closeOnEscape: true,
      position: {
        my: 'center',
        at: 'center',
        of: window
      },
      open: function () {
        // close dialog by clicking the overlay behind it
        $('.ui-widget-overlay').bind('click', function () {
          $dialog.dialog('close');
        });
      },
      create: function () {
        // style fix for WordPress admin
        $('.ui-dialog-titlebar-close').addClass('ui-button');
      }
    });

    $dialog.dialog('open');

    $dialog.load(ajaxurl, {
      nonce: ppNotifLog.nonce,
      action: 'publishpress_view_notification',
      id: notificationId
    }, function() {
      $dialog.dialog("option", "position", {my: "center", at: "center", of: window});
    });
  });

  $('.filter-date-begin').datepicker();
  $('.filter-date-end').datepicker();
});
