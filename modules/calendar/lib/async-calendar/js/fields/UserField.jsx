export default function UserField(props) {
    const editField = () => {
        return (
            <input type="text" id={props.id} value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <span id={props.id}>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
