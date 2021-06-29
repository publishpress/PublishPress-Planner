export default function TextArea(props) {
    const editField = () => {
        return (
            <textarea onChange={(e) => {
                if (props.onChange) {
                    props.onChange(e, e.target.value);
                }
            }}>{props.value}</textarea>
        )
    }

    const viewField = () => {
        return (
            <div>{props.value}</div>
        );
    }

    return props.isEditing ? editField() : viewField();
}
