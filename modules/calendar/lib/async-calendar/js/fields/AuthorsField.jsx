export default function AuthorsField(props) {
    const editField = () => {
        return (
            <input type="text" value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value.join(', ')}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
