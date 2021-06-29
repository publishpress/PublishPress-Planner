export default function TimeField(props) {
    const editField = () => {
        return (
            <input type="time" value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
