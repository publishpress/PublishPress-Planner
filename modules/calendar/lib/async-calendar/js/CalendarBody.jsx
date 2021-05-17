import {getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from './Functions';
import CalendarItem from "./CalendarItem";
import CalendarCell from "./CalendarCell";

export default function CalendarBody(props) {
    // Compensate the timezone in the browser with the server's timezone
    const timezoneOffset = (new Date().getTimezoneOffset() / 60) + parseInt(props.timezoneOffset);

    props.todayDate.setHours(props.todayDate.getHours() + timezoneOffset);
    props.firstDateToDisplay = _getFirstDayOfWeek(props.firstDateToDisplay);

    function _getFirstDayOfWeek(theDate) {
        return getBeginDateOfWeekByDate(theDate, props.weekStartsOnSunday);
    }

    function _isValidDate(theDate) {
        return Object.prototype.toString.call(theDate) === '[object Date]';
    }

    function _getCalendarDays() {
        const firstDayOfTheFirstWeek = _getFirstDayOfWeek(props.firstDateToDisplay);
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

    const daysCells = _getCalendarDays();

    return (
        <ul className="publishpress-calendar-days">
            {daysCells.map((dayDate) => {
                let dayItemsElements = [];

                if (_isValidDate(dayDate.date)) {
                    const dateString = getDateAsStringInWpFormat(dayDate.date);
                    const dayItems = props.items[dateString] ? props.items[dateString] : null;

                    if (dayItems) {
                        for (let i = 0; i < dayItems.length; i++) {
                            dayItemsElements.push(
                                <CalendarItem
                                    key={dayItems[i].id + '-' + dayDate.date.getTime()}
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
                        date={dayDate.date}
                        shouldDisplayMonthName={dayDate.shouldDisplayMonthName}
                        todayDate={props.todayDate}
                        items={dayItemsElements}/>
                )
            })}
        </ul>
    )
}
