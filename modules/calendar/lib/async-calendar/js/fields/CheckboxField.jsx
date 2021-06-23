export default function CheckboxField(props) {
    const editField = () => {
        return (
            <input type="checkbox" value="1" checked={props.value === 'Yes'}/>
        )
    };

    const viewField = () => {
        let icon;

        if (props.value === 'Yes') {
            icon = <span className="dashicons dashicons-yes-alt"/>;
        } else {
            icon = <span className="dashicons dashicons-no-alt"/>;
        }

        return icon;
    };

    return props.isEditing ? editField() : viewField();
}
