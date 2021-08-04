jQuery(function ($) {
  editorialCommentReply.init();

  // Check if certain hash flag set and take action
  if (location.hash === '#editorialcomments/add') {
    editorialCommentReply.open();
  } else if (location.hash.search(/#editorialcomments\/reply/) > -1) {
    var reply_id = location.hash.substring(location.hash.lastIndexOf('/') + 1);
    editorialCommentReply.open(reply_id);
  }

  $(window).on('hashchange', function () {
    if (location.hash.search(/#comment-/) > -1) {
      var offset = $(':target').offset();
      var scrollto = offset.top - 120; // minus fixed header height
      $('html, body').animate({scrollTop: scrollto}, 0);
    }
  });
});

/**
 * Blatantly stolen and modified from /wp-admin/js/edit-comment.dev.js -- yay!
 */
editorialCommentReply = {

  init: function () {
    var row = jQuery('#pp-replyrow');

    // Bind click events to cancel and submit buttons
    jQuery('a.pp-replycancel', row).on('click', function () {
      return editorialCommentReply.revert();
    });
    jQuery('a.pp-replysave', row).on('click', function () {
      return editorialCommentReply.send();
    });
  },

  revert: function () {
    // Fade out slowly, slowly, slowly...
    jQuery('#pp-replyrow').fadeOut('fast', function () {
      editorialCommentReply.close();
    });
    return false;
  },

  close: function () {

    jQuery('#pp-comment_respond').show();

    // Move reply form back after the main "Respond" form
    jQuery('#pp-post_comment').after(jQuery('#pp-replyrow'));

    // Empty out all the form values
    jQuery('#pp-replycontent').val('');
    jQuery('#pp-comment_parent').val('');

    // Hide error and waiting
    jQuery('#pp-replysubmit .error').html('').hide();
    jQuery('#pp-comment_loading').hide();
  },

  /**
   * @id = comment id
   */
  open: function (id) {
    editorialCommentEdit.close();

    var parent;

    // Close any open reply boxes
    this.close();

    // Check if reply or new comment
    if (id) {
      jQuery('input#pp-comment_parent').val(id);
      parent = '#comment-' + id;
    } else {
      parent = '#pp-comments_wrapper';
    }

    jQuery('#pp-comment_respond').hide();

    // Show reply textbox
    jQuery('#pp-replyrow')
      .show()
      .appendTo(jQuery(parent))
    ;

    jQuery('#pp-replycontent').focus();

    return false;
  },

  /**
   * Sends the ajax response to save the commment
   * @param bool reply - indicates whether the comment is a reply or not
   */
  send: function (reply) {
    var post = {};

    jQuery('#pp-replysubmit .error').html('').hide();

    // Validation: check to see if comment entered
    post.content = jQuery.trim(jQuery('#pp-replycontent').val());
    if (!post.content) {
      jQuery('#pp-replyrow .error').text('Please enter a comment').show();
      return;
    }

    jQuery('#pp-comment_loading').show();

    // Prepare data
    post.action = 'publishpress_ajax_insert_comment';
    post.parent = (jQuery('#pp-comment_parent').val() == '') ? 0 : jQuery('#pp-comment_parent').val();
    post._nonce = jQuery('#pp_comment_nonce').val();
    post.post_id = jQuery('#pp-post_id').val();

    // Send the request
    jQuery.ajax({
      type: 'POST',
      url: (ajaxurl) ? ajaxurl : wpListL10n.url,
      data: post,
      success: function (x) {
        editorialCommentReply.show(x);
      },
      error: function (r) {
        editorialCommentReply.error(r);
      }
    });

    return false;
  },

  show: function (xml) {
    editorialCommentEdit.close();

    var response, comment, supplemental, id, bg;

    // Didn't pass validation, so let's throw an error
    if (typeof (xml) == 'string') {
      this.error({'responseText': xml});
      return false;
    }

    // Parse the response
    response = wpAjax.parseAjaxResponse(xml);
    if (response.errors) {
      // Uh oh, errors found
      this.error({'responseText': wpAjax.broken});
      return false;
    }

    response = response.responses[0];
    comment = response.data;
    supplemental = response.supplemental;

    jQuery(comment).hide();

    if (response.action.indexOf('reply') == -1 || !pp_thread_comments) {
      // Not a reply, so add it to the bottom
      jQuery('#pp-comments').append(comment);
    } else {

      // This is a reply, so add it after the comment replied to

      if (jQuery('#pp-replyrow').parent().next().is('ul')) {
        // Already been replied to, so just add to the list
        jQuery('#pp-replyrow').parent().next().append(comment);
      } else {
        // This is a first reply, so create an unordered list to house the comment
        var newUL = jQuery('<ul></ul>')
          .addClass('children')
          .append(comment)
        ;
        jQuery('#pp-replyrow').parent().after(newUL);
      }
    }

    // Get the comment contaner's id
    this.o = id = '#comment-' + response.id;
    // Close the reply box
    this.revert();

    // Show the new comment
    jQuery(id)
      .animate({'backgroundColor': '#CCEEBB'}, 600)
      .animate({'backgroundColor': '#fff'}, 600);

  },

  error: function (r) {
    // Oh noes! We haz an error!
    jQuery('#pp-comment_loading').hide();

    if (r.responseText) {
      er = r.responseText.replace(/<.[^<>]*?>/g, '');

      jQuery('#pp-replysubmit .error').html(er).show();
    }
  }
};

editorialCommentEdit = {
  $: jQuery,

  init: function () {},

  close: function () {
    var $editRow = this.$('#pp-editrow');

    var id = $editRow.data('id');
    this.$('#comment-' + id + ' .comment-content').show();

    $editRow.remove();
  },

  open: function (id) {
    editorialCommentReply.revert();

    // Close any open reply boxes
    this.close();

    // Check if reply or new comment
    if (!id) {
      return false;
    }

    var $rowActions = this.$('#comment-' + id + ' .post-comment-wrap .row-actions');
    var $content    = this.$('#comment-' + id + ' .comment-content');

    var $editBox = this.$('<div id="pp-editrow" data-id="' + id + '"><div id="pp-editcontainer"></div></div>');
    $rowActions.before($editBox);

    var $textArea = this.$('<textarea id="pp-editcontent" name="editcontent" cols="40" rows="5" spellcheck="false">');
    var $editContainer = this.$('#pp-editcontainer');
    $editContainer.append($textArea);
    $textArea.val($content.text());
    $content.hide();

    var $editSubmit = this.$('<div id="pp-editsubmit">');
    $editContainer.append($editSubmit);

    var $buttonSave = this.$('<a class="button pp-editsave button-primary alignright" href="#pp-editrow">' + wp.i18n.__('Save', 'publishpress') + '</a>');
    var $buttonCancel = this.$('<a class="button pp-editcancel alignright" href="#pp-editrow">' + wp.i18n.__('Cancel', 'publishpress') + '</a>');
    $editSubmit.append($buttonSave);
    $editSubmit.append($buttonCancel);

    $buttonCancel.on('click', this.close.bind(this));
    $buttonSave.on('click', this.send.bind(this));

    return false;
  },

  send: function (reply) {
    var post = {};
    var self = this;

    this.$('.pp-error').remove();

    var $li = this.$(this.$('#pp-editcontent').parents('li')[0]);

    // Validation: check to see if comment entered
    post.content = this.$.trim(this.$('#pp-editcontent').val());
    if (!post.content) {
      var $errorLine = this.$('<div class="pp-error">').text('Please enter a comment');
      this.$('#pp-editcontainer').append($errorLine);
      return;
    }

    this.$('#pp-comment_loading').remove();
    var $loading = this.$('<img alt="' + wp.i18n.__('Sending content...', 'publishpress') + '" src="' + publishpressEditorialCommentsParams.loadingImgSrc + '" class="alignright" id="pp-comment_loading"/>');
    this.$('#pp-editcontainer').append($loading);

    // Prepare data
    post.action = 'publishpress_ajax_edit_comment';
    post._nonce = this.$('#pp_comment_nonce').val();
    post.comment_id = $li.data('id');
    post.post_id = $li.data('post-id');

    // Send the request
    this.$.ajax({
      type: 'POST',
      url: (ajaxurl) ? ajaxurl : wpListL10n.url,
      data: post,
      success: function (x) {
        self.$('#comment-' + post.comment_id + ' .comment-content').html(x.content);
        self.close();
      },
      error: function (r) {
        let $errorLine = self.$('<div class="pp-error">').html(r.responseText);
        self.$('#pp-editcontainer').append($errorLine);
        self.$('#pp-comment_loading').remove();
      }
    });


    return false;
  },
};

editorialCommentDelete = {
  $: jQuery,

  init: function () {
  },

  close: function() {
    let $editRow = this.$('#pp-editrow');
    $editRow.remove();
  },

  open: function (id) {
    const hasChildComments = this.$(`#pp-comments [data-parent="${id}"]`).length > 0;

    this.close();

    if (hasChildComments) {
      alert(wp.i18n.__('This comment can\'t be deleted because it has one or more replies. Before deleting it make sure to delete all the replies first.', 'publishpress'));
      return;
    }

    if (confirm(wp.i18n.__('Are you sure you want to delete this comment?', 'publishpress'))) {
      var self = this;

      var $rowActions = this.$('#comment-' + id + ' .post-comment-wrap .row-actions');
      var $editBox = this.$('<div id="pp-editrow" data-id="' + id + '"><div id="pp-editcontainer"></div></div>');
      $rowActions.before($editBox);

      // Prepare data
      let data = {};

      data.action = 'publishpress_ajax_delete_comment';
      data._nonce = this.$('#pp_comment_nonce').val();
      data.comment_id = id;

      // Send the request
      this.$.ajax({
        type: 'POST',
        url: (ajaxurl) ? ajaxurl : wpListL10n.url,
        data: data,
        success: function (x) {
          self.$('#comment-' + id).remove();

          editorialCommentEdit.close();
          editorialCommentReply.revert();
        },
        error: function (r) {
          let $errorLine = self.$('<div class="pp-error">').html(r.responseText);
          self.$('#pp-editcontainer').append($errorLine);
          self.$('#pp-comment_loading').remove();
        }
      });
    }
  }
};
