import {getBeginDateOfWeekByDate} from "./calendar-functions";

let {__} = wp.i18n;


class PublishPressAsyncCalendar extends React.Component {
    constructor(props) {
        super(props);

        this.props.todayDate.setHours(0, 0, 0);
    }

    static defaultProps = {
        todayDate: new Date(),
        numberOfWeeksToDisplay: 5,
        sundayIsFirstDayOfWeek: true,
        firstDateToDisplay: new Date(),
        theme: 'light'
    }

    _getWeekDaysItems() {
        let weekDayLabel = [
            __('Sun', 'publishpress'),
            __('Mon', 'publishpress'),
            __('Tue', 'publishpress'),
            __('Wed', 'publishpress'),
            __('Thu', 'publishpress'),
            __('Fri', 'publishpress'),
            __('Sat', 'publishpress'),
        ]

        let weekDaysItems = [];
        let firstIndexOfWeekDay = this.props.sundayIsFirstDayOfWeek ? 0 : 1;
        let currentDayOfWeek = firstIndexOfWeekDay - 1;

        for (let i = 0; i < 7; i++) {
            currentDayOfWeek++;

            if (i === 6 && !this.props.sundayIsFirstDayOfWeek) {
                currentDayOfWeek = 0;
            }

            weekDaysItems.push(
                <li data-week-day={currentDayOfWeek}>{weekDayLabel[currentDayOfWeek]}</li>
            );
        }

        return weekDaysItems;
    }

    _getDayItemClassName(dayDate) {
        const businessDays = [1, 2, 3, 4, 5];

        let dayItemClassName = businessDays.indexOf(dayDate.getDay()) >= 0 ? 'business-day' : 'weekend-day'

        return 'publishpress-calendar-' + dayItemClassName;
    }

    _getDaysItems() {
        const firstDayOfTheFirstWeek = getBeginDateOfWeekByDate(this.props.firstDateToDisplay, this.props.sundayIsFirstDayOfWeek);
        const numberOfDaysToDisplay = this.props.numberOfWeeksToDisplay * 7;


        let daysItems = [];
        let dayDate;

        for (let i = 0; i < numberOfDaysToDisplay; i++) {
            dayDate = new Date(firstDayOfTheFirstWeek);
            dayDate.setDate(dayDate.getDate() + i);

            daysItems.push(
                <li className={this._getDayItemClassName(dayDate)}>{dayDate.getDate()}</li>
            );
        }

        return daysItems;
    }

    render() {
        return (
            <div className={'publishpress-async-calendar publishpress-async-calendar-theme-' + this.props.theme}>

                <ul className="publishpress-async-calendar-week-days">{this._getWeekDaysItems()}</ul>
                <ul className="publishpress-async-calendar-days">{this._getDaysItems()}</ul>
            </div>
        )
    }
}

jQuery(function ($) {
    ReactDOM.render(
        <PublishPressAsyncCalendar
            firstDateToDisplay={new Date()}
            sundayIsFirstDayOfWeek={true}
            numberOfWeeksToDisplay={5}
            theme={'light'}/>,

        document.getElementById('publishpress-async-calendar-wrap')
    );
});
