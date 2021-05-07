import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";
import {getDateAsStringInWpFormat} from "./Functions";

const useState = React.useState;
const useEffect = React.useEffect;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [items, setItems] = useState([]);

    async function _fetchData() {
        const response = await fetch(props.dataUrl + '&start_date=' + getDateAsStringInWpFormat(props.firstDateToDisplay) + '&number_of_weeks=' + props.numberOfWeeksToDisplay);
        const responseJson = await response.json();

        setItems(responseJson);
    }

    useEffect(_fetchData, []);

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <NavigationBar/>

            <div className="publishpress-calendar-section">
                <WeekDays weekStartsOnSunday={props.weekStartsOnSunday}/>
                <CalendarBody
                    firstDateToDisplay={props.firstDateToDisplay}
                    numberOfWeeksToDisplay={props.numberOfWeeksToDisplay}
                    theme={theme}
                    todayDate={props.todayDate}
                    weekStartsOnSunday={props.weekStartsOnSunday}
                    timezoneOffset={props.timezoneOffset}
                    timeFormat={props.timeFormat}
                    items={items}/>
            </div>
        </div>
    )
}
