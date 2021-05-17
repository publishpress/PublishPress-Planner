import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import CalendarBody from "./CalendarBody";
import MessageBar from "./MessageBar";
import {calculateWeeksInMilliseconds, getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from "./Functions";

const useState = React.useState;
const useEffect = React.useEffect;
const {__} = wp.i18n;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [firstDateToDisplay, setFirstDateToDisplay] = useState(props.firstDateToDisplay);
    const [items, setItems] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [message, setMessage] = useState();
    const [, setForceUpdate] = useState(Date.now());

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

    function initDraggable() {
        $ = jQuery;
        $('.publishpress-calendar-day-items li').draggable({
            zIndex: 99999,
            helper: 'clone',
            opacity: 0.40,
            containment: '.publishpress-calendar-days',
            cursor: 'move',
            classes: {
                'ui-draggable': 'publishpress-calendar-draggable',
                'ui-draggable-handle': 'publishpress-calendar-draggable-handle',
                'ui-draggable-dragging': 'publishpress-calendar-draggable-dragging',
            }
        });

        let $lastHoveredCell;

        $('.publishpress-calendar-day-items').droppable({
            addClasses: false,
            classes: {
                'ui-droppable-hover': 'publishpress-calendar-state-active',
            },
            drop: (event, ui) => {
                const $dayCell = $(event.target).parent();
                const $item = $(ui.draggable[0]);
                const dateTime = getDateAsStringInWpFormat(new Date($item.data('datetime')));
                const $dayParent = $(event.target).parents('li');

                $dayParent.addClass('publishpress-calendar-day-loading');

                moveItemToNewDate(
                    dateTime,
                    $item.data('index'),
                    $dayCell.data('year'),
                    $dayCell.data('month'),
                    $dayCell.data('day')
                ).then(() => {
                    $dayParent.removeClass('publishpress-calendar-day-hover');
                    $dayParent.removeClass('publishpress-calendar-day-loading');
                });
            },
            over: (event, ui) => {
                if ($lastHoveredCell) {
                    $lastHoveredCell.removeClass('publishpress-calendar-day-hover');
                }

                const $dayParent = $(event.target).parents('li');
                $dayParent.addClass('publishpress-calendar-day-hover');

                $lastHoveredCell = $dayParent;
            }
        });
    }

    async function moveItemToNewDate(itemDate, itemIndex, newYear, newMonth, newDay) {
        let newItemsList = JSON.parse(JSON.stringify(items));
        let item = newItemsList[itemDate][itemIndex];

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

    useEffect(fetchData, []);
    useEffect(initDraggable);

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
