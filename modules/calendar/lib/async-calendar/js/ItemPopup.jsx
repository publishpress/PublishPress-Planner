import TimeField from "./fields/TimeField";
import AuthorField from "./fields/AuthorField";

const $ = jQuery;

export default function ItemPopup(props) {
    const popupRef = React.useRef(null);
    const [top, setTop] = React.useState(0);
    const [left, setLeft] = React.useState(0);
    const offsetX = 10;
    const offsetWidth = 300;
    const isEditing = false;

    const setPosition = () => {
        const targetPosition = $(props.target.current).position();
        const targetOffset = $(props.target.current).offset();
        const targetWidth = $(props.target.current).width();
        const popupWidth = $(popupRef.current).width();

        const isWiderThanParentWidth = () => {
            return (targetOffset.left + popupWidth + offsetX) >= $(document).width() - offsetWidth;
        }

        const getPositionOnRightSide = () => {
            return targetPosition.left + targetWidth + offsetX;
        }

        const getPositionOnLeftSide = () => {
            return targetPosition.left - (offsetX * 3) - popupWidth;
        }

        setTop(targetPosition.top);
        setLeft(isWiderThanParentWidth() ? getPositionOnLeftSide() : getPositionOnRightSide());
    }

    const didMount = () => {
        setPosition();

        return () => {

        }
    }

    React.useEffect(didMount, []);

    if (!props.data) {
        return <></>;
    }

    const fieldsRows = [];

    let dataProperty;
    let field;
    for (const dataPropertyName in props.data) {
        if (!props.data.hasOwnProperty(dataPropertyName)) {
            continue;
        }

        dataProperty = props.data[dataPropertyName];

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

        fieldsRows.push(
            <tr>
                <th>{dataProperty.label}:</th>
                <td>{field}</td>
            </tr>
        );
    }

    return (
        <div className="publishpress-calendar-popup" ref={popupRef} style={{top: top, left: left}}>
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
                {fieldsRows}
                </tbody>
            </table>
        </div>
    )
}
