import Select from "../Select";

export default function PostStatusField(props) {
    const editField = () => {
        return (
            <Select options={props.options}
                    value={props.value}
                    allowClear={props.allowClear}
                    onSelect={props.onSelect}
                    onClear={props.onClear}/>
        )
    }

    const viewField = () => {
        return (
            <span>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
