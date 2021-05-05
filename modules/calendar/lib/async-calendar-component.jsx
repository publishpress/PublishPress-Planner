import {getBeginDateOfWeekByDate} from "./calendar-functions";
import {PublishPressAsyncCalendarItem} from "./async-calendar-item";

let {__} = wp.i18n;

class PublishPressAsyncCalendar extends React.Component {
    constructor(props) {
        super(props);

        // Compensate the timezone in the browser with the server's timezone
        const timezoneOffset = (new Date().getTimezoneOffset() / 60) + parseInt(this.props.timezoneOffset);

        this.props.todayDate.setHours(this.props.todayDate.getHours() + timezoneOffset);
        this.setState({
            data: this._getData()
        });

        this.props.firstDateToDisplay = this._getFirstDayOfWeek(this.props.firstDateToDisplay);
    }

    static defaultProps = {
        todayDate: new Date(),
        numberOfWeeksToDisplay: 5,
        sundayIsFirstDayOfWeek: true,
        timezoneOffset: 0,
        firstDateToDisplay: new Date(),
        theme: 'light',
        getDataCallback: null
    }

    _getData() {
        if (typeof this.props.getDataCallback !== 'function') {
            return [];
        }

        return this.props.getDataCallback({
            numberOfWeeksToDisplay: this.props.numberOfWeeksToDisplay,
            firstDateToDisplay: this.props.firstDateToDisplay
        });
    }

    _getWeekDaysCells() {
        let weekDayLabel = [
            __('Sun', 'publishpress'),
            __('Mon', 'publishpress'),
            __('Tue', 'publishpress'),
            __('Wed', 'publishpress'),
            __('Thu', 'publishpress'),
            __('Fri', 'publishpress'),
            __('Sat', 'publishpress'),
        ];

        let weekDaysCells = [];
        let firstIndexOfWeekDay = this.props.sundayIsFirstDayOfWeek ? 0 : 1;
        let currentDayOfWeek = firstIndexOfWeekDay - 1;

        for (let i = 0; i < 7; i++) {
            currentDayOfWeek++;

            if (i === 6 && !this.props.sundayIsFirstDayOfWeek) {
                currentDayOfWeek = 0;
            }

            weekDaysCells.push(
                <li data-week-day={currentDayOfWeek}>{weekDayLabel[currentDayOfWeek]}</li>
            );
        }

        return weekDaysCells;
    }

    _getDayItemClassName(dayDate) {
        const businessDays = [1, 2, 3, 4, 5];

        let dayItemClassName = businessDays.indexOf(dayDate.getDay()) >= 0 ? 'business-day' : 'weekend-day'

        if (this.props.todayDate.getFullYear() === dayDate.getFullYear()
            && this.props.todayDate.getMonth() === dayDate.getMonth()
            && this.props.todayDate.getDate() === dayDate.getDate()
        ) {
            dayItemClassName += ' publishpress-calendar-today';
        }

        return 'publishpress-calendar-' + dayItemClassName;
    }

    _getMonthName(month) {
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

    _getDayItems() {
        let items = [];

        items.push(<PublishPressAsyncCalendarItem/>);
        items.push(<PublishPressAsyncCalendarItem/>);

        return items;
    }

    _getFirstDayOfWeek(theDate) {
        return getBeginDateOfWeekByDate(theDate, this.props.sundayIsFirstDayOfWeek);
    }

    _getDaysCells() {
        const firstDayOfTheFirstWeek = this._getFirstDayOfWeek(this.props.firstDateToDisplay);
        const numberOfDaysToDisplay = this.props.numberOfWeeksToDisplay * 7;


        let daysCells = [];
        let dayItems = [];
        let dayDate;
        let lastMonthDisplayed = firstDayOfTheFirstWeek.getMonth();
        let shouldDisplayMonthName;

        for (let i = 0; i < numberOfDaysToDisplay; i++) {
            dayDate = new Date(firstDayOfTheFirstWeek);
            dayDate.setDate(dayDate.getDate() + i);

            shouldDisplayMonthName = lastMonthDisplayed !== dayDate.getMonth() || i === 0;

            daysCells.push(
                <li
                    className={this._getDayItemClassName(dayDate)}
                    data-year={dayDate.getFullYear()}
                    data-month={dayDate.getMonth() + 1}
                    data-day={dayDate.getDate()}>
                    <div className="publishpress-calendar-date">
                        {shouldDisplayMonthName &&
                        <span
                            className="publishpress-calendar-month-name">{this._getMonthName(dayDate.getMonth())}</span>
                        }
                        {dayDate.getDate()}
                    </div>
                    <ul className="publishpress-calendar-day-items">{this._getDayItems()}</ul>
                </li>
            );

            lastMonthDisplayed = dayDate.getMonth();
        }

        return daysCells;
    }

    render() {
        return (
            <div className={'publishpress-calendar publishpress-calendar-theme-' + this.props.theme}>

                <ul className="publishpress-calendar-week-days">{this._getWeekDaysCells()}</ul>
                <ul className="publishpress-calendar-days">{this._getDaysCells()}</ul>
            </div>
        )
    }
}

jQuery(function ($) {
    ReactDOM.render(
        <PublishPressAsyncCalendar
            firstDateToDisplay={new Date(Date.parse(pp_calendar_params.calendar_filters.firstDateToDisplay))}
            sundayIsFirstDayOfWeek={pp_calendar_params.calendar_filters.sundayIsFirstDayOfWeek}
            numberOfWeeksToDisplay={pp_calendar_params.calendar_filters.numberOfWeeksToDisplay}
            todayDate={new Date(Date.parse(pp_calendar_params.calendar_filters.todayDate))}
            timezoneOffset={pp_calendar_params.calendar_filters.timezoneOffset}
            theme={pp_calendar_params.calendar_filters.theme}
            getDataCallback={publishpressAsyncCalendarGetData}/>,

        document.getElementById('publishpress-calendar-wrap')
    );
});

function publishpressAsyncCalendarGetData(numberOfWeeksToDisplay, firstDateToDisplay) {
    return [];
}
