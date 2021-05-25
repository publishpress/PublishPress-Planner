import AsyncCalendar from "./AsyncCalendar";
import Filters from "./Filters";

jQuery(() => {
    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={new Date(Date.parse(publishpressCalendarParams.firstDateToDisplay))}
            weekStartsOnSunday={publishpressCalendarParams.weekStartsOnSunday}
            numberOfWeeksToDisplay={publishpressCalendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(Date.parse(publishpressCalendarParams.todayDate))}
            timeFormat={publishpressCalendarParams.timeFormat}
            theme={publishpressCalendarParams.theme}
            statusesToDisplayTime={publishpressCalendarParams.statusesToDisplayTime}
            maxVisibleItems={publishpressCalendarParams.maxVisibleItems}
            ajaxUrl={publishpressCalendarParams.ajaxUrl}
            actionGetData={'publishpress_calendar_get_data'}
            actionMoveItem={'publishpress_calendar_move_item'}
            nonce={publishpressCalendarParams.nonce}/>,
        document.getElementById('publishpress-calendar-wrap')
    );

    ReactDOM.render(
        <Filters
            statuses={publishpressCalendarParams.statuses}
            postTypes={publishpressCalendarParams.postTypes}
            ajaxurl={publishpressCalendarParams.ajaxUrl}
            nonce={publishpressCalendarParams.nonce}
        />,
        document.getElementById('publishpress-calendar-filters-wrap')
    );
});
