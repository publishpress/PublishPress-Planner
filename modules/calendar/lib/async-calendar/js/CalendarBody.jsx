import {getDateAsStringInWpFormat} from './Functions';
import CalendarItem from "./CalendarItem";
import CalendarCell from "./CalendarCell";

export default function CalendarBody(props) {
    function isValidDate(theDate) {
        return Object.prototype.toString.call(theDate) === '[object Date]';
    }

    function initDraggable() {
        const $ = jQuery;

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

        let $lastHoveredCell;

        $('.publishpress-calendar-day-items').droppable({
            addClasses: false,
            classes: {
                'ui-droppable-hover': 'publishpress-calendar-state-active',
            },
            drop: (event, ui) => {
                const $dayCell = $(event.target).parent();
                const $item = $(ui.draggable[0]);
                const dateTime = getDateAsStringInWpFormat(new Date($item.data('datetime')));
                const $dayParent = $(event.target).parents('li');

                // $dayParent.addClass('publishpress-calendar-day-loading');

                props.moveItemToANewDateCallback(
                    dateTime,
                    $item.data('index'),
                    $dayCell.data('year'),
                    $dayCell.data('month'),
                    $dayCell.data('day')
                ).then(() => {
                    $dayParent.removeClass('publishpress-calendar-day-hover');
                    // $dayParent.removeClass('publishpress-calendar-day-loading');
                });
            },
            over: (event, ui) => {
                if ($lastHoveredCell) {
                    $lastHoveredCell.removeClass('publishpress-calendar-day-hover');
                }

                const $dayParent = $(event.target).parents('li');
                $dayParent.addClass('publishpress-calendar-day-hover');

                $lastHoveredCell = $dayParent;
            }
        });
    }

    React.useEffect(initDraggable);

    return (
        <ul className="publishpress-calendar-days">
            {props.cells.map((dayCell) => {
                let cellItems = [];

                if (isValidDate(dayCell.date)) {
                    const dateString = getDateAsStringInWpFormat(dayCell.date);
                    const dayItems = props.items[dateString] ? props.items[dateString] : null;

                    if (dayItems) {
                        for (let i = 0; i < dayItems.length; i++) {
                            cellItems.push(
                                <CalendarItem
                                    key={'item-' + dayItems[i].id + '-' + dayCell.date.getTime()}
                                    icon={dayItems[i].icon}
                                    color={dayItems[i].color}
                                    label={dayItems[i].label}
                                    id={dayItems[i].id}
                                    timestamp={dayItems[i].timestamp}
                                    timeFormat={props.timeFormat}
                                    showTime={dayItems[i].showTime}
                                    showIcon={true}
                                    index={i}/>
                            );
                        }
                    }
                }

                return (
                    <CalendarCell
                        key={'day-' + dayCell.date.getTime()}
                        date={dayCell.date}
                        shouldDisplayMonthName={dayCell.shouldDisplayMonthName}
                        todayDate={props.todayDate}
                        isLoading={dayCell.isLoading}
                        items={cellItems}/>

                )
            })}
        </ul>
    )
}
