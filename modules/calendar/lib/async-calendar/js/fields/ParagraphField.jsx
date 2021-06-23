export default function ParagraphField(props) {
    const editField = () => {
        return (
            <textarea>{props.value}</textarea>
        )
    }

    const viewField = () => {
        return (
            <div>{props.value}</div>
        );
    }

    return props.isEditing ? editField() : viewField();
}
