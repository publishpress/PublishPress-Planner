import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";
import MessageBar from "./MessageBar";
import {getDateAsStringInWpFormat} from "./Functions";

const useState = React.useState;
const useEffect = React.useEffect;
const {__} = wp.i18n;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [items, setItems] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [message, setMessage] = useState();

    async function _fetchData() {
        setIsLoading(true);
        setItems({});
        setMessage(__('Loading...', 'publishpress'));

        const response = await fetch(props.dataUrl + '&start_date=' + getDateAsStringInWpFormat(props.firstDateToDisplay) + '&number_of_weeks=' + props.numberOfWeeksToDisplay);
        const responseJson = await response.json();

        setItems(responseJson);
        setIsLoading(false);
        setMessage(null);
    }

    function _handleRefreshClick(e) {
        e.preventDefault();

        _fetchData();
    }

    useEffect(_fetchData, []);

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <NavigationBar refreshFunction={_handleRefreshClick}/>

            <MessageBar showSpinner={isLoading} message={message}/>

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
