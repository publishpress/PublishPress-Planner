import Select from "../Select";

export default function TaxonomyField(props) {
    const editField = () => {
        return (
            <Select
                placeholder={props.placeholder}
                id={props.id}
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_terms'}
                ajaxArgs={{taxonomy: props.taxonomy}}
                options={props.options}
                value={props.value}
                multiple={props.multiple}
                onSelect={props.onSelect}
                onClear={props.onClear}
                className={props.className}/>
        )
    }

    const viewField = () => {
        if (typeof props.value === 'undefined' || props.value.length === 0) {
            return (
                <span id={props.id}
                      className="publishpress-calendar-empty-value">{publishpressCalendarParams.strings.noTerms}</span>
            );
        }

        return (
            <span id={props.id}>{props.value.join(', ')}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
