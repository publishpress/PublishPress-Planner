export default function PostStatusField(props) {
    const editField = () => {
        return (
            <input type="text" value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
