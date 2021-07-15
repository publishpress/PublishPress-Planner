import {getHourStringOnFormat} from './Functions';
import ItemPopup from './ItemPopup';

const {__} = wp.i18n;
const $ = jQuery;

export default function Item(props) {
    const DEFAULT_TIME_FORMAT = 'g:i a';
    const DEFAULT_LABEL = __('Untitled', 'publishpress');

    const calendarItem = React.useRef(null);

    const getHourString = () => {
        let timestampDate = new Date(Date.parse(props.timestamp));

        return getHourStringOnFormat(timestampDate, props.timeFormat || DEFAULT_TIME_FORMAT);
    }

    const getClassName = () => {
        let className = 'publishpress-calendar-item';

        if (props.isPopupOpened) {
            className += ' publishpress-calendar-item-opened-popup';
        }

        if (props.canMove) {
            className += ' publishpress-calendar-item-movable';
        }

        return className;
    }

    const isPopupElementOrChildrenOfPopup = (element) => {
        return $(element).hasClass('publishpress-calendar-popup')
            || $(element).parents('.publishpress-calendar-popup').length > 0;
    }

    const dispatchClickEvent = (e) => {
        if (isPopupElementOrChildrenOfPopup(e.target)) {
            return;
        }

        props.onClickItemCallback(props.id);
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
                       data={props.isPopupOpened ? props.getPopupItemDataCallback() : null}
                       onItemActionClickCallback={props.onItemActionClickCallback}
                       ajaxUrl={props.ajaxUrl}/>
            }
        </li>
    )
}
