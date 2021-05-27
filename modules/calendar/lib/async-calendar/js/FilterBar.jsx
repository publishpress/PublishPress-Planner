import Select from "./Select";

const $ = jQuery;
const _n = wp.i18n._n;

export default function FilterBar(props) {
    const dispatchFilterEvent = (filterName, value) => {
        window.dispatchEvent(
            new CustomEvent(
                'PublishpressCalendar:filter',
                {
                    detail: {
                        filter: filterName,
                        value: value
                    }
                }
            )
        );
    }

    const handleStatusChange = (e, element, value) => {
        dispatchFilterEvent('status', value);
    }

    const handleCategoriesChange = (e, element, value) => {
        dispatchFilterEvent('category', value);
    }

    const handleTagsChange = (e, element, value) => {
        dispatchFilterEvent('tag', value);
    }

    const handleAuthorsChange = (e, element, value) => {
        dispatchFilterEvent('author', value);
    }

    const handlePostTypeChange = (e, element, value) => {
        dispatchFilterEvent('postType', value);
    }

    const handleWeeksChange = (e, element, value) => {
        dispatchFilterEvent('weeks', value);
    }

    const getWeeksFilterLabel = (numberOfWeeks) => {
        return _n('%d week', '%d weeks', numberOfWeeks, 'publishpress')
            .replace('%d', numberOfWeeks);
    }

    const weeksFilterPlaceholder = getWeeksFilterLabel(props.numberOfWeeksToDisplay);

    let weeksOptions = [];
    for (let i = 1; i <= 12; i++) {
        weeksOptions.push({
            value: i,
            text: getWeeksFilterLabel(i)
        });
    }

    return (
        <div className="publishpress-calendar-filter-bar">
            <Select
                placeholder={"All statuses"}
                options={props.statuses}
                onSelect={handleStatusChange}
                onClear={handleStatusChange}
            />

            <Select
                placeholder={"All categories"}
                ajaxurl={props.ajaxurl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_categories'}
                onSelect={handleCategoriesChange}
                onClear={handleCategoriesChange}
            />

            <Select
                placeholder={"All tags"}
                ajaxurl={props.ajaxurl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_tags'}
                onSelect={handleTagsChange}
                onClear={handleTagsChange}
            />

            <Select
                placeholder={"All authors"}
                ajaxurl={props.ajaxurl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_authors'}
                onSelect={handleAuthorsChange}
                onClear={handleAuthorsChange}
            />

            <Select
                placeholder={"All types"}
                options={props.postTypes}
                onSelect={handlePostTypeChange}
                onClear={handlePostTypeChange}
            />

            <Select
                placeholder={weeksFilterPlaceholder}
                options={weeksOptions}
                onSelect={handleWeeksChange}
                onClear={handleWeeksChange}
            />
        </div>
    )
}
