import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";

const useState = React.useState;
const useEffect = React.useEffect;

// const useFetch = dataUrl => {
//     const [data, setData] = useState([]);
//
//     useEffect(async () => {
//         const response = await fetch(dataUrl);
//         const responseJson = await response.json();
//
//         setData(responseJson);
//     }, []);
//
//     return data;
// };

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [items, setItems] = useState([]);

    async function _loadData() {
        const response = await fetch(props.dataUrl);
        const responseJson = await response.json();

        setItems(responseJson);
    }

    useEffect(_loadData, []);

    // function fetchData() {
    //     const items = useFetch(props.dataUrl);
    //
    //     setItems(items);
    // }

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
