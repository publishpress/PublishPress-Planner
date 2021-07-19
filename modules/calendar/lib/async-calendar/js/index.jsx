import AsyncCalendar from "./AsyncCalendar";
import {getDateWithNoTimezoneOffset} from "./Functions";

jQuery(() => {
    ReactDOM.render(
        <AsyncCalendar
            firstDateToDisplay={getDateWithNoTimezoneOffset(publishpressCalendarParams.firstDateToDisplay)}
            weekStartsOnSunday={parseInt(publishpressCalendarParams.weekStartsOnSunday) === 1}
            numberOfWeeksToDisplay={publishpressCalendarParams.numberOfWeeksToDisplay}
            todayDate={new Date(publishpressCalendarParams.todayDate)}
            dateFormat={publishpressCalendarParams.dateFormat}
            timeFormat={publishpressCalendarParams.timeFormat}
            theme={publishpressCalendarParams.theme}
            statusesToDisplayTime={publishpressCalendarParams.statusesToDisplayTime}
            maxVisibleItems={publishpressCalendarParams.maxVisibleItems}
            ajaxUrl={publishpressCalendarParams.ajaxUrl}
            actionGetData={'publishpress_calendar_get_data'}
            actionMoveItem={'publishpress_calendar_move_item'}
            actionGetPostTypeFields={'publishpress_calendar_get_post_type_fields'}
            nonce={publishpressCalendarParams.nonce}
            statuses={publishpressCalendarParams.statuses}
            postTypes={publishpressCalendarParams.postTypes}
            postTypesCanCreate={publishpressCalendarParams.postTypesCanCreate}
            userCanAddPosts={publishpressCalendarParams.userCanAddPosts}
            items={publishpressCalendarParams.items}
            allowAddingMultipleAuthors={publishpressCalendarParams.allowAddingMultipleAuthors}/>,
        document.getElementById('publishpress-calendar-wrap')
    );
});
