import {callAjaxAction, getDateAsStringInWpFormat, getPostLinksElement, getTodayMidnight} from "./Functions";
import Select from "./Select";
import DateTimeField from "./fields/DateTimeField";
import AuthorsField from "./fields/AuthorsField";
import PostStatusField from "./fields/PostStatusField";
import TaxonomyField from "./fields/TaxonomyField";
import CheckboxField from "./fields/CheckboxField";
import LocationField from "./fields/LocationField";
import ParagraphField from "./fields/ParagraphField";
import TextField from "./fields/TextField";
import UserField from "./fields/UserField";
import NumberField from "./fields/NumberField";
import TimeField from "./fields/TimeField";

const {__} = wp.i18n;
const $ = jQuery;
const date = wp.date;

export default function ItemFormPopup(props) {
    const [postType, setPostType] = React.useState(props.postTypes[0].value);
    const [fields, setFields] = React.useState([]);

    const today = getTodayMidnight();

    const didMount = () => {
        if (props.postTypes.length === 1) {
            setPostType(props.postTypes[0].id);
        }
    }

    const getFieldRows = () => {
        const fieldRows = [];

        let dataProperty;
        let field;
        let options;

        for (const dataPropertyName in fields) {
            if (!fields.hasOwnProperty(dataPropertyName)) {
                continue;
            }

            dataProperty = fields[dataPropertyName];

            switch (dataProperty.type) {
                case 'date':
                    field = <DateTimeField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'authors':
                    field = <AuthorsField value={dataProperty.value}
                                          isEditing={true}
                                          nonce={props.nonce}
                                          ajaxUrl={props.ajaxUrl}/>;
                    break;

                case 'status':
                    field = <PostStatusField value={dataProperty.value}
                                             isEditing={true}
                                             options={props.statuses}/>;
                    break;

                case 'taxonomy':
                    field = <TaxonomyField value={dataProperty.value}
                                           isEditing={true}
                                           taxonomy={dataProperty.taxonomy}
                                           nonce={props.nonce}
                                           ajaxUrl={props.ajaxUrl}
                                            multiple={true}/>;
                    break;

                case 'checkbox':
                    field = <CheckboxField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'location':
                    field = <LocationField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'html':
                    field = <ParagraphField value={dataProperty.value}
                                            isEditing={true}/>;
                    break;

                case 'text':
                    field = <TextField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'user':
                    field = <UserField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'number':
                    field = <NumberField value={dataProperty.value} isEditing={true}/>;
                    break;

                case 'time':
                    field = <TimeField value={dataProperty.value} isEditing={true}/>;
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

    const handleLinkOnClick = (e, linkData) => {
        e.preventDefault();

        callAjaxAction(linkData.action, linkData.args, props.ajaxUrl).then((result) => {
            props.onItemActionClickCallback(linkData.action, props.id, result);
        });
    }

    const getFormLinks = () => {
        const links = [];
        let linkData;

        for (const linkName in props.links) {
            if (!props.links.hasOwnProperty(linkName)) {
                continue;
            }

            linkData = props.links[linkName];

            links.push(getPostLinksElement(linkData, handleLinkOnClick));
        }

        return links;
    }

    const handleOnSelectPostType = (e) => {
        let $target = $(e.target);
        setPostType($target.pp_select2('data')[0].id);
    }

    const getTitle = () => {
        let title;
        if (props.postId) {
            title = '';
        } else {
            if (props.date >= today) {
                title = __('Schedule for %s', 'publishpress');
            } else {
                title = __('Create with date %s', 'publishpress');
            }

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
        if (postType === null || typeof postType === 'undefined') {

        }

        const args = {
            nonce: props.nonce
        };

        callAjaxAction(props.actionGetPostTypeFields, args, props.ajaxUrl).then((result) => {
            setFields(result);
        });
    }

    const title = getTitle();

    React.useEffect(didMount, []);
    React.useEffect(loadFields, [postType]);

    return (
        <div className="publishpress-calendar-popup publishpress-calendar-popup-form">
            <div className="publishpress-calendar-popup-title">
                {title}
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

                {getFieldRows()}
                </tbody>
            </table>
            <hr/>
            <div className="publishpress-calendar-popup-links">
                {getFormLinks()}
            </div>
        </div>
    )
}
