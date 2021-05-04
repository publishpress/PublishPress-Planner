import {getBeginDateOfWeekByWeekNumber, getWeekNumberByDate} from "./calendar-functions";

let {__} = wp.i18n;

class PublishPressCalendarWeekDaysNames extends React.Component {
    static defaultProps = {
        sundayIsFirstDayOfWeek: true
    }

    render() {
        let weekDayLabel = [
            __('Sunday', 'publishpress'),
            __('Monday', 'publishpress'),
            __('Tuesday', 'publishpress'),
            __('Wednesday', 'publishpress'),
            __('Thursday', 'publishpress'),
            __('Friday', 'publishpress'),
            __('Saturday', 'publishpress'),
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

        return (
            <ul className="publishpress-async-calendar-week-days">{weekDaysItems}</ul>
        )
    }
}

class PublishPressAsyncCalendarDays extends React.Component {
    static defaultProps = {
        sundayIsFirstDayOfWeek: true,
        numberOfWeeksToDisplay: 5,
        firstDateToDisplay: new Date()
    }

    render() {
        let a = getBeginDateOfWeekByWeekNumber(18, 2021, this.props.sundayIsFirstDayOfWeek);

        return (
            <ul><li>{a.toString()}</li></ul>
        )
    }
}

class PublishPressAsyncCalendar extends React.Component {
    static defaultProps = {
        todayDate: new Date(),
        numberOfWeeksToDisplay: 5,
        sundayIsFirstDayOfWeek: true,
        firstDateToDisplay: new Date()
    }

    render() {
        return (
            <div className="publishpress-async-calendar-wrap">
                <PublishPressCalendarWeekDaysNames sundayIsFirstDayOfWeek={this.props.sundayIsFirstDayOfWeek}/>
                <PublishPressAsyncCalendarDays
                    sundayIsFirstDayOfWeek={this.props.sundayIsFirstDayOfWeek}
                    numberOfWeeksToDisplay={this.props.numberOfWeeksToDisplay}
                    firstDateToDisplay={this.props.firstDateToDisplay}/>
            </div>
        )
    }
}

jQuery(function ($) {
    ReactDOM.render(
        <PublishPressAsyncCalendar sundayIsFirstDayOfWeek={true} numberOfWeeksToDisplay={5}/>,
        document.getElementById('publishpress-async-calendar-wrapper')
    );
});
