import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import MessageBar from "./MessageBar";
import CalendarCell from "./CalendarCell";
import {calculateWeeksInMilliseconds, getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from "./Functions";

const {__} = wp.i18n;
const $ = jQuery;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [firstDateToDisplay, setFirstDateToDisplay] = React.useState(props.firstDateToDisplay);
    const [cells, setCells] = React.useState({});
    const [isLoading, setIsLoading] = React.useState(false);
    const [message, setMessage] = React.useState();
    const [filters, setFilters] = React.useState({status: null, category: null, tag: null, author: null, postType: null});

    let $lastHoveredCell;

    const getUrl = (action, query) => {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    };

    const didMount = () => {
        prepareDataByDate();

        window.addEventListener('PublishpressCalendar:filter', onFilterEventCallback);

        return () => {
            window.removeEventListener('PublishpressCalendar:filter', onFilterEventCallback);
        }
    };

    const prepareDataByDate = (newDate, filtersOverride) => {
        const numberOfDaysToDisplay = props.numberOfWeeksToDisplay * 7;
        const firstDate = getBeginDateOfWeekByDate((newDate) ? newDate : firstDateToDisplay);

        let newDataList = {};
        let newCell;
        let dayDate;
        let dateString;
        let lastMonthDisplayed = firstDate.getMonth();
        let shouldDisplayMonthName;

        setIsLoading(true);
        setMessage(__('Loading...', 'publishpress'));

        fetchData(filtersOverride).then((fetchedData) => {
            for (let dataIndex = 0; dataIndex < numberOfDaysToDisplay; dataIndex++) {
                dayDate = new Date(firstDate);
                dayDate.setDate(dayDate.getDate() + dataIndex);
                dateString = getDateAsStringInWpFormat(dayDate);

                shouldDisplayMonthName = lastMonthDisplayed !== dayDate.getMonth() || dataIndex === 0;

                newCell = {
                    date: dayDate,
                    shouldDisplayMonthName: shouldDisplayMonthName,
                    isLoading: false,
                    items: []
                };

                if (fetchedData[dateString]) {
                    newCell.items = fetchedData[dateString];

                    for (let itemIndex = 0; itemIndex < newCell.items.length; itemIndex++) {
                        newCell.items[itemIndex].collapse = itemIndex >= props.maxVisibleItems;
                    }
                }

                newDataList[getDateAsStringInWpFormat(dayDate)] = newCell;

                lastMonthDisplayed = dayDate.getMonth();
            }

            setCells(newDataList);

            setIsLoading(false);
            setMessage(null);

            resetClasses();
        });
    };

    const resetClasses = () => {
        $('.publishpress-calendar-day-hover').removeClass('publishpress-calendar-day-hover');
        $('.publishpress-calendar-loading').removeClass('publishpress-calendar-loading');
    };

    const fetchData = async (filtersOverride) => {
        let dataUrl = getUrl(props.actionGetData, '&start_date=' + getDateAsStringInWpFormat(props.firstDateToDisplay) + '&number_of_weeks=' + props.numberOfWeeksToDisplay);

        const filtersToUse = filtersOverride ? filtersOverride : filters;

        if (filtersToUse) {
            if (filtersToUse.status) {
                dataUrl += '&post_status=' + filtersToUse.status;
            }

            if (filtersToUse.category) {
                dataUrl += '&category=' + filtersToUse.category;
            }

            if (filtersToUse.tag) {
                dataUrl += '&post_tag=' + filtersToUse.tag;
            }

            if (filtersToUse.author) {
                dataUrl += '&post_author=' + filtersToUse.author;
            }

            if (filtersToUse.postType) {
                dataUrl += '&post_type=' + filtersToUse.postType;
            }
        }

        const response = await fetch(dataUrl);
        return await response.json();
    }

    const navigateByOffsetInWeeks = (offsetInWeeks) => {
        const offsetInMilliseconds = calculateWeeksInMilliseconds(offsetInWeeks);

        const newDate = new Date(
            firstDateToDisplay.getTime() + offsetInMilliseconds
        );

        setFirstDateToDisplay(newDate);

        prepareDataByDate(newDate);
    };

    const handleRefreshOnClick = (e) => {
        e.preventDefault();

        prepareDataByDate();
    };

    const handleBackPageOnClick = (e) => {
        e.preventDefault();

        navigateByOffsetInWeeks(props.numberOfWeeksToDisplay * -1);
    };

    const handleBackOnClick = (e) => {
        e.preventDefault();

        navigateByOffsetInWeeks(-1);
    };

    const handleForwardOnClick = (e) => {
        e.preventDefault();

        navigateByOffsetInWeeks(1);
    }

    const handleForwardPageOnClick = (e) => {
        e.preventDefault();

        navigateByOffsetInWeeks(props.numberOfWeeksToDisplay);
    };

    const handleTodayOnClick = (e) => {
        e.preventDefault();

        const newDate = getBeginDateOfWeekByDate(props.todayDate, props.weekStartsOnSunday);
        setFirstDateToDisplay(newDate);

        prepareDataByDate(newDate);
    };

    const getItemByDateAndIndex = (date, index) => {
        return cells[date].items[index];
    };

    const moveItemToNewDate = async (itemDate, itemIndex, newYear, newMonth, newDay) => {
        let item = getItemByDateAndIndex(itemDate, itemIndex);

        setIsLoading(true);
        setMessage(__('Moving the item...', 'publishpress'));

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

        response.json().then(() => {
            prepareDataByDate();
        });
    }

    const handleOnDropItemCallback = (event, ui) => {
        const $dayCell = $(event.target);
        const $item = $(ui.draggable[0]);
        const dateTime = getDateAsStringInWpFormat(new Date($item.data('datetime')));

        $dayCell.addClass('publishpress-calendar-loading');

        moveItemToNewDate(
            dateTime,
            $item.data('index'),
            $dayCell.data('year'),
            $dayCell.data('month'),
            $dayCell.data('day')
        );
    };

    const handleOnHoverCellCallback = (event, ui) => {
        if ($lastHoveredCell) {
            $lastHoveredCell.removeClass('publishpress-calendar-day-hover');
        }

        const $dayParent = $(event.target);
        $dayParent.addClass('publishpress-calendar-day-hover');

        $lastHoveredCell = $dayParent;
    };

    const initDraggable = () => {
        $('.publishpress-calendar-day-items li').draggable({
            zIndex: 99999,
            helper: 'clone',
            containment: '.publishpress-calendar table',
            cursor: 'move',
            start: (event, ui) => {
                $(event.target).addClass('ui-draggable-target');
            },
            stop: (event, ui) => {
                $('.ui-draggable-target').removeClass('ui-draggable-target');
            }
        });

        $('.publishpress-calendar tbody > tr > td').droppable({
            drop: handleOnDropItemCallback,
            over: handleOnHoverCellCallback
        });
    };

    const onFilterEventCallback = (e) => {
        let currentFilters;

        switch (e.detail.filter) {
            case 'status':
            case 'category':
            case 'tag':
            case 'author':
            case 'postType':
                if (e.detail.value) {
                    filters[e.detail.filter] = e.detail.value[0].id;
                } else {
                    filters[e.detail.filter] = null;
                }

                currentFilters = filters;
                setFilters({...filters});

                prepareDataByDate(firstDateToDisplay, currentFilters);
                break;
        }
    }

    const calendarBodyRows = () => {
        let rows = [];
        let rowCells;
        let cell;
        let dayIndex = 0;

        for (const date in cells) {
            if (dayIndex === 0) {
                rowCells = [];
            }

            cell = cells[date];

            rowCells.push(
                <CalendarCell
                    key={'day-' + cell.date.getTime()}
                    date={cell.date}
                    shouldDisplayMonthName={cell.shouldDisplayMonthName}
                    todayDate={props.todayDate}
                    isLoading={cell.isLoading}
                    items={cell.items}
                    maxVisibleItems={props.maxVisibleItems}
                    timeFormat={props.timeFormat}/>
            );

            dayIndex++;

            if (dayIndex === 7) {
                dayIndex = 0;
                rows.push(
                    <tr>{rowCells}</tr>
                );
            }
        }

        return rows;
    };

    React.useEffect(didMount, []);
    React.useEffect(initDraggable);

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <NavigationBar
                refreshOnClickCallback={handleRefreshOnClick}
                backPageOnClickCallback={handleBackPageOnClick}
                backOnClickCallback={handleBackOnClick}
                forwardOnClickCallback={handleForwardOnClick}
                forwardPageOnClickCallback={handleForwardPageOnClick}
                todayOnClickCallback={handleTodayOnClick}/>

            <MessageBar showSpinner={isLoading} message={message}/>

            <table>
                <thead>
                <tr>
                    <WeekDays weekStartsOnSunday={props.weekStartsOnSunday}/>
                </tr>
                </thead>
                <tbody>
                {calendarBodyRows()}
                </tbody>
            </table>
        </div>
    )
}
