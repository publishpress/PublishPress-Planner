import {callAjaxAction, getFieldElementElement} from "./Functions";

const {__} = wp.i18n;
const $ = jQuery;

export default function ItemPopup(props) {
    if (!props.data) {
        return <></>;
    }

    if (!props.target.current) {
        return <></>;
    }

    const offsetX = 10;
    const offsetWidth = 180;
    const isEditing = false;
    const targetPosition = $(props.target.current).position();
    const targetOffset = $(props.target.current).offset();
    const targetWidth = $(props.target.current).width();
    const popupWidth = 380;

    const isWiderThanParentWidth = () => {
        return (targetOffset.left + popupWidth + offsetX + offsetWidth) >= $(document).width();
    }

    const getPositionOnRightSide = () => {
        return targetPosition.left + targetWidth + offsetX;
    }

    const getPositionOnLeftSide = () => {
        return targetPosition.left - (offsetX * 2.5) - popupWidth;
    }

    const positionTop = targetPosition.top;
    const positionLeft = isWiderThanParentWidth() ? getPositionOnLeftSide() : getPositionOnRightSide();

    const getFieldRows = () => {
        const fieldRows = [];

        let dataProperty;
        let field;

        for (const dataPropertyName in props.data.fields) {
            if (!props.data.fields.hasOwnProperty(dataPropertyName)) {
                continue;
            }

            dataProperty = props.data.fields[dataPropertyName];

            field = getFieldElementElement(dataProperty.type, dataProperty.value, false);

            fieldRows.push(
                <tr>
                    <th>{dataProperty.label}:</th>
                    <td>{field}</td>
                </tr>
            );
        }

        return fieldRows;
    };

    const handleOnClick = (e, linkData) => {
        e.preventDefault();

        callAjaxAction(linkData.action, linkData.args, props.ajaxUrl).then((result) => {
            props.onItemActionClickCallback(linkData.action, props.id, result);
        });
    }


    const getPostLinks = () => {
        const links = [];
        let linkData;

        for (const linkName in props.data.links) {
            if (!props.data.links.hasOwnProperty(linkName)) {
                continue;
            }

            linkData = props.data.links[linkName];

            if (linkData.url) {
                links.push(
                    <a href={linkData.url}>{linkData.label}</a>
                );
            } else if (linkData.action) {
                links.push(
                    <a href="javascript:void(0);" onClick={(e) => handleOnClick(e, linkData)}>{linkData.label}</a>
                );
            }
        }

        return links;
    }

    return (
        <div className="publishpress-calendar-popup" style={{top: positionTop, left: positionLeft}}>
            <div className="publishpress-calendar-popup-title">
                {props.color &&
                <span className="publishpress-calendar-item-color" style={{backgroundColor: props.color}}/>
                }
                {props.icon &&
                <span className={'dashicons ' + props.icon}/>
                }
                {props.title}
            </div>
            <hr/>
            <table>
                <tbody>
                {getFieldRows()}
                </tbody>
            </table>
            <hr/>
            <div className="publishpress-calendar-popup-links">
                {getPostLinks()}
            </div>
        </div>
    )
}
