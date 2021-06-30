export default function NumberField(props) {
    const editField = () => {
        return (
            <input type="number"
                   value={props.value}
                   id={props.id}
                   onChange={(e) => {
                       if (props.onChange) {
                           props.onChange(e, e.target.value);
                       }
                   }}/>
        )
    }

    const viewField = () => {
        const className = props.value === 0 ? 'publishpress-calendar-empty-value' : '';

        return (
            <span id={props.id} className={className}>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
