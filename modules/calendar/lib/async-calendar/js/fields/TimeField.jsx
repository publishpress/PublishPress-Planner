export default function TimeField(props) {
    const editField = () => {
        return (
            <input type="text"
                   id={props.id}
                   placeholder={props.placeholder || null}
                   value={props.value}/>
        )
    };

    const viewField = () => {
        return (
            <span id={props.id}>{props.value}</span>
        );
    };

    const initInputMask = () => {
        const selector = '#' + props.id;

        let args = {
            regex: "^([0][0-9]|[1][0-9]|[2][0-3]):[0-5][0-9]$",
            showMaskOnHover: false,
            staticDefinitionSymbol: "*"
        };

        if (props.placeholder) {
            args.placeholder = props.placeholder;
        }

        jQuery(selector).inputmask(args);
        jQuery(selector).on('change', (e) => {
            if (props.onChange) {
                props.onChange(e, e.target.value);
            }
        });
    };

    React.useEffect(initInputMask);

    return props.isEditing ? editField() : viewField();
}
