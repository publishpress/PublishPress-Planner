export default function TextArea(props) {
    const editField = () => {
        return (
            <textarea
                id={props.id}
                onChange={(e) => {
                    if (props.onChange) {
                        props.onChange(e, e.target.value);
                    }
                }}>{props.value}</textarea>
        )
    }

    const viewField = () => {
        return (
            <div id={props.id}>{props.value}</div>
        );
    }

    return props.isEditing ? editField() : viewField();
}
