import Select from "../Select";

const __ = wp.i18n.__;

export default function TaxonomyField(props) {
    const editField = () => {
        return (
            <Select
                placeholder={props.placeholder}
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_terms'}
                ajaxArgs={{taxonomy: props.taxonomy}}
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
                <span className="publishpress-calendar-empty-value">{__('No terms', 'publishpress')}</span>
            );
        }

        return (
            <span>{props.value.join(', ')}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
