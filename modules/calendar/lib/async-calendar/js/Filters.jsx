import Select from "./Select";

const $ = jQuery;

export default function Filters(props) {
    const statusesOptions = props.statuses.map((item) => {
        return {
            value: item.slug,
            text: item.name
        };
    });

    const dispatchFilterEvent = (filterName, value) => {
        window.dispatchEvent(
            new CustomEvent(
                'PublishpressCalendar:filter',
                {
                    detail: {
                        filter: filterName,
                        value: value
                    }
                }
            )
        );
    }

    const handleStatusChange = (e, element, value) => {
        dispatchFilterEvent('status', value);
    }

    const handleCategoriesChange = (e, element, value) => {
        dispatchFilterEvent('category', value);
    }

    return (
        <>
            <Select
                blankOptionText={"All statuses"}
                options={statusesOptions}
                onSelect={handleStatusChange}
                onClear={handleStatusChange}
                />

            <Select
                blankOptionText={"All categories"}
                ajaxurl={props.ajaxurl}
                nonce={props.nonce}
                ajaxAction={'publishpress_calendar_search_categories'}
                onSelect={handleCategoriesChange}
                onClear={handleCategoriesChange}
            />
        </>
    )
}
