export default function TextArea(props) {
    let wrapper_class = props.metadata ? 'pp-calendar-form-metafied-input pp-calendar-form-metafied ' + props.post_types : '';
    const editField = () => {
        return (
            <div className={wrapper_class}>
                <textarea
                    id={props.id}
                    metadata={props.metadata}
                    post_types={props.post_types}
                    name={props.name}
                    className={wrapper_class}
                    onChange={(e) => {
                        if (props.onChange) {
                            props.onChange(e, e.target.value);
                        }
                    }}>{props.value}</textarea>
            </div>
        )
    }

    const viewField = () => {
        return (
            <div id={props.id}>{props.value}</div>
        );
    }

    return props.isEditing ? editField() : viewField();
}
