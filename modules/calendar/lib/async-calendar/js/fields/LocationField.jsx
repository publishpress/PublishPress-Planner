export default function LocationField(props) {
    const editField = () => {
        return (
            <input type="location" value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
