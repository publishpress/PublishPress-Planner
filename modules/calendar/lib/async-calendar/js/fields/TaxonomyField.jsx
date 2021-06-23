const __ = wp.i18n.__;

export default function TaxonomyField(props) {
    const editField = () => {
        return (
            <input type="text" value={props.value}/>
        )
    }

    const viewField = () => {
        if (props.value.length === 0) {
            return (
                <span className="publishpress-calendar-empty-value">{__('No terms', 'publishpress')}</span>
            );
        }

        return (
            <span>{props.value.join(', ')}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
