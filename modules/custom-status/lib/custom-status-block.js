/**
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2019 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 */
var __ = wp.i18n.__;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var registerPlugin = wp.plugins.registerPlugin;
var _wp$data = wp.data,
    withSelect = _wp$data.withSelect,
    withDispatch = _wp$data.withDispatch;
var compose = wp.compose.compose;
var SelectControl = wp.components.SelectControl;
/**
 * Map Custom Statuses as options for SelectControl
 */

var statuses = window.PPCustomStatuses.map(function (s) {
  return {
    label: s.name,
    value: s.slug
  };
});

var getStatusLabel = function getStatusLabel(slug) {
  var item = statuses.find(function (s) {
    return s.value === slug;
  });

  if (label) {
    return item.label;
  }

  return '';
}; // Remove the Published status from the list.


statuses = statuses.filter(function (item) {
  return item.value !== 'publish';
});
/**
 * Hack :(
 *
 * @see https://github.com/WordPress/gutenberg/issues/3144
 *
 * @param status
 */

var sideEffectL10nManipulation = function sideEffectL10nManipulation(status) {
  if (status === 'publish') {
    return;
  }

  var statusLabel = getStatusLabel(status);
  setTimeout(function () {
    var node = document.querySelector('.editor-post-save-draft');

    if (!node) {
      node = document.querySelector('.editor-post-switch-to-draft');
    }

    if (node) {
      document.querySelector('.editor-post-save-draft, .editor-post-switch-to-draft').innerText = "".concat(__('Save as'), " ").concat(statusLabel);
      node.dataset.ppInnerTextUpdated = true;
    }
  }, 100);
};
/**
 * Hack :(
 * We need an interval because the DOM element is removed by autosave and rendered back after finishing.
 *
 * @see https://github.com/WordPress/gutenberg/issues/3144
 */


setInterval(function () {
  var status = wp.data.select('core/editor').getEditedPostAttribute('status');

  if (status === 'publish') {
    return;
  }

  var statusLabel = getStatusLabel(status);
  var node = document.querySelector('.editor-post-save-draft');

  if (!node) {
    node = document.querySelector('.editor-post-switch-to-draft');
  }

  if (node && !node.dataset.ppInnerTextUpdated) {
    document.querySelector('.editor-post-save-draft, .editor-post-switch-to-draft').innerText = "".concat(__('Save as'), " ").concat(statusLabel);
    node.dataset.ppInnerTextUpdated = true;
  }
}, 250);
/**
 * Custom status component
 * @param object props
 */

var PPCustomPostStatusInfo = function PPCustomPostStatusInfo(_ref) {
  var onUpdate = _ref.onUpdate,
      status = _ref.status;
  return wp.element.createElement(PluginPostStatusInfo, {
    className: "publishpress-extended-post-status publishpress-extended-post-status-".concat(status)
  }, wp.element.createElement("h4", null, __('Post Status', 'publishpress')), status !== 'publish' ? wp.element.createElement(SelectControl, {
    label: "",
    value: status,
    options: statuses,
    onChange: onUpdate
  }) : wp.element.createElement("div", null, __('Published', 'publishpress')), wp.element.createElement("small", {
    className: "publishpress-extended-post-status-note"
  }, status !== 'publish' ? __("Note: this will override all status settings above.", 'publishpress') : __('To select a custom status, please unpublish the content first.', 'publishpress')));
};

var plugin = compose(withSelect(function (select) {
  return {
    status: select('core/editor').getEditedPostAttribute('status')
  };
}), withDispatch(function (dispatch) {
  return {
    onUpdate: function onUpdate(status) {
      dispatch('core/editor').editPost({
        status: status
      });
      sideEffectL10nManipulation(status);
    }
  };
}))(PPCustomPostStatusInfo);
registerPlugin('publishpress-custom-status-block', {
  icon: 'admin-site',
  render: plugin
});

//# sourceMappingURL=custom-status-block.js.map