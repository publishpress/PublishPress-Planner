/**
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2019 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 */
let {
  __
} = wp.i18n;
let {
  PluginPostStatusInfo
} = wp.editPost;
let {
  registerPlugin
} = wp.plugins;
let {
  withSelect,
  withDispatch
} = wp.data;
let {
  compose
} = wp.compose;
let {
  SelectControl
} = wp.components;
/**
 * Map Custom Statuses as options for SelectControl
 */

let statuses = window.PPCustomStatuses.map(s => ({
  label: s.name,
  value: s.slug
}));

let getStatusLabel = slug => statuses.find(s => s.value === slug).label; // Hack :(
// @see https://github.com/WordPress/gutenberg/issues/3144


let sideEffectL10nManipulation = status => {
  let node = document.querySelector('.editor-post-save-draft');

  if (node) {
    document.querySelector('.editor-post-save-draft').innerText = `${__('Save')} ${status}`;
  }
};
/**
 * Custom status component
 * @param object props
 */


let PPCustomPostStatusInfo = ({
  onUpdate,
  status
}) => wp.element.createElement(PluginPostStatusInfo, {
  className: `publishpress-extended-post-status publishpress-extended-post-status-${status}`
}, wp.element.createElement("h4", null, status !== 'publish' ? __('Extended Post Status', 'publishpress') : __('Extended Post Status Disabled.', 'publishpress')), status !== 'publish' ? wp.element.createElement(SelectControl, {
  label: "",
  value: status,
  options: statuses,
  onChange: onUpdate
}) : null, wp.element.createElement("small", {
  className: "publishpress-extended-post-status-note"
}, status !== 'publish' ? __(`Note: this will override all status settings above.`, 'publishpress') : __('To select a custom status, please unpublish the content first.', 'publishpress')));

let plugin = compose(withSelect(select => ({
  status: select('core/editor').getEditedPostAttribute('status')
})), withDispatch(dispatch => ({
  onUpdate(status) {
    dispatch('core/editor').editPost({
      status
    });
    sideEffectL10nManipulation(getStatusLabel(status));
  }

})))(PPCustomPostStatusInfo);
registerPlugin('publishpress-custom-status-block', {
  icon: 'admin-site',
  render: plugin
});
//# sourceMappingURL=custom-status-block.js.map