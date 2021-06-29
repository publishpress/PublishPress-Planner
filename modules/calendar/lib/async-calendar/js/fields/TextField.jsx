export default function TextField(props) {
    const editField = () => {
        return (
            <input type="text" value={props.value} onChange={(e) => {
                if (props.onChange) {
                    props.onChange(e, e.target.value);
                }
            }}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
