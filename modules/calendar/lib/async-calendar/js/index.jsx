import AsyncCalendar from "./AsyncCalendar";

jQuery(function () {
    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={new Date(Date.parse(publishpressCalendarParams.firstDateToDisplay))}
            weekStartsOnSunday={publishpressCalendarParams.weekStartsOnSunday}
            numberOfWeeksToDisplay={publishpressCalendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(Date.parse(publishpressCalendarParams.todayDate))}
            timeFormat={publishpressCalendarParams.timeFormat}
            theme={publishpressCalendarParams.theme}
            statusesToDisplayTime={publishpressCalendarParams.statusesToDisplayTime}
            ajaxUrl={publishpressCalendarParams.ajaxUrl}
            actionGetData={'publishpress_calendar_get_data'}
            actionMoveItem={'publishpress_calendar_move_item'}
            nonce={publishpressCalendarParams.nonce}/>,

        document.getElementById('publishpress-calendar-wrap')
    );
});
