jQuery(function ($) {
  var __ = wp.i18n.__;

  $('.filter-posts').pp_select2({
    placeholder: ppNotifLog.text.allPosts,
    allowClear: true,
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
    allowClear: true
  });

  $('.filter-channels').pp_select2({
    placeholder: ppNotifLog.text.allChannels,
    allowClear: true
  });

  $('.filter-statuses').pp_select2({
    placeholder: ppNotifLog.text.allStatuses,
    allowClear: true
  });

  $('.view-log').click(function (event) {
    event.preventDefault();
    var $dialog = $('<div class="notif-log-modal">Test!</div>');
    var notificationId = $(event.target).data('id');
    var receiver = $(event.target).data('receiver');
    var receiverText = $(event.target).data('receiver-text');
    var channel = $(event.target).data('channel');



    $('body').append($dialog);

    $dialog.text(ppNotifLog.text.loading);

    $dialog.dialog({
      title: ppNotifLog.text.dialogTitle + ': ' + notificationId + ' ' + __('for', 'publishpress') + ' ' + receiverText + ' ' + __('by', 'publishpress') + ' ' + channel ,
      dialogClass: 'wp-dialog',
      autoOpen: false,
      draggable: true,
      width: '70%',
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
      id: notificationId,
      receiver: receiver,
      channel: channel
    }, function() {
      $dialog.dialog("option", "position", {my: "center", at: "center", of: window});
    });
  });

  $('.filter-date-begin').datepicker();
  $('.filter-date-end').datepicker();

  $('.publishpress_page_pp-notif-log .slide-closed-text').click(function() {
    $(this).next().slideDown();
    $(this).remove();
  });
});
