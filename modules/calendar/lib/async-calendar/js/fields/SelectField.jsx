import Select from "../Select";

export default function SelectField(props) {
    const editField = () => {
        return (
            <Select
                ajaxUrl={props.ajaxUrl}
                nonce={props.nonce}
                multiple={props.multiple}
                ajaxAction={props.ajaxAction}
                ajaxArgs={props.ajaxArgs}
                options={props.options}
                value={props.value}
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
