import {callAjaxAction, callAjaxPostAction, getDateAsStringInWpFormat, getPostLinksElement} from "./Functions";
import Select from "./Select";
import DateTimeField from "./fields/DateTimeField";
import AuthorsField from "./fields/AuthorsField";
import PostStatusField from "./fields/PostStatusField";
import TaxonomyField from "./fields/TaxonomyField";
import CheckboxField from "./fields/CheckboxField";
import LocationField from "./fields/LocationField";
import TextArea from "./fields/TextArea";
import TextField from "./fields/TextField";
import UserField from "./fields/UserField";
import NumberField from "./fields/NumberField";
import TimeField from "./fields/TimeField";

const {__} = wp.i18n;
const $ = jQuery;

export default function ItemFormPopup(props) {
    const [postType, setPostType] = React.useState(props.postTypes[0].value);
    const [fields, setFields] = React.useState([]);
    const [isLoading, setIsLoading] = React.useState(false);
    const [savingLink, setSavingLink] = React.useState(false);
    const [errorMessage, setErrorMessage] = React.useState();

    const didMount = () => {
        setPostType(props.postTypes[0].value);
        updateFormsFieldData('post_type', props.postTypes[0].value);
    }

    const getFieldRows = () => {
        const fieldRows = [];

        let dataProperty;
        let field;

        for (const dataPropertyName in fields) {
            if (!fields.hasOwnProperty(dataPropertyName)) {
                continue;
            }

            dataProperty = fields[dataPropertyName];

            switch (dataProperty.type) {
                case 'date':
                    field = <DateTimeField value={dataProperty.value}
                                           isEditing={true}
                                           onChange={(e, value) => {
                                               updateFormsFieldData(dataPropertyName, value);
                                           }}/>;
                    break;

                case 'authors':
                    field = <AuthorsField value={dataProperty.value}
                                          isEditing={true}
                                          name={dataPropertyName}
                                          nonce={props.nonce}
                                          ajaxUrl={props.ajaxUrl}
                                          onSelect={(e, elem, data) => {
                                              let values = [];
                                              for (let i = 0; i < data.length; i++) {
                                                  values.push(data[i].id);
                                              }

                                              updateFormsFieldData(dataPropertyName, values);
                                          }}
                                          onClear={(e, elem) => {
                                              updateFormsFieldData(dataPropertyName, null);
                                          }}/>;
                    break;

                case 'status':
                    field = <PostStatusField value={dataProperty.value}
                                             isEditing={true}
                                             options={props.statuses}
                                             onSelect={(e, elem, data) => {
                                                 let value = null;
                                                 if (data.length > 0) {
                                                     value = data[0].id;
                                                 }

                                                 updateFormsFieldData(dataPropertyName, value);
                                             }}
                                             onClear={(e, elem) => {
                                                 updateFormsFieldData(dataPropertyName, null);
                                             }}/>;
                    break;

                case 'taxonomy':
                    field = <TaxonomyField value={dataProperty.value}
                                           isEditing={true}
                                           taxonomy={dataProperty.taxonomy}
                                           nonce={props.nonce}
                                           ajaxUrl={props.ajaxUrl}
                                           multiple={true}
                                           onSelect={(e, elem, data) => {
                                               let values = [];
                                               for (let i = 0; i < data.length; i++) {
                                                   values.push(data[i].id);
                                               }

                                               updateFormsFieldData(dataPropertyName, values);
                                           }}
                                           onClear={(e, elem) => {
                                               updateFormsFieldData(dataPropertyName, null);
                                           }}/>;
                    break;

                case 'checkbox':
                    field = <CheckboxField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'location':
                    field = <LocationField value={dataProperty.value}
                                           isEditing={true}
                                           onChange={(e, value) => {
                                               updateFormsFieldData(dataPropertyName, value);
                                           }}/>;
                    break;

                case 'html':
                    field = <TextArea value={dataProperty.value}
                                      isEditing={true}
                                      onChange={(e, value) => {
                                          updateFormsFieldData(dataPropertyName, value);
                                      }}/>;
                    break;

                case 'text':
                    field = <TextField value={dataProperty.value}
                                       isEditing={true}
                                       onChange={(e, value) => {
                                           updateFormsFieldData(dataPropertyName, value);
                                       }}/>;
                    break;

                case 'user':
                    field = <UserField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'number':
                    field = <NumberField value={dataProperty.value}
                                         isEditing={true}
                                         onChange={(e, value) => {
                                             updateFormsFieldData(dataPropertyName, value);
                                         }}/>;
                    break;

                case 'time':
                    field = <TimeField value={dataProperty.value}
                                       isEditing={true}
                                       onChange={(e, value) => {
                                           updateFormsFieldData(dataPropertyName, value);
                                       }}/>;
                    break;

                default:
                    field = null;
                    break;
            }

            fieldRows.push(
                <tr>
                    <th>{dataProperty.label}:</th>
                    <td>{field}</td>
                </tr>
            );
        }

        return fieldRows;
    };

    // We are using a global var because the states are async and we were having a hard time to make all
    // fields work together updating the same state.
    const getGlobal = (name) => {
        if (typeof window.publishpressCalendaGlobal === 'undefined') {
            window.publishpressCalendaGlobal = {};
        }

        if (typeof window.publishpressCalendaGlobal.formFieldsData === 'undefined') {
            window.publishpressCalendaGlobal.formFieldsData = {};
        }

        if (window.publishpressCalendaGlobal.formFieldsData.hasOwnProperty(name)) {
            return window.publishpressCalendaGlobal.formFieldsData[name];
        }

        return null;
    }

    const setGlobal = (name, value) => {
        getGlobal(name);

        window.publishpressCalendaGlobal.formFieldsData[name] = value;
    }

    const updateFormsFieldData = (fieldName, value) => {
        setGlobal(fieldName, value);
    }

    const getFormData = () => {
        let formData = new FormData;

        formData.append('date', getDateAsStringInWpFormat(props.date));
        formData.append('nonce', props.nonce);

        for (const fieldName in window.publishpressCalendaGlobal.formFieldsData) {
            if (window.publishpressCalendaGlobal.formFieldsData.hasOwnProperty(fieldName)) {
                formData.append(fieldName, window.publishpressCalendaGlobal.formFieldsData[fieldName]);
            }
        }

        return formData;
    }

    const handleLinkOnClick = (e, linkData) => {
        e.preventDefault();

        setIsLoading(true);
        setSavingLink(linkData.id);
        setErrorMessage(null);

        callAjaxPostAction(linkData.action, linkData.args, props.ajaxUrl, getFormData()).then((result) => {
            if (linkData.id === 'create') {
                setIsLoading(false);
                setSavingLink(null);

                if (result.status === 'success') {
                    if (props.onCloseCallback) {
                        props.onCloseCallback();
                    }
                } else {
                    setErrorMessage(result.message);
                }
            } else if (linkData.id === 'edit') {
                if (result.status === 'success') {
                    window.location.href = result.data.link;
                } else {
                    setIsLoading(false);
                    setSavingLink(null);
                    setErrorMessage(result.message);
                }
            }
        });
    }

    const getFormLinks = () => {
        const formLinks = [
            {
                'id': 'create',
                'label': __('Save', 'publishpress'),
                'labelLoading': __('Saving...', 'publishpress'),
                'action': 'publishpress_calendar_create_item'
            },
            {
                'id': 'edit',
                'label': __('Save and edit', 'publishpress'),
                'labelLoading': __('Saving...', 'publishpress'),
                'action': 'publishpress_calendar_create_item'
            }
        ];

        const links = [];
        let linkData;

        for (const linkName in formLinks) {
            if (!formLinks.hasOwnProperty(linkName)) {
                continue;
            }

            linkData = formLinks[linkName];

            if (savingLink === linkData.id) {
                links.push(<span>{linkData.labelLoading}</span>);
            } else {
                links.push(getPostLinksElement(linkData, handleLinkOnClick));
            }
            links.push(<span>|</span>);
        }

        links.pop();

        return links;
    }

    const handleOnSelectPostType = (e) => {
        let $target = $(e.target);

        const postType = $target.pp_select2('data')[0].id;

        setPostType(postType);

        updateFormsFieldData('post_type', postType);
    }

    const getTitle = () => {
        let title;
        if (props.postId) {
            title = '';
        } else {
            title = __('Schedule content for %s', 'publishpress');
            title = title.replace('%s', getDateAsStringInWpFormat(props.date));
        }

        return title;
    }

    const getPostTypeText = (postTypeId) => {
        for (let i = 0; i < props.postTypes.length; i++) {
            if (props.postTypes[i].value === postTypeId) {
                return props.postTypes[i].text;
            }
        }

        return __('Post type not found', 'publishpress');
    }

    const loadFields = () => {
        setIsLoading(true);

        const args = {
            nonce: props.nonce
        };

        setFields(null);

        callAjaxAction(props.actionGetPostTypeFields, args, props.ajaxUrl)
            .then((result) => {
                setFields(result);
            })
            .then(() => {
                setTimeout(() => {
                    $('.publishpress-calendar-popup-form input').first().focus();
                }, 500);
                setIsLoading(false);
            });
    }

    const title = getTitle();

    React.useEffect(didMount, []);
    React.useEffect(loadFields, [postType]);

    const fieldRows = getFieldRows();

    return (
        <div className="publishpress-calendar-popup publishpress-calendar-popup-form">
            <div className="publishpress-calendar-popup-title">
                <span className={'dashicons dashicons-plus-alt'}/> {title}
                {isLoading &&
                <span className={'dashicons dashicons-update-alt publishpress-spinner'}/>
                }
            </div>
            <hr/>
            <table>
                <tbody>
                {props.postTypes.length > 1 &&
                <tr>
                    <th><label>{__('Post type:', 'publishpress')}</label></th>
                    <td>
                        <Select
                            options={props.postTypes}
                            allowClear={false}
                            onSelect={handleOnSelectPostType}
                        />
                    </td>
                </tr>
                }

                {props.postTypes.length === 1 &&
                <tr>
                    <th>{__('Post type:', 'publishpress')}</th>
                    <td>{getPostTypeText(postType)}</td>
                </tr>
                }

                {fieldRows.length > 0 &&
                    fieldRows
                }
                </tbody>
            </table>


            {fieldRows.length === 0 &&
            <div
                className={'publishpress-calendar-popup-loading-fields'}>{__('Please, wait! Loading the form fields...', 'publishpress')}</div>
            }

            {errorMessage &&
            <div className={'publishpress-calendar-popup-error-message'}>
                <span className={'dashicons dashicons-warning'}/>
                {errorMessage}
            </div>
            }

            <hr className={'publishpress-calendar-popup-links-hr'}/>
            <div className="publishpress-calendar-popup-links">
                {getFormLinks()}
            </div>
        </div>
    )
}
