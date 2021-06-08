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

    const [state, setState] = React.useState({
        firstDateToDisplay: getBeginDateOfWeekByDate(props.firstDateToDisplay),
        numberOfWeeksToDisplay: props.numberOfWeeksToDisplay,
        itemsByDate: props.items,
        isLoading: false,
        message: null,
        filters: {
            status: null,
            category: null,
            tag: null,
            author: null,
            postType: null,
            weeks: props.numberOfWeeksToDisplay
        },
        openedItemId: null,
        openedItemData: []
    });

    const getFirstDateToDisplay = () => {
        return state.firstDateToDisplay || props.firstDateToDisplay;
    }

    const getUrl = (action, query) => {
        if (!query) {
            query = '';
        }

        return props.ajaxUrl + '?action=' + action + '&nonce=' + props.nonce + query;
    }

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
        addEventListeners();

        return didUnmount;
    }

    const setStateProperty = (stateProperties) => {
        let newState = {...state};

        for (const propertyName in stateProperties) {
            if (!stateProperties.hasOwnProperty(propertyName)) {
                continue;
            }

            newState[propertyName] = stateProperties[propertyName];
        }

        setState(newState);

        return newState;
    }

    window.t = setStateProperty;

    const loadDataByDate = (newDate, filtersOverride) => {
        setStateProperty({
            isLoading: true,
            message: __('Loading...', 'publishpress')
        });

        fetchData(newDate, filtersOverride).then((fetchedData) => {
            setStateProperty({
                firstDateToDisplay: newDate,
                itemsByDate: fetchedData,
                isLoading: false,
                message: null,
            });

            resetCSSClasses();
        });
    };

    const resetCSSClasses = () => {
        $('.publishpress-calendar-day-hover').removeClass('publishpress-calendar-day-hover');
        $('.publishpress-calendar-loading').removeClass('publishpress-calendar-loading');
    };

    const fetchData = async (newDate, filtersOverride) => {
        const numberOfWeeksToDisplayOverride = filtersOverride ? (filtersOverride.weeks || state.numberOfWeeksToDisplay) : state.numberOfWeeksToDisplay;
        const firstDateToDisplay = newDate || state.firstDateToDisplay || props.firstDateToDisplay;

        let dataUrl = getUrl(props.actionGetData, '&start_date=' + getDateAsStringInWpFormat(getBeginDateOfWeekByDate(firstDateToDisplay)) + '&number_of_weeks=' + numberOfWeeksToDisplayOverride);

        const filtersToUse = filtersOverride || state.filters;

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

    const fetchItemData = async (id) => {
        const dataUrl = props.ajaxUrl + '?action=' + 'publishpress_calendar_get_post_data' + '&nonce=' + props.nonce + '&id=' + id;
        const response = await fetch(dataUrl);
        return await response.json();
    }

    const navigateByOffsetInWeeks = (offsetInWeeks) => {
        loadDataByDate(new Date(getFirstDateToDisplay().getTime() + calculateWeeksInMilliseconds(offsetInWeeks)));
    };

    const handleRefreshOnClick = (e) => {
        e.preventDefault();

        loadDataByDate(state.firstDateToDisplay);
    };

    const handleBackPageOnClick = (e) => {
        e.preventDefault();

        navigateByOffsetInWeeks(state.numberOfWeeksToDisplay * -1);
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

        navigateByOffsetInWeeks(state.numberOfWeeksToDisplay);
    };

    const handleTodayOnClick = (e) => {
        e.preventDefault();

        const newDate = getBeginDateOfWeekByDate(props.todayDate, props.weekStartsOnSunday);

        loadDataByDate(newDate);
    };

    const getItemByDateAndIndex = (date, index) => {
        return state.itemsByDate[date][index];
    };

    const moveItemToNewDate = async (itemDate, itemIndex, newYear, newMonth, newDay) => {
        let item = getItemByDateAndIndex(itemDate, itemIndex);

        setStateProperty({
            isLoading: true,
            message: __('Moving the item...', 'publishpress'),
        });

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
            loadDataByDate(state.firstDateToDisplay);
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
        return id === state.openedItemId;
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
        switch (e.detail.filter) {
            case 'status':
            case 'category':
            case 'tag':
            case 'author':
            case 'postType':
            case 'weeks':
                let filters = {...state.filters}

                if (e.detail.value) {
                    filters[e.detail.filter] = e.detail.value[0].id;
                } else {
                    filters[e.detail.filter] = null;
                }

                setStateProperty({filters: filters});

                loadDataByDate(getFirstDateToDisplay(), filters);
                break;
        }
    }

    const resetOpenedItem = () => {
        setStateProperty({
            openedItemId: null,
            openedItemData: null,
        });
    }

    const onClickItem = (e) => {
        setStateProperty({
            openedItemId: e.detail.id,
            openedItemData: null,
        });

        if (itemPopupIsOpenedById(e.detail.id)) {
            return false;
        }

        onRefreshItemPopup(e);
    }

    const onRefreshItemPopup = (e) => {
        fetchItemData(e.detail.id).then(fetchedData => {
            setStateProperty({
                openedItemId: e.detail.id,
                openedItemData: fetchedData
            });
        });
    }

    const onDocumentKeyDown = (e) => {
        if (e.key === 'Escape') {
            resetOpenedItem();
        }
    }

    const getOpenedItemData = () => {
        return state.openedItemData;
    }

    const calendarBodyRows = () => {
        const numberOfDaysToDisplay = state.numberOfWeeksToDisplay * 7;
        const firstDate = getBeginDateOfWeekByDate(getFirstDateToDisplay());

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
                    items={state.itemsByDate[dateString] || []}
                    maxVisibleItems={props.maxVisibleItems}
                    timeFormat={props.timeFormat}
                    openedItemId={state.openedItemId}
                    getOpenedItemDataCallback={getOpenedItemData}
                    ajaxUrl={props.ajaxUrl}/>
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

    return (
        <div className={'publishpress-calendar publishpress-calendar-theme-' + theme}>
            <FilterBar
                statuses={props.statuses}
                postTypes={props.postTypes}
                numberOfWeeksToDisplay={state.numberOfWeeksToDisplay}
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

            <MessageBar showSpinner={state.isLoading} message={state.message}/>
        </div>
    )
}
