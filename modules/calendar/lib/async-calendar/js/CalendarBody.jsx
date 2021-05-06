import {getBeginDateOfWeekByDate} from './Functions';

export default function CalendarBody(props) {
    // Compensate the timezone in the browser with the server's timezone
    // const timezoneOffset = (new Date().getTimezoneOffset() / 60) + parseInt(props.timezoneOffset);
    //
    // props.todayDate.setHours(props.todayDate.getHours() + timezoneOffset);
    // props.firstDateToDisplay = this._getFirstDayOfWeek(props.firstDateToDisplay);

    // this.state = {
    //     error: null,
    //     isLoaded: false,
    //     items: []
    // };

    // static defaultProps = {
    //     todayDate: new Date(),
    //     numberOfWeeksToDisplay: 5,
    //     weekStartsOnSunday: true,
    //     timezoneOffset: 0,
    //     firstDateToDisplay: new Date(),
    //     theme: 'light',
    //     getDataCallback: null,
    //     timeFormat: 'g:i a',
    // }

    console.log(props);

    function _getFirstDayOfWeek(theDate) {
        return getBeginDateOfWeekByDate(theDate, props.weekStartsOnSunday);
    }

    // function _isValidDate(theDate) {
    //     return Object.prototype.toString.call(theDate) === '[object Date]';
    // }
    //
    // function _getDateFromString(theDate) {
    //     return new Date(Date.parse(theDate));
    // }
    //
    // function _getDateAsStringInWpFormat(theDate) {
    //     return theDate.getFullYear() + '-'
    //         + (theDate.getMonth() + 1).toString().padStart(2, '0') + '-'
    //         + theDate.getDate().toString().padStart(2, '0');
    // }

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
        <ul className={'publishpress-calendar-days'}>
            {daysCells.map((dayDate) => {
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
                    </li>
                )
            })}
        </ul>
    )
}
