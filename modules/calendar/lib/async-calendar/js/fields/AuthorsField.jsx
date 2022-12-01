import Select from "../Select";

export default function AuthorsField(props) {
    const editField = () => {
        return (
            <Select
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                multiple={props.multiple}
                ajaxAction={'publishpress_calendar_search_authors'}
                ajaxArgs={props.ajaxArgs}
                value={props.value}
                metadata={props.metadata}
                post_types={props.post_types}
                className={props.metadata ? 'pp-calendar-form-metafied-input' : ''}
                name={props.name}
                id={props.id}
                onSelect={props.onSelect}
                onClear={props.onClear}/>
        )
    }

    const viewField = () => {
        return (
            <span id={props.id}>{props.value.join(', ')}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
