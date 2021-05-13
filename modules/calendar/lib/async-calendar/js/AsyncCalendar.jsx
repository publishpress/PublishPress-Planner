import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";
import MessageBar from "./MessageBar";
import {calculateWeeksInMilliseconds, getDateAsStringInWpFormat, getBeginDateOfWeekByDate} from "./Functions";

const useState = React.useState;
const useEffect = React.useEffect;
const {__} = wp.i18n;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [firstDateToDisplay, setFirstDateToDisplay] = useState(props.firstDateToDisplay);
    const [items, setItems] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [message, setMessage] = useState();

    function getUrl(action, query) {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    }

    async function fetchData() {
        setIsLoading(true);
        setMessage(__('Loading...', 'publishpress'));

        const dataUrl = getUrl(props.actionGetData, '&start_date=' + getDateAsStringInWpFormat(props.firstDateToDisplay) + '&number_of_weeks=' + props.numberOfWeeksToDisplay);

        const response = await fetch(dataUrl);
        const responseJson = await response.json();

        setItems(responseJson);
        setIsLoading(false);
        setMessage(null);
    }

    function navigate(offset) {
        const newDate = new Date(
            firstDateToDisplay.getTime() + offset
        );

        setFirstDateToDisplay(newDate);

        fetchData();
    }

    function handleRefreshOnClick(e) {
        e.preventDefault();

        fetchData();
    }

    function handleBackPageOnClick(e) {
        e.preventDefault();

        navigate(calculateWeeksInMilliseconds(props.numberOfWeeksToDisplay) * -1);
    }

    function handleBackOnClick(e) {
        e.preventDefault();

        navigate(calculateWeeksInMilliseconds(1) * -1);
    }

    function handleForwardOnClick(e) {
        e.preventDefault();

        navigate(calculateWeeksInMilliseconds(1));
    }

    function handleForwardPageOnClick(e) {
        e.preventDefault();

        navigate(calculateWeeksInMilliseconds(props.numberOfWeeksToDisplay));
    }

    function handleTodayOnClick(e) {
        e.preventDefault();

        setFirstDateToDisplay(getBeginDateOfWeekByDate(props.todayDate, props.weekStartsOnSunday));

        fetchData();
    }

    useEffect(fetchData, []);

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <NavigationBar
                refreshOnClick={handleRefreshOnClick}
                backPageOnClick={handleBackPageOnClick}
                backOnClick={handleBackOnClick}
                forwardOnClick={handleForwardOnClick}
                forwardPageOnClick={handleForwardPageOnClick}
                todayOnClick={handleTodayOnClick}/>

            <MessageBar showSpinner={isLoading} message={message}/>

            <div className="publishpress-calendar-section">
                <WeekDays weekStartsOnSunday={props.weekStartsOnSunday}/>
                <CalendarBody
                    firstDateToDisplay={firstDateToDisplay}
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
