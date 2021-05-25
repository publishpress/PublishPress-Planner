const $ = jQuery;

export default function Select(props) {
    const selectRef = React.useRef(null);

    const initSelect2 = () => {
        let params = {
            placeholder: props.placeholder || false,
            tags: true,
            allowClear: true
        };

        if (props.ajaxurl) {
            params.ajax = {
                delay: 250,
                url: props.ajaxurl,
                dataType: 'json',
                data: function (params) {
                    return {
                        q: params.term,
                        action: props.ajaxAction,
                        nonce: props.nonce,
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                }
            };
        }

        $(selectRef.current).pp_select2(params)
            .on('select2:select', (e) => {
                props.onSelect(
                    e,
                    selectRef.current,
                    $(selectRef.current).pp_select2('data')
                );
            })
            .on('select2:clear', (e) => {
                props.onClear(
                    e,
                    selectRef.current
                );
            });

        return () => {
            $(selectRef.current).pp_select2('destroy');
        }
    };

    const blankOption = () => {
        if (props.placeholder) {
            return <option value="">{props.placeholder}</option>
        }

        return <></>;
    };

    React.useEffect(initSelect2, []);

    let options;

    if (props.options) {
        options = props.options.map((option) => {
            return <option value={option.value}>{option.text}</option>
        });
    }

    return (
        <select className="pp_select2"
                multiple={props.multiple}
                ref={selectRef}>
            {blankOption()}
            {options}
        </select>
    )
}
