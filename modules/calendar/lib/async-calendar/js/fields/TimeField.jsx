export default function TimeField (props) {
    const editField = () => {
        return (
            <input type="text" value={props.value}/>
        )
    }

    const viewField = () => {
        return (
            <time dateTime={props.value}
                  title={props.value}>{props.value}</time>
        );
    }

    return props.isEditing ? editField() : viewField();
}
