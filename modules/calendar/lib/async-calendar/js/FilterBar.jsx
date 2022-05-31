import Select from "./Select";
import TaxonomyField from "./fields/TaxonomyField";

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
        return _n(props.strings.xWeek, props.strings.xWeeks, numberOfWeeks, 'publishpress')
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
                placeholder={props.strings.allStatuses}
                options={props.statuses}
                onSelect={handleStatusChange}
                onClear={handleStatusChange}/>

            <TaxonomyField placeholder={props.strings.allCategories}
                           isEditing={true}
                           ajaxUrl={props.ajaxUrl}
                           nonce={props.nonce}
                           taxonomy={'category'}
                           onSelect={handleCategoriesChange}
                           onClear={handleCategoriesChange}
                           multiple={false}
                           className={'publishpress-calendar-category-filter'}/>

            <TaxonomyField placeholder={props.strings.allTags}
                           isEditing={true}
                           ajaxUrl={props.ajaxUrl}
                           nonce={props.nonce}
                           taxonomy={'post_tag'}
                           onSelect={handleTagsChange}
                           onClear={handleTagsChange}
                           multiple={false}
                           className={'publishpress-calendar-tag-filter'}/>

            <Select
                placeholder={props.strings.allAuthors}
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_authors'}
                onSelect={handleAuthorsChange}
                onClear={handleAuthorsChange}/>

            <Select
                placeholder={props.strings.allTypes}
                options={props.postTypes}
                onSelect={handlePostTypeChange}
                onClear={handlePostTypeChange}/>

            <Select
                placeholder={weeksFilterPlaceholder}
                options={weeksOptions}
                onSelect={handleWeeksChange}
                onClear={handleWeeksChange}/>
        </div>
    )
}
