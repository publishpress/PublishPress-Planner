import AsyncCalendar from "./AsyncCalendar";

jQuery(function () {
    const calendarParams = pp_calendar_params.calendarParams;
    const dataURL = pp_calendar_params.calendarParams.ajaxUrl + '?action=publishpress_calendar_get_data&nonce=' + pp_calendar_params.calendarParams.nonce;

    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={new Date(Date.parse(calendarParams.firstDateToDisplay))}
            weekStartsOnSunday={calendarParams.weekStartsOnSunday}
            numberOfWeeksToDisplay={calendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(Date.parse(calendarParams.todayDate))}
            timezoneOffset={calendarParams.timezoneOffset}
            timeFormat={calendarParams.timeFormat}
            theme={calendarParams.theme}
            dataUrl={dataURL}/>,

        document.getElementById('publishpress-calendar-wrap')
    );
});
