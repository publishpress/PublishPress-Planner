import AsyncCalendar from "./AsyncCalendar";
import { getDateWithNoTimezoneOffset, getDateInstanceFromString, addCalendarPosts } from "./Functions";
import { createRoot } from "&wp.element";
import { render } from "&ReactDOM";

jQuery(() => {
    const container = document.getElementById('publishpress-calendar-wrap');

    publishpressCalendarParams.PostData = addCalendarPosts([], publishpressCalendarParams.items);

    const component = (
        <AsyncCalendar
                firstDateToDisplay={getDateWithNoTimezoneOffset(publishpressCalendarParams.firstDateToDisplay)}
                weekStartsOnSunday={parseInt(publishpressCalendarParams.weekStartsOnSunday) === 1}
                numberOfWeeksToDisplay={publishpressCalendarParams.numberOfWeeksToDisplay}
                todayDate={getDateInstanceFromString(publishpressCalendarParams.todayDate)}
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
                allowAddingMultipleAuthors={publishpressCalendarParams.allowAddingMultipleAuthors}
                requestFilter={publishpressCalendarParams.requestFilter}
                strings={publishpressCalendarParams.strings} />
    );

    if (createRoot) {
        createRoot(container).render(component);
    } else {
        render(component, container);
    }
});
