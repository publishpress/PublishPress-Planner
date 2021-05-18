import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";
import MessageBar from "./MessageBar";
import {calculateWeeksInMilliseconds, getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from "./Functions";

const {__} = wp.i18n;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [firstDateToDisplay, setFirstDateToDisplay] = React.useState(props.firstDateToDisplay);
    const [items, setItems] = React.useState({});
    const [cells, setCells] = React.useState([]);
    const [isLoading, setIsLoading] = React.useState(false);
    const [message, setMessage] = React.useState();

    function getUrl(action, query) {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    }

    function init() {
        initCells();
        fetchData();
    }

    function initCells() {
        const firstDayOfTheFirstWeek = getFirstDayOfWeek(props.firstDateToDisplay);
        const numberOfDaysToDisplay = props.numberOfWeeksToDisplay * 7;

        let newCells = [];
        let dayDate;
        let lastMonthDisplayed = firstDayOfTheFirstWeek.getMonth();
        let shouldDisplayMonthName;

        for (let i = 0; i < numberOfDaysToDisplay; i++) {
            dayDate = new Date(firstDayOfTheFirstWeek);
            dayDate.setDate(dayDate.getDate() + i);

            shouldDisplayMonthName = lastMonthDisplayed !== dayDate.getMonth() || i === 0;

            newCells.push({
                date: dayDate,
                shouldDisplayMonthName: shouldDisplayMonthName,
                isLoading: false
            });

            lastMonthDisplayed = dayDate.getMonth();
        }

        setCells(newCells);
    }

    function getFirstDayOfWeek(theDate) {
        return getBeginDateOfWeekByDate(theDate, props.weekStartsOnSunday);
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

    function navigate(offsetInWeeks) {
        const offset = calculateWeeksInMilliseconds(offsetInWeeks);

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

        navigate(props.numberOfWeeksToDisplay * -1);
    }

    function handleBackOnClick(e) {
        e.preventDefault();

        navigate(-1);
    }

    function handleForwardOnClick(e) {
        e.preventDefault();

        navigate(1);
    }

    function handleForwardPageOnClick(e) {
        e.preventDefault();

        navigate(props.numberOfWeeksToDisplay);
    }

    function handleTodayOnClick(e) {
        e.preventDefault();

        setFirstDateToDisplay(getBeginDateOfWeekByDate(props.todayDate, props.weekStartsOnSunday));

        fetchData();
    }

    function getItemByDateAndIndex(date, index) {
        return items[date][index];
    }

    async function moveItemToNewDate(itemDate, itemIndex, newYear, newMonth, newDay) {
        let item = getItemByDateAndIndex(itemDate, itemIndex);

        setIsLoading(true);
        setMessage(__('Moving item...', 'publishpress'));

        const dataUrl = getUrl(props.actionMoveItem);

        const formData = new FormData();
        formData.append('id', item.id);
        formData.append('year', newYear);
        formData.append('month', newMonth);
        formData.append('day', newDay);

        const response = await fetch(dataUrl, {
            method: 'POST',
            body: formData
        });

        fetchData();

        setIsLoading(false);
        setMessage(null);
    }

    React.useEffect(init, []);

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
                    firstDateToDisplay={getFirstDayOfWeek(props.firstDateToDisplay)}
                    numberOfWeeksToDisplay={props.numberOfWeeksToDisplay}
                    theme={theme}
                    todayDate={props.todayDate}
                    weekStartsOnSunday={props.weekStartsOnSunday}
                    timeFormat={props.timeFormat}
                    items={items}
                    cells={cells}
                    moveItemToANewDateCallback={moveItemToNewDate}/>
            </div>
        </div>
    )
}
