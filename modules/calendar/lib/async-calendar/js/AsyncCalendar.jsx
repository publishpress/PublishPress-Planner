import NavigationBar from "./NavigationBar";
import WeekDays from "./WeekDays";
import MessageBar from "./MessageBar";
import DayCell from "./DayCell";
import {calculateWeeksInMilliseconds, getBeginDateOfWeekByDate, getDateAsStringInWpFormat} from "./Functions";
import FilterBar from "./FilterBar";
import ItemFormPopup from "./ItemFormPopup";

const {__} = wp.i18n;
const $ = jQuery;

export default function AsyncCalendar(props) {
    const theme = (props.theme || 'light');

    const [firstDateToDisplay, setFirstDateToDisplay] = React.useState(getBeginDateOfWeekByDate(props.firstDateToDisplay, props.weekStartsOnSunday));
    const [numberOfWeeksToDisplay, setNumberOfWeeksToDisplay] = React.useState(props.numberOfWeeksToDisplay);
    const [itemsByDate, setItemsByDate] = React.useState(props.items);
    const [isLoading, setIsLoading] = React.useState(false);
    const [isDragging, setIsDragging] = React.useState(false);
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
    const [hoveredDate, setHoveredDate] = React.useState();
    const [formDate, setFormDate] = React.useState();

    const DRAG_AND_DROP_HOVERING_CLASS = 'publishpress-calendar-day-hover';

    const getUrl = (action, query) => {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    }

    const addEventListeners = () => {
        document.addEventListener('keydown', onDocumentKeyDown);
        $(document).on('publishpress_calendar:close_popup', onCloseItemPopup);
    }

    const removeEventListeners = () => {
        document.removeEventListener('keydown', onDocumentKeyDown);
        $('.publishpress-calendar tbody > tr > td').off('mouseenter');
    }

    const didMount = () => {
        addEventListeners();

        if (props.userCanAddPosts) {
            initClickToCreateFeature();
        }

        return didUnmount;
    }

    const didUnmount = () => {
        removeEventListeners();
    }

    const fetchCalendarData = () => {
        setIsLoading(true);
        setMessage(__('Loading...', 'publishpress'));

        let dataUrl = getUrl(props.actionGetData, '&start_date=' + getDateAsStringInWpFormat(getBeginDateOfWeekByDate(firstDateToDisplay, props.weekStartsOnSunday)) + '&number_of_weeks=' + numberOfWeeksToDisplay);

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
        $('.' + DRAG_AND_DROP_HOVERING_CLASS).removeClass(DRAG_AND_DROP_HOVERING_CLASS);
        $('.publishpress-calendar-loading').removeClass('publishpress-calendar-loading');
    };

    const fetchCalendarItemData = () => {
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

    const moveCalendarItemToANewDate = (itemDate, itemIndex, newYear, newMonth, newDay) => {
        let item = itemsByDate[itemDate][itemIndex];

        setIsLoading(true);
        setMessage(__('Moving the item...', 'publishpress'));

        const dataUrl = getUrl(props.actionMoveItem);

        const formData = new FormData();
        formData.append('id', item.id);
        formData.append('year', newYear);
        formData.append('month', newMonth);
        formData.append('day', newDay);

        fetch(dataUrl, {method: 'POST', body: formData})
            .then(response => response.json())
            .then(() => {
                setRefreshCount(refreshCount + 1);
            });
    }

    const handleOnDropItemCallback = (event, ui) => {
        const $dayCell = $(event.target);
        const $item = $(ui.draggable[0]);
        const dateTime = getDateAsStringInWpFormat(new Date($item.data('datetime')));

        $(event.target).addClass('publishpress-calendar-loading');

        moveCalendarItemToANewDate(
            dateTime,
            $item.data('index'),
            $dayCell.data('year'),
            $dayCell.data('month'),
            $dayCell.data('day')
        );
    };

    const handleOnHoverCellCallback = (event, ui) => {
        resetCSSClasses();

        $(event.target).addClass(DRAG_AND_DROP_HOVERING_CLASS);
    };

    const itemPopupIsOpenedById = (id) => {
        return id === openedItemId;
    }

    const initDraggableAndDroppableBehaviors = () => {
        $('.publishpress-calendar-day-items li').draggable({
            zIndex: 99999,
            helper: 'clone',
            containment: '.publishpress-calendar table',
            start: (event, ui) => {
                // Do not drag the item if the popup is opened.
                if (itemPopupIsOpenedById($(event.target).data('id'))) {
                    return false;
                }

                if (!$(event.target).hasClass('publishpress-calendar-item-movable')) {
                    return false;
                }

                $(event.target).addClass('ui-draggable-target');

                resetOpenedItemInPopup();

                setIsDragging(true);
            },
            stop: (event, ui) => {
                $('.ui-draggable-target').removeClass('ui-draggable-target');

                setIsDragging(false);
            }
        });

        $('.publishpress-calendar tbody > tr > td').droppable({
            drop: handleOnDropItemCallback,
            over: handleOnHoverCellCallback
        });
    };

    const getCellFromChild = (child) => {
        let $child = $(child);

        if ($child.is('td.publishpress-calendar-business-day, td.publishpress-calendar-weekend-day')) {
            return $child;
        }

        if ($child.is('.publishpress-calendar-cell-header, .publishpress-calendar-date, .publishpress-calendar-cell-click-to-add, .publishpress-calendar-month-name')) {
            return $child.parents('td');
        }

        return null;
    }

    const getCellDate = (cell) => {
        let date = new Date(cell.data('year') + '-' + cell.data('month') + '-' + cell.data('day'));

        // Compensate the timezone for returning the correct date
        if (date.getHours() > 0) {
            date.setTime(date.getTime() + (60*1000*date.getTimezoneOffset()));
        }

        return date;
    }

    const isHoveringCellWhileDragging = (cell) => {
        return $(cell).hasClass(DRAG_AND_DROP_HOVERING_CLASS);
    }

    const initClickToCreateFeature = () => {
        // We have to use this variable because when the click is done on the "click to add" label, we can't get its parent.
        // Probably because it was already removed from the DOM.
        let lastHoveredDate;
        $('.publishpress-calendar tbody > tr > td')
            .on('mouseover', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const cell = getCellFromChild(e.target);

                if (cell) {
                    if (isHoveringCellWhileDragging(cell)) {
                        return;
                    }

                    setHoveredDate(getCellDate(cell));
                    lastHoveredDate = getCellDate(cell);
                }
            })
            .on('mouseout', (e) => {
                e.stopPropagation();
                e.preventDefault();

                if (getCellFromChild(e.relatedTarget)) {
                    return;
                }

                setHoveredDate(null);
                lastHoveredDate = null;
            })
            .on('click', (e) => {
                const cell = getCellFromChild(e.target);

                if (cell) {
                    setOpenedItemId(null);
                    setFormDate(lastHoveredDate);
                }
            });
    }

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

    const resetOpenedItemInPopup = () => {
        setOpenedItemId(null);
        setOpenedItemData(null);
        setFormDate(null);
    }

    const onClickItem = (id) => {
        setOpenedItemData(null);
        setHoveredDate(null);
        setFormDate(null);
        setOpenedItemId(id);
    }

    const onPopupItemActionClick = (action, id, result) => {
        setOpenedItemRefreshCount(openedItemRefreshCount + 1);
    }

    const onDocumentKeyDown = (e) => {
        if (e.key === 'Escape') {
            resetOpenedItemInPopup();
        }
    }

    const getOpenedItemData = () => {
        return openedItemData;
    }

    const onCloseForm = () => {
        setRefreshCount(refreshCount + 1);

        setFormDate(null);
    }

    const onCloseItemPopup = () => {
        setOpenedItemId(null);
    }

    const calendarTableBodyRowsWithCells = () => {
        const numberOfDaysToDisplay = numberOfWeeksToDisplay * 7;
        const firstDate = getBeginDateOfWeekByDate(firstDateToDisplay, props.weekStartsOnSunday);

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
                <DayCell
                    key={'day-' + dayDate.getTime()}
                    date={dayDate}
                    shouldDisplayMonthName={lastMonthDisplayed !== dayDate.getMonth() || dataIndex === 0}
                    todayDate={props.todayDate}
                    isLoading={false}
                    isHovering={!isDragging && hoveredDate && hoveredDate.getTime() === dayDate.getTime() && !formDate}
                    items={itemsByDate[dateString] || []}
                    maxVisibleItems={parseInt(props.maxVisibleItems)}
                    timeFormat={props.timeFormat}
                    openedItemId={openedItemId}
                    getOpenedItemDataCallback={getOpenedItemData}
                    ajaxUrl={props.ajaxUrl}
                    onClickItemCallback={onClickItem}
                    onItemActionClickCallback={onPopupItemActionClick}/>
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
    React.useEffect(initDraggableAndDroppableBehaviors);

    if (props.userCanAddPosts) {
        React.useEffect(
            initClickToCreateFeature,
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
    }

    React.useEffect(
        fetchCalendarData,
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
        fetchCalendarItemData,
        [
            openedItemId,
            openedItemRefreshCount
        ]
    )

    let componentClassName = [
        'publishpress-calendar',
        'publishpress-calendar-theme-' + theme,
    ];

    if (props.userCanAddPosts) {
        componentClassName.push('user-can-add-posts');
    }

    return (
        <div className={componentClassName.join(' ')}>
            <FilterBar
                statuses={props.statuses}
                postTypes={props.postTypes}
                numberOfWeeksToDisplay={numberOfWeeksToDisplay}
                ajaxUrl={props.ajaxUrl}
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
                {calendarTableBodyRowsWithCells()}
                </tbody>
            </table>

            {formDate &&
                <ItemFormPopup
                    date={formDate}
                    ajaxUrl={props.ajaxUrl}
                    dateFormat={props.dateFormat}
                    postTypes={props.postTypesCanCreate}
                    statuses={props.statuses}
                    actionGetPostTypeFields={props.actionGetPostTypeFields}
                    nonce={props.nonce}
                    onCloseCallback={onCloseForm}
                    allowAddingMultipleAuthors={props.allowAddingMultipleAuthors}/>
            }

            <MessageBar showSpinner={isLoading} message={message}/>
        </div>
    )
}
