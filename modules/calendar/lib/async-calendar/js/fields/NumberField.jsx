export default function NumberField(props) {
    const editField = () => {
        return (
            <input type="number" value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
