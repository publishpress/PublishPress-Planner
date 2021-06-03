import {getHourStringOnFormat} from './Functions';
import ItemPopup from './ItemPopup';

const {__} = wp.i18n;

export default function CalendarItem(props) {
    const DEFAULT_TIME_FORMAT = 'g:i a';
    const DEFAULT_LABEL = __('Untitled', 'publishpress');

    const calendarItem = React.useRef(null);

    const getHourString = () => {
        let timestampDate = new Date(Date.parse(props.timestamp));

        return getHourStringOnFormat(timestampDate, props.timeFormat || DEFAULT_TIME_FORMAT);
    }

    const getClassName = () => {
        let className = 'publishpress-calendar-item';

        if (props.collapse) {
            className += ' publishpress-calendar-item-collapse';
        }

        if (props.isPopupOpened) {
            className += ' publishpress-calendar-item-opened-popup';
        }

        return className;
    }

    const dispatchClickEvent = () => {
        window.dispatchEvent(
            new CustomEvent(
                'PublishpressCalendar:clickItem',
                {
                    detail: {
                        id: props.id
                    }
                }
            )
        );
    }

    const iconElement = props.showIcon && props.icon ?
        <span className={'dashicons ' + props.icon}> </span> : null;

    const timeElement = props.showTime ?
        <time className="publishpress-calendar-item-time"
              dateTime={props.timestamp}
              title={props.timestamp}>{getHourString()}</time> : null;

    const label = props.label || DEFAULT_LABEL;

    return (
        <li
            ref={calendarItem}
            className={getClassName()}
            style={{backgroundColor: props.color}}
            data-index={props.index}
            data-id={props.id}
            data-datetime={props.timestamp}
            onClick={dispatchClickEvent}>

            {iconElement}{timeElement}
            {label}
            {props.isPopupOpened &&
            <ItemPopup target={calendarItem}
                       id={props.id}
                       title={label}
                       icon={props.icon}
                       timestamp={props.timestamp}
                       color={props.color}
                       data={props.popupItemData}/>
            }
        </li>
    )
}
