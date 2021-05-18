import {getHourStringOnFormat} from './Functions';

const {__} = wp.i18n;

export default function CalendarItem(props) {
    const DEFAULT_TIME_FORMAT = 'g:i a';
    const DEFAULT_LABEL = __('Untitled', 'publishpress');

    const calendarItem = React.useRef(null);

    const getHourString = () => {
        let timestampDate = new Date(Date.parse(props.timestamp));

        return getHourStringOnFormat(timestampDate, props.timeFormat || DEFAULT_TIME_FORMAT);
    }

    const iconElement = props.showIcon && props.icon ?
        <span className={'dashicons ' + props.icon}> </span> : null;

    const timeElement = props.showTime ?
        <time className="publishpress-calendar-item-time"
              dateTime={props.timestamp}
              title={props.timestamp}>{getHourString()}</time> : null;

    return (
        <li
            ref={calendarItem}
            className="publishpress-calendar-item"
            style={{backgroundColor: props.color}}
            data-index={props.index}
            data-id={props.id}
            data-datetime={props.timestamp}>{iconElement}{timeElement}{props.label || DEFAULT_LABEL}</li>
    )
}
