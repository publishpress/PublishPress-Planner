import {getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from './Functions';
import CalendarItem from "./CalendarItem";
import CalendarCell from "./CalendarCell";

export default function CalendarBody(props) {
    props.firstDateToDisplay = getFirstDayOfWeek(props.firstDateToDisplay);

    function getFirstDayOfWeek(theDate) {
        return getBeginDateOfWeekByDate(theDate, props.weekStartsOnSunday);
    }

    function isValidDate(theDate) {
        return Object.prototype.toString.call(theDate) === '[object Date]';
    }

    function getCalendarDays() {
        const firstDayOfTheFirstWeek = getFirstDayOfWeek(props.firstDateToDisplay);
        const numberOfDaysToDisplay = props.numberOfWeeksToDisplay * 7;

        let calendarDays = [];
        let dayDate;
        let lastMonthDisplayed = firstDayOfTheFirstWeek.getMonth();
        let shouldDisplayMonthName;

        for (let i = 0; i < numberOfDaysToDisplay; i++) {
            dayDate = new Date(firstDayOfTheFirstWeek);
            dayDate.setDate(dayDate.getDate() + i);

            shouldDisplayMonthName = lastMonthDisplayed !== dayDate.getMonth() || i === 0;

            calendarDays.push({
                date: dayDate,
                shouldDisplayMonthName: shouldDisplayMonthName
            });

            lastMonthDisplayed = dayDate.getMonth();
        }

        return calendarDays;
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

                $dayParent.addClass('publishpress-calendar-day-loading');

                props.moveItemToANewDateCallback(
                    dateTime,
                    $item.data('index'),
                    $dayCell.data('year'),
                    $dayCell.data('month'),
                    $dayCell.data('day')
                ).then(() => {
                    $dayParent.removeClass('publishpress-calendar-day-hover');
                    $dayParent.removeClass('publishpress-calendar-day-loading');
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

    const daysCells = getCalendarDays();

    return (
        <ul className="publishpress-calendar-days">
            {daysCells.map((dayDate) => {
                let dayItemsElements = [];

                if (isValidDate(dayDate.date)) {
                    const dateString = getDateAsStringInWpFormat(dayDate.date);
                    const dayItems = props.items[dateString] ? props.items[dateString] : null;

                    if (dayItems) {
                        for (let i = 0; i < dayItems.length; i++) {
                            dayItemsElements.push(
                                <CalendarItem
                                    key={'item-' + dayItems[i].id + '-' + dayDate.date.getTime()}
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
                        key={'day-' + dayDate.date.getTime()}
                        date={dayDate.date}
                        shouldDisplayMonthName={dayDate.shouldDisplayMonthName}
                        todayDate={props.todayDate}
                        items={dayItemsElements}/>
                )
            })}
        </ul>
    )
}
