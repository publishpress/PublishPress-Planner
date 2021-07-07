import Select from "./Select";
import TaxonomyField from "./fields/TaxonomyField";

const __ = wp.i18n.__;
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
                placeholder={__('All statuses', 'publishpress')}
                options={props.statuses}
                onSelect={handleStatusChange}
                onClear={handleStatusChange}/>

            <TaxonomyField placeholder={__('All categories', 'publishpress')}
                           isEditing={true}
                           ajaxUrl={props.ajaxUrl}
                           nonce={props.nonce}
                           taxonomy={'category'}
                           onSelect={handleCategoriesChange}
                           onClear={handleCategoriesChange}
                           multiple={false}
                           className={'publishpress-calendar-category-filter'}/>

            <TaxonomyField placeholder={__('All tags', 'publishpress')}
                           isEditing={true}
                           ajaxUrl={props.ajaxUrl}
                           nonce={props.nonce}
                           taxonomy={'post_tag'}
                           onSelect={handleTagsChange}
                           onClear={handleTagsChange}
                           multiple={false}
                           className={'publishpress-calendar-tag-filter'}/>

            <Select
                placeholder={__('All authors', 'publishpress')}
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_authors'}
                onSelect={handleAuthorsChange}
                onClear={handleAuthorsChange}/>

            <Select
                placeholder={__('All types', 'publishpress')}
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
