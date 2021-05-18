import {getDateAsStringInWpFormat} from './Functions';
import CalendarItem from "./CalendarItem";
import CalendarCell from "./CalendarCell";

const $ = jQuery;

export default function CalendarBody(props) {
    function initDraggable() {
        $('.publishpress-calendar-day-items li').draggable({
            zIndex: 99999,
            helper: 'clone',
            opacity: 0.40,
            containment: '.publishpress-calendar-days',
            cursor: 'move',
            classes: {
                'ui-draggable': 'publishpress-calendar-draggable',
                'ui-draggable-handle': 'publishpress-calendar-draggable-handle',
                'ui-draggable-dragging': 'publishpress-calendar-draggable-dragging',
            }
        });

        $('.publishpress-calendar-day-items').droppable({
            addClasses: false,
            classes: {
                'ui-droppable-hover': 'publishpress-calendar-state-active',
            },
            drop: props.handleOnDropItemCallback,
            over: props.handleOnHoverCellCallback
        });
    }

    React.useEffect(initDraggable);

    let cells = [];
    let cell;

    for (const date in props.cells) {
        cell = props.cells[date];

        cells.push(
            <CalendarCell
                key={'day-' + cell.date.getTime()}
                date={cell.date}
                shouldDisplayMonthName={cell.shouldDisplayMonthName}
                todayDate={props.todayDate}
                isLoading={cell.isLoading}
                items={cell.items}
                timeFormat={props.timeFormat}/>
        );
    }

    return (<ul className="publishpress-calendar-days">{cells}</ul>)
}
