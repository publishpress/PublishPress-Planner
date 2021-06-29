import Select from "./Select";

const _n = wp.i18n._n;

export default function FilterBar(props) {
    const handleFilterChange = (filterName, value) => {
        const filterValue = value ? value[0].id : null;

        props.onChange(filterName, filterValue);
    }

    const handleStatusChange = (e, element, value) => {
        handleFilterChange('status', value);
    }

    const handleCategoriesChange = (e, element, value) => {
        handleFilterChange('category', value);
    }

    const handleTagsChange = (e, element, value) => {
        handleFilterChange('tag', value);
    }

    const handleAuthorsChange = (e, element, value) => {
        handleFilterChange('author', value);
    }

    const handlePostTypeChange = (e, element, value) => {
        handleFilterChange('postType', value);
    }

    const handleWeeksChange = (e, element, value) => {
        handleFilterChange('weeks', value);
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
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_categories'}
                onSelect={handleCategoriesChange}
                onClear={handleCategoriesChange}
            />

            <Select
                placeholder={"All tags"}
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_tags'}
                onSelect={handleTagsChange}
                onClear={handleTagsChange}
            />

            <Select
                placeholder={"All authors"}
                ajaxUrl={props.ajaxUrl}
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
