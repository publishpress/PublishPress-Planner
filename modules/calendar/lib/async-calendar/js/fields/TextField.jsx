export default function TextField(props) {
    const editField = () => {
        return (
            <input type="text"
                   id={props.id}
                   value={props.value}
                   onChange={(e) => {
                       if (props.onChange) {
                           props.onChange(e, e.target.value);
                       }
                   }}/>
        )
    }

    const viewField = () => {
        return (
            <span id={props.id}>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
