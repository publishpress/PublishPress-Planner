import AsyncCalendar from "./AsyncCalendar";

jQuery(function () {
    const calendarParams = pp_calendar_params.calendarParams;

    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={new Date(Date.parse(calendarParams.firstDateToDisplay))}
            weekStartsOnSunday={calendarParams.weekStartsOnSunday}
            numberOfWeeksToDisplay={calendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(Date.parse(calendarParams.todayDate))}
            timezoneOffset={calendarParams.timezoneOffset}
            timeFormat={calendarParams.timeFormat}
            theme={calendarParams.theme}
            getDataCallback={publishpressAsyncCalendarGetData}/>,

        document.getElementById('publishpress-calendar-wrap')
    );
});

function publishpressAsyncCalendarGetData(numberOfWeeksToDisplay, firstDateToDisplay, calendarInstance) {
    fetch(pp_calendar_params.calendarParams.ajaxUrl + '?action=publishpress_calendar_get_data&nonce=' + pp_calendar_params.calendarParams.nonce)
        .then(res => res.json())
        .then(
            (result) => {
                console.log(result);
                calendarInstance.setState({
                    isLoaded: true,
                    items: result
                }),
                    (error) => {
                        calendarInstance.setState({
                            isLoaded: true,
                            error: true
                        });
                    }
            }
        );
}
