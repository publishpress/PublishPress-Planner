import TimeField from "./fields/TimeField";
import AuthorField from "./fields/AuthorField";

const $ = jQuery;

export default function ItemPopup(props) {
    if (!props.data) {
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

            switch (dataProperty.type) {
                case 'time':
                    field = <TimeField value={dataProperty.value} isEditing={isEditing}/>;
                    break;

                case 'author':
                    field = <AuthorField value={dataProperty.value} isEditing={isEditing}/>;
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
                <a href="#">Edit</a>
                <a href="#">Trash</a>
                <a href="#">Preview</a>
                <a href="#">Notify me</a>
            </div>
        </div>
    )
}
