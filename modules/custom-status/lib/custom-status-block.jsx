/**
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2019 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 */

let {__} = wp.i18n;
let {PluginPostStatusInfo} = wp.editPost;
let {registerPlugin} = wp.plugins;
let {withSelect, withDispatch} = wp.data;
let {compose} = wp.compose;
let {SelectControl} = wp.components;

/**
 * Map Custom Statuses as options for SelectControl
 */
let statuses = window.PPCustomStatuses.map(s => ({label: s.name, value: s.slug}));
let getStatusLabel = slug => {
    let item = statuses.find(s => s.value === slug);

    if (item) {
        return item.label;
    }

    return '';
};

// Remove the Published status from the list.
statuses = statuses.filter((item) => {
    return item.value !== 'publish';
});

/**
 * Hack :(
 *
 * @see https://github.com/WordPress/gutenberg/issues/3144
 *
 * @param status
 */
let sideEffectL10nManipulation = status => {

    if (status === 'publish') {
        return;
    }

    let statusLabel = getStatusLabel(status);

    setTimeout(() => {
        let node = document.querySelector('.editor-post-save-draft');

        if (!node) {
            node = document.querySelector('.editor-post-switch-to-draft');
        }

        if (node) {
            document.querySelector('.editor-post-save-draft, .editor-post-switch-to-draft').innerText = `${__('Save as')} ${statusLabel}`;
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
setInterval(() => {
    let status = wp.data.select('core/editor').getEditedPostAttribute('status');

    if (status === 'publish') {
        return;
    }

    let statusLabel = getStatusLabel(status);
    let node = document.querySelector('.editor-post-save-draft');

    if (!node) {
        node = document.querySelector('.editor-post-switch-to-draft');
    }

    if (node && !node.dataset.ppInnerTextUpdated) {
        document.querySelector('.editor-post-save-draft, .editor-post-switch-to-draft').innerText = `${__('Save as')} ${statusLabel}`;
        node.dataset.ppInnerTextUpdated = true;
    }
}, 250);

/**
 * Custom status component
 * @param object props
 */
let PPCustomPostStatusInfo = ({onUpdate, status}) => (
    <PluginPostStatusInfo
        className={`publishpress-extended-post-status publishpress-extended-post-status-${status}`}
    >
        <h4>{__('Post Status', 'publishpress')}</h4>

        {status !== 'publish' ? <SelectControl
            label=""
            value={status}
            options={statuses}
            onChange={onUpdate}
        /> : <div>{__('Published', 'publishpress')}</div>}

        <small className="publishpress-extended-post-status-note">
            {status !== 'publish' ? __(`Note: this will override all status settings above.`, 'publishpress') : __('To select a custom status, please unpublish the content first.', 'publishpress')}
        </small>
    </PluginPostStatusInfo>
);

let plugin = compose(
    withSelect((select) => ({
        status: select('core/editor').getEditedPostAttribute('status')
    })),
    withDispatch((dispatch) => ({
        onUpdate (status) {
            dispatch('core/editor').editPost({status});
            sideEffectL10nManipulation(status);
        }
    }))
)(PPCustomPostStatusInfo);

registerPlugin('publishpress-custom-status-block', {
    icon: 'admin-site',
    render: plugin
});
