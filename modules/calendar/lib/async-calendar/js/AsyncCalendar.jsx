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

    const [firstDateToDisplay, setFirstDateToDisplay] = React.useState(getBeginDateOfWeekByDate(props.firstDateToDisplay));
    const [numberOfWeeksToDisplay, setNumberOfWeeksToDisplay] = React.useState(props.numberOfWeeksToDisplay);
    const [itemsByDate, setItemsByDate] = React.useState(props.items);
    const [isLoading, setIsLoading] = React.useState(false);
    const [message, setMessage] = React.useState();
    const [filterStatus, setFilterStatus] = React.useState();
    const [filterCategory, setFilterCategory] = React.useState();
    const [filterTag, setFilterTag] = React.useState();
    const [filterAuthor, setFilterAuthor] = React.useState();
    const [filterPostType, setFilterPostType] = React.useState();
    const [filterWeeks, setFilterWeeks] = React.useState(props.numberOfWeeksToDisplay);
    const [openedItemId, setOpenedItemId] = React.useState();
    const [openedItemData, setOpenedItemData] = React.useState([]);
    const [openedItemRefreshCount, setOpenedItemRefreshCount] = React.useState(0);
    const [refreshCount, setRefreshCount] = React.useState(0);

    const getUrl = (action, query) => {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    }

    const addEventListeners = () => {
        window.addEventListener('PublishpressCalendar:refreshItemPopup', onRefreshItemPopup);
        document.addEventListener('keydown', onDocumentKeyDown);
    }

    const removeEventListeners = () => {
        window.removeEventListener('PublishpressCalendar:refreshItemPopup', onRefreshItemPopup);
        document.removeEventListener('keydown', onDocumentKeyDown);
    }

    const didUnmount = () => {
        removeEventListeners();
    }

    const didMount = () => {
        addEventListeners();

        return didUnmount;
    }

    const loadData = () => {
        setIsLoading(true);
        setMessage(__('Loading...', 'publishpress'));


        let dataUrl = getUrl(props.actionGetData, '&start_date=' + getDateAsStringInWpFormat(getBeginDateOfWeekByDate(firstDateToDisplay )) + '&number_of_weeks=' + numberOfWeeksToDisplay);

        if (filterStatus) {
            dataUrl += '&post_status=' + filterStatus;
        }

        if (filterCategory) {
            dataUrl += '&category=' + filterCategory;
        }

        if (filterTag) {
            dataUrl += '&post_tag=' + filterTag;
        }

        if (filterAuthor) {
            dataUrl += '&post_author=' + filterAuthor;
        }

        if (filterPostType) {
            dataUrl += '&post_author=' + filterPostType;
        }

        if (filterWeeks) {
            dataUrl += '&weeks=' + filterWeeks;
        }

        fetch(dataUrl)
            .then(response => response.json())
            .then((fetchedData) => {
                setItemsByDate(fetchedData);
                setIsLoading(false);
                setMessage(null);

                resetCSSClasses();
            });
    };

    const resetCSSClasses = () => {
        $('.publishpress-calendar-day-hover').removeClass('publishpress-calendar-day-hover');
        $('.publishpress-calendar-loading').removeClass('publishpress-calendar-loading');
    };

    const loadItemData = () => {
        if (!openedItemId) {
            return;
        }

        setIsLoading(true);
        setMessage(__('Loading item...', 'publishpress'));

        const dataUrl = props.ajaxUrl + '?action=' + 'publishpress_calendar_get_post_data' + '&nonce=' + props.nonce + '&id=' + openedItemId;
        fetch(dataUrl)
            .then(response => response.json())
            .then((data) => {
                setIsLoading(false);
                setMessage(null);

                setOpenedItemData(data);
            });
    }

    const addOffsetInWeeksToFirstDateToDisplay = (offsetInWeeks) => {
        setFirstDateToDisplay(new Date(firstDateToDisplay.getTime() + calculateWeeksInMilliseconds(offsetInWeeks)));
    };

    const handleRefreshOnClick = (e) => {
        e.preventDefault();

        setRefreshCount(refreshCount + 1);
    };

    const handleBackPageOnClick = (e) => {
        e.preventDefault();

        addOffsetInWeeksToFirstDateToDisplay(numberOfWeeksToDisplay * -1);
    };

    const handleBackOnClick = (e) => {
        e.preventDefault();

        addOffsetInWeeksToFirstDateToDisplay(-1);
    };

    const handleForwardOnClick = (e) => {
        e.preventDefault();

        addOffsetInWeeksToFirstDateToDisplay(1);
    }

    const handleForwardPageOnClick = (e) => {
        e.preventDefault();

        addOffsetInWeeksToFirstDateToDisplay(numberOfWeeksToDisplay);
    };

    const handleTodayOnClick = (e) => {
        e.preventDefault();

        setFirstDateToDisplay(getBeginDateOfWeekByDate(props.todayDate, props.weekStartsOnSunday));
    };

    const moveItemToNewDate = async (itemDate, itemIndex, newYear, newMonth, newDay) => {
        let item = itemsByDate[itemDate][itemIndex];

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
            setRefreshCount(refreshCount + 1);
        });
    }

    const handleOnDropItemCallback = (event, ui) => {
        const $dayCell = $(event.target);
        const $item = $(ui.draggable[0]);
        const dateTime = getDateAsStringInWpFormat(new Date($item.data('datetime')));

        $(event.target).addClass('publishpress-calendar-loading');

        moveItemToNewDate(
            dateTime,
            $item.data('index'),
            $dayCell.data('year'),
            $dayCell.data('month'),
            $dayCell.data('day')
        );
    };

    const handleOnHoverCellCallback = (event, ui) => {
        resetCSSClasses();

        $(event.target).addClass('publishpress-calendar-day-hover');
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

    const onFilterEventCallback = (filterName, value) => {
        if ('status' === filterName) {
            setFilterStatus(value);
        }

        if ('category' === filterName) {
            setFilterCategory(value);
        }

        if ('tag' === filterName) {
            setFilterTag(value);
        }

        if ('author' === filterName) {
            setFilterAuthor(value);
        }

        if ('postType' === filterName) {
            setFilterPostType(value);
        }

        if ('weeks' === filterName) {
            value = parseInt(value);
            if (value === 0 || isNaN(value)) {
                value = props.numberOfWeeksToDisplay;
            }

            setFilterWeeks(value);
            setNumberOfWeeksToDisplay(value);

        }
    }

    const resetOpenedItem = () => {
        setOpenedItemId(null);
        setOpenedItemData(null);
    }

    const onClickItem = (id) => {
        setOpenedItemData(null);
        setOpenedItemId(id);
    }

    const onRefreshItemPopup = (e) => {
        setOpenedItemRefreshCount(openedItemRefreshCount + 1);
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
        const numberOfDaysToDisplay = numberOfWeeksToDisplay * 7;
        const firstDate = getBeginDateOfWeekByDate(firstDateToDisplay);

        let tableRows = [];
        let rowCells = [];
        let dayIndexInTheRow = 0;
        let dayDate;
        let dateString;
        let lastMonthDisplayed = firstDate.getMonth();

        for (let dataIndex = 0; dataIndex < numberOfDaysToDisplay; dataIndex++) {
            if (dayIndexInTheRow === 0) {
                rowCells = [];
            }

            dayDate = new Date(firstDate);
            dayDate.setDate(dayDate.getDate() + dataIndex);
            dateString = getDateAsStringInWpFormat(dayDate);

            rowCells.push(
                <CalendarCell
                    key={'day-' + dayDate.getTime()}
                    date={dayDate}
                    shouldDisplayMonthName={lastMonthDisplayed !== dayDate.getMonth() || dataIndex === 0}
                    todayDate={props.todayDate}
                    isLoading={false}
                    items={itemsByDate[dateString] || []}
                    maxVisibleItems={props.maxVisibleItems}
                    timeFormat={props.timeFormat}
                    openedItemId={openedItemId}
                    getOpenedItemDataCallback={getOpenedItemData}
                    ajaxUrl={props.ajaxUrl}
                    onClickItemCallback={onClickItem}/>
            );

            dayIndexInTheRow++;

            if (dayIndexInTheRow === 7) {
                dayIndexInTheRow = 0;
                tableRows.push(
                    <tr>{rowCells}</tr>
                );
            }

            lastMonthDisplayed = dayDate.getMonth();
        }

        return tableRows;
    };

    React.useEffect(didMount, []);
    React.useEffect(initDraggable);
    React.useEffect(
        loadData,
        [
            firstDateToDisplay,
            numberOfWeeksToDisplay,
            filterWeeks,
            filterAuthor,
            filterTag,
            filterCategory,
            filterStatus,
            filterPostType,
            refreshCount
        ]
    );
    React.useEffect(
        loadItemData,
        [
            openedItemId,
            openedItemRefreshCount
        ]
    )

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <FilterBar
                statuses={props.statuses}
                postTypes={props.postTypes}
                numberOfWeeksToDisplay={numberOfWeeksToDisplay}
                ajaxurl={props.ajaxUrl}
                nonce={props.nonce}
                onChange={onFilterEventCallback}/>

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
