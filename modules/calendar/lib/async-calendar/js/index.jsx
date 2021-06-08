import AsyncCalendar from "./AsyncCalendar";
import {getDateWithNoTimezoneOffset} from "./Functions";

jQuery(() => {
    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={getDateWithNoTimezoneOffset(publishpressCalendarParams.firstDateToDisplay)}
            weekStartsOnSunday={publishpressCalendarParams.weekStartsOnSunday}
            numberOfWeeksToDisplay={publishpressCalendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(publishpressCalendarParams.todayDate)}
            timeFormat={publishpressCalendarParams.timeFormat}
            theme={publishpressCalendarParams.theme}
            statusesToDisplayTime={publishpressCalendarParams.statusesToDisplayTime}
            maxVisibleItems={publishpressCalendarParams.maxVisibleItems}
            ajaxUrl={publishpressCalendarParams.ajaxUrl}
            actionGetData={'publishpress_calendar_get_data'}
            actionMoveItem={'publishpress_calendar_move_item'}
            nonce={publishpressCalendarParams.nonce}
            statuses={publishpressCalendarParams.statuses}
            postTypes={publishpressCalendarParams.postTypes}
            items={publishpressCalendarParams.items}/>,
        document.getElementById('publishpress-calendar-wrap')
    );
});
