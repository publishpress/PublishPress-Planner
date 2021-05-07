import {getHourStringOnFormat} from './Functions';

const {__} = wp.i18n;

export default function CalendarItem(props) {
    const DEFAULT_TIME_FORMAT = 'g:i a';
    const DEFAULT_ICON = 'yes';
    const DEFAULT_LABEL = __('Untitled', 'publishpress');

    function _getHourString() {
        let timestampDate = new Date(Date.parse(props.timestamp));

        return getHourStringOnFormat(timestampDate, props.timeFormat || DEFAULT_TIME_FORMAT);
    }

    const iconElement = (props.showIcon || true) && props.icon ?
        <span className={'dashicons ' + props.icon}> </span> : null;

    const timeElement = (props.showTime || true) ?
        <time className="publishpress-calendar-item-time"
              dateTime={props.timestamp}
              title={props.timestamp}>{_getHourString()}</time> : null;

    return (
        <li className="publishpress-calendar-item"
            data-id={props.id}
            data-datetime={props.timestamp}>{iconElement}{timeElement}{props.label || DEFAULT_LABEL}</li>
    )
    // return (
    //     <li> </li>
    // )
}
