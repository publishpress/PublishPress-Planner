export default function DateTimeField(props) {
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
            <time dateTime={props.value}
                  title={props.value}>{props.value}</time>
        );
    }

    return props.isEditing ? editField() : viewField();
}
