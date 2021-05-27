import AsyncCalendar from "./AsyncCalendar";
import FilterBar from "./FilterBar";

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
            nonce={publishpressCalendarParams.nonce}
            statuses={publishpressCalendarParams.statuses}
            postTypes={publishpressCalendarParams.postTypes}/>,
        document.getElementById('publishpress-calendar-wrap')
    );
});
