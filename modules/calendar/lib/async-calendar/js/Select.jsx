const $ = jQuery;

export default function Select(props) {
    const selectRef = React.useRef(null);

    const initSelect2 = () => {
        $(selectRef.current).pp_select2({
            placeholder: props.blankOptionText || false,
            tags: true,
            allowClear: true
        })
            .on('select2:select', (e) => {
                props.onSelect(e, selectRef.current, $(selectRef.current).pp_select2('data'));
            })
            .on('select2:clear', (e) => {
                props.onClear(e, selectRef.current);
            });

        return () => {
            $(selectRef.current).pp_select2('destroy');
        }
    };

    const blankOption = () => {
        if (props.blankOptionText) {
            return <option value="">{props.blankOptionText}</option>
        }

        return <></>;
    };

    React.useEffect(initSelect2, []);

    return (
        <select className="pp_select2"
                multiple={props.multiple}
                ref={selectRef}>
            {blankOption()}
            {props.options.map((option) => {
                return <option value={option.value}>{option.text}</option>
            })}
        </select>
    )
}
