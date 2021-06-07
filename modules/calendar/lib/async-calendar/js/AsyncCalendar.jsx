import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import MessageBar from "./MessageBar";
import CalendarCell from "./CalendarCell";
import {calculateWeeksInMilliseconds, getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from "./Functions";
import FilterBar from "./FilterBar";

const {__} = wp.i18n;
const $ = jQuery;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [firstDateToDisplay, setFirstDateToDisplay] = React.useState(props.firstDateToDisplay);
    const [numberOfWeeksToDisplay, setNumberOfWeeksToDisplay] = React.useState(props.numberOfWeeksToDisplay);
    const [cells, setCells] = React.useState({});
    const [isLoading, setIsLoading] = React.useState(false);
    const [message, setMessage] = React.useState();
    const [filters, setFilters] = React.useState({
        status: null,
        category: null,
        tag: null,
        author: null,
        postType: null,
        weeks: null
    });
    const [openedItemId, setOpenedItemId] = React.useState();
    const [openedItemData, setOpenedItemData] = React.useState([]);

    let $lastHoveredCell;

    const getUrl = (action, query) => {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    };

    const addEventListeners = () => {
        window.addEventListener('PublishpressCalendar:filter', onFilterEventCallback);
        window.addEventListener('PublishpressCalendar:clickItem', onClickItem);
        window.addEventListener('PublishpressCalendar:refreshItemPopup', onRefreshItemPopup);
        document.addEventListener('keydown', onDocumentKeyDown);
    }

    const removeEventListeners = () => {
        window.removeEventListener('PublishpressCalendar:filter', onFilterEventCallback);
        window.removeEventListener('PublishpressCalendar:clickItem', onClickItem);
        window.removeEventListener('PublishpressCalendar:refreshItemPopup', onRefreshItemPopup);
        document.removeEventListener('keydown', onDocumentKeyDown);
    }

    const didUnmount = () => {
        removeEventListeners();
    }

    const didMount = () => {
        prepareDataByDate();
        addEventListeners();

        return didUnmount;
    };

    const prepareDataByDate = (newDate, filtersOverride) => {
        const numberOfWeeksToDisplayOverride = filtersOverride ? (filtersOverride.weeks || numberOfWeeksToDisplay) : numberOfWeeksToDisplay;

        const numberOfDaysToDisplay = numberOfWeeksToDisplayOverride * 7;
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

            resetCSSClasses();
        });
    };

    const resetCSSClasses = () => {
        $('.publishpress-calendar-day-hover').removeClass('publishpress-calendar-day-hover');
        $('.publishpress-calendar-loading').removeClass('publishpress-calendar-loading');
    };

    const fetchData = async (filtersOverride) => {
        const numberOfWeeksToDisplayOverride = filtersOverride ? (filtersOverride.weeks || numberOfWeeksToDisplay) : numberOfWeeksToDisplay;

        let dataUrl = getUrl(props.actionGetData, '&start_date=' + getDateAsStringInWpFormat(props.firstDateToDisplay) + '&number_of_weeks=' + numberOfWeeksToDisplayOverride);

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

            if (filtersToUse.weeks) {
                setNumberOfWeeksToDisplay(filtersToUse.weeks);
            }
        }

        const response = await fetch(dataUrl);
        return await response.json();
    }

    const fetchItemData = async (id) => {
        const dataUrl = props.ajaxUrl + '?action=' + 'publishpress_calendar_get_post_data' + '&nonce=' + props.nonce + '&id=' + id;
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

        navigateByOffsetInWeeks(numberOfWeeksToDisplay * -1);
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

        navigateByOffsetInWeeks(numberOfWeeksToDisplay);
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

    const itemPopupIsOpenedById = (id) => {
        return id === openedItemId;
    }

    const initDraggable = () => {
        $('.publishpress-calendar-day-items li').draggable({
            zIndex: 99999,
            helper: 'clone',
            containment: '.publishpress-calendar table',
            start: (event, ui) => {
                // Do not drag the item if the popup is opened.
                if (itemPopupIsOpenedById($(event.target).data('id'))) {
                    return false;
                }

                $(event.target).addClass('ui-draggable-target');

                resetOpenedItem();
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
            case 'weeks':
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

    const resetOpenedItem = () => {
        setOpenedItemId(null);
        setOpenedItemData(null);
    }

    const onClickItem = (e) => {
        setOpenedItemId(e.detail.id);
        setOpenedItemData(null);

        if (itemPopupIsOpenedById(e.detail.id)) {
            return false;
        }

        fetchItemData(e.detail.id).then(fetchedData => {
            setOpenedItemData(fetchedData);
        });
    }

    const onRefreshItemPopup = (e) => {
        fetchItemData(e.detail.id).then(fetchedData => {
            setOpenedItemData(fetchedData);
        });
    }

    const onDocumentKeyDown = (e) => {
        if (e.key === 'Escape') {
            resetOpenedItem();
        }
    }

    const getOpenedItemData = () => {
        return openedItemData;
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
                    timeFormat={props.timeFormat}
                    openedItemId={openedItemId}
                    getOpenedItemDataCallback={getOpenedItemData}
                    ajaxUrl={props.ajaxUrl}/>
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
            <FilterBar
                statuses={props.statuses}
                postTypes={props.postTypes}
                numberOfWeeksToDisplay={props.numberOfWeeksToDisplay}
                ajaxurl={props.ajaxUrl}
                nonce={props.nonce}
            />

            <NavigationBar
                refreshOnClickCallback={handleRefreshOnClick}
                backPageOnClickCallback={handleBackPageOnClick}
                backOnClickCallback={handleBackOnClick}
                forwardOnClickCallback={handleForwardOnClick}
                forwardPageOnClickCallback={handleForwardPageOnClick}
                todayOnClickCallback={handleTodayOnClick}/>

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
            <MessageBar showSpinner={isLoading} message={message}/>
        </div>
    )
}
