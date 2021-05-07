import AsyncCalendar from "./AsyncCalendar";

jQuery(function () {
    const dataURL = publishpressCalendarParams.ajaxUrl + '?action=publishpress_calendar_get_data&nonce=' + publishpressCalendarParams.nonce;

    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={new Date(Date.parse(publishpressCalendarParams.firstDateToDisplay))}
            weekStartsOnSunday={publishpressCalendarParams.weekStartsOnSunday}
            numberOfWeeksToDisplay={publishpressCalendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(Date.parse(publishpressCalendarParams.todayDate))}
            timezoneOffset={publishpressCalendarParams.timezoneOffset}
            timeFormat={publishpressCalendarParams.timeFormat}
            theme={publishpressCalendarParams.theme}
            dataUrl={dataURL}/>,

        document.getElementById('publishpress-calendar-wrap')
    );
});
