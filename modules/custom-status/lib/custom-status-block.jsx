/**
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2019 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 */

let {__} = wp.i18n;
let {PluginPostStatusInfo} = wp.hasOwnProperty('editPost') ? wp.editPost : '';
let {registerPlugin} = wp.plugins;
let {withSelect, withDispatch, subscribe} = wp.data;
let {compose} = wp.compose;
let {SelectControl} = wp.components;

const publishedStatuses = ['publish', 'future', 'private'];

/**
 * Map Custom Statuses as options for SelectControl
 */
let getStatusLabel = slug => {
    let item = window.PPCustomStatuses.find(s => s.slug === slug);

    if (item) {
        return item.name;
    }

    let draft = window.PPCustomStatuses.find(s => s.slug === 'draft');

    return draft.name;
};

// Remove the Published statuses and Pending Review from the list.
let getStatuses = () => {
    let statuses = window.PPCustomStatuses.map(s => ({label: s.name, value: s.slug}));
    statuses = statuses.filter((item) => {
        return !publishedStatuses.includes(item.value) && 'pending' != item.value;
    });

    return statuses;
};

/**
 * Hack :(
 *
 * @see https://github.com/WordPress/gutenberg/issues/3144
 *
 * @param status
 */
let updateTheSaveAsButtonText = (status) => {
    setTimeout(() => {
        let node = document.querySelector('.editor-post-save-draft, .editor-post-switch-to-draft');

        if (!node) {
            return;
        }

        let label;
        let currentStatus = wp.data.select('core/editor').getCurrentPost().status;
        if (publishedStatuses.includes(currentStatus)) {
            if ('future' === currentStatus) {
                label = __('Unschedule and Save as %s', 'publishpress').replace('%s', getStatusLabel('draft'));
            } else {
                label = __('Unpublish and Save as %s', 'publishpress').replace('%s', getStatusLabel('draft'));
            }
        } else {
            label = __('Save as %s', 'publishpress').replace('%s', getStatusLabel(status));
        }

        if (label !== node.getAttribute('aria-label')) {
            node.setAttribute('aria-label', label);
        }
    }, 100);
};

/**
 * Hack :(
 * We need an interval because the DOM element is removed by autosave and rendered back after finishing.
 *
 * @see
 */
if (PluginPostStatusInfo) {
    var lastStatus = wp.data.select('core/editor').getCurrentPost().status;
    setInterval(() => {
        let currentStatus = wp.data.select('core/editor').getCurrentPost().status;
        let editedStatus = wp.data.select('core/editor').getEditedPostAttribute('status');

        updateTheSaveAsButtonText(editedStatus);

        if (lastStatus !== editedStatus) {

            // Force to render after a post status change
            wp.data.dispatch('core/editor').editPost({ status: '!' });
            wp.data.dispatch('core/editor').editPost({ status: editedStatus });

            lastStatus = editedStatus;
        }
    }, 100);
}


/**
 * Custom status component
 * @param object props
 */
let PPCustomPostStatusInfo = ({onUpdate, status}) => {
    let statusControl;
    let originalStatus = wp.data.select('core/editor').getCurrentPost().status;
    let noteElement;

    if ([originalStatus, status].includes('publish')) {
        statusControl = <div className={"publishpress-static-post-status"}>{__('Published', 'publishpress')}</div>;
        noteElement = <small className="publishpress-extended-post-status-note">
            {__('To select a custom status, please unpublish the content first.', 'publishpress')}
        </small>;
    } else if ([originalStatus, status].includes('future')) {
        statusControl = <div className={"publishpress-static-post-status"}>{__('Scheduled', 'publishpress')}</div>;
        noteElement = <small className="publishpress-extended-post-status-note">
            {__('To select a custom status, please unschedule the content first.', 'publishpress')}
        </small>;
    } else if ('pending' === status) {
        statusControl = <div className={"publishpress-static-post-status"}>{__('Pending review', 'publishpress')}</div>;
        noteElement = <small className="publishpress-extended-post-status-note">
            {__('To select a custom status, please uncheck the "Pending Review" checkbox first.', 'publishpress')}
        </small>;
    } else if ([originalStatus, status].includes('private')) {
        statusControl =
            <div className={"publishpress-static-post-status"}>{__('Privately Published', 'publishpress')}</div>;
        noteElement = <small className="publishpress-extended-post-status-note">
            {__('To select a custom status, please unpublish the content first.', 'publishpress')}
        </small>;
    } else {
        statusControl = <SelectControl
            label=""
            value={status}
            options={getStatuses()}
            onChange={onUpdate}
        />;
    }

    return (
        <PluginPostStatusInfo
            className={`publishpress-extended-post-status publishpress-extended-post-status-${status}`}
        >
            <h4>{__('Post Status', 'publishpress')}</h4>
            {statusControl}
            {noteElement}
        </PluginPostStatusInfo>
    );
};

let getCurrentPostStatus = function () {
    let currentPostStatus = wp.data.select('core/editor').getEditedPostAttribute('status');

    if (currentPostStatus === 'auto-draft') {
        currentPostStatus = 'draft';
    }

    return currentPostStatus;
};

let plugin = compose(
    withSelect((select) => ({
        status: getCurrentPostStatus()
    })),
    withDispatch((dispatch) => ({
        onUpdate(status) {
            wp.data.dispatch('core/editor').editPost({status});
            updateTheSaveAsButtonText(status);
        }
    }))
)(PPCustomPostStatusInfo);

if (PluginPostStatusInfo) {
    registerPlugin('publishpress-custom-status-block', {
        icon: 'admin-site',
        render: plugin
    });
}