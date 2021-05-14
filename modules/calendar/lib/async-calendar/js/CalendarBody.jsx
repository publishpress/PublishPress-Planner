import {getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from './Functions';
import CalendarItem from "./CalendarItem";

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

    function _getDateFromString(theDate) {
        return new Date(Date.parse(theDate));
    }

    function _getMonthName(month) {
        const monthNames = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        ];

        return monthNames[month];
    }

    function _getDayItemClassName(dayDate) {
        const businessDays = [1, 2, 3, 4, 5];

        let dayItemClassName = businessDays.indexOf(dayDate.getDay()) >= 0 ? 'business-day' : 'weekend-day'

        if (props.todayDate.getFullYear() === dayDate.getFullYear()
            && props.todayDate.getMonth() === dayDate.getMonth()
            && props.todayDate.getDate() === dayDate.getDate()
        ) {
            dayItemClassName += ' publishpress-calendar-today';
        }

        return 'publishpress-calendar-' + dayItemClassName;
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
                    <li
                        key={dayDate.date.toString()}
                        className={_getDayItemClassName(dayDate.date)}
                        data-year={dayDate.date.getFullYear()}
                        data-month={dayDate.date.getMonth() + 1}
                        data-day={dayDate.date.getDate()}>

                        <div className="publishpress-calendar-date">
                            {dayDate.shouldDisplayMonthName &&
                            <span
                                className="publishpress-calendar-month-name">{_getMonthName(dayDate.date.getMonth())}</span>
                            }
                            {dayDate.date.getDate()}
                        </div>

                        <ul className="publishpress-calendar-day-items">{dayItemsElements}</ul>
                    </li>
                )
            })}
        </ul>
    )
}
