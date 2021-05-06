import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";

const {__} = wp.i18n;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <NavigationBar/>

            <section className="publishpress-calendar-section">
                <WeekDays weekStartsOnSunday={props.weekStartsOnSunday}/>
                <CalendarBody
                    firstDateToDisplay={props.firstDateToDisplay}
                    numberOfWeeksToDisplay={props.numberOfWeeksToDisplay}
                    theme={theme}
                    todayDate={props.todayDate}
                    weekStartsOnSunday={props.weekStartsOnSunday}
                    timezoneOffset={props.timezoneOffset}
                    timeFormat={props.timeFormat}/>
            </section>
        </div>
    )
}
