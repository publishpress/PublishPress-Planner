import Select from "./Select";

const $ = jQuery;

export default function Filters(props) {
    const statusesOptions = props.statuses.map((item) => {
        return {
            value: item.slug,
            text: item.name
        };
    });

    const handleStatusChange = (e, element, value) => {
        window.dispatchEvent(
            new CustomEvent(
                'PublishpressCalendar:filter',
                {
                    detail: {
                        filter: 'status',
                        value: $(element).pp_select2('data')
                    }
                }
            )
        );
    }

    return (
        <>
            <Select
                blankOptionText={"All statuses"}
                options={statusesOptions}
                onSelect={handleStatusChange}
                onClear={handleStatusChange}
                />
        </>
    )
}
