export default function MessageBar(props) {
    const showSpinner = props.showSpinner || false;
    const message = props.message || '';

    return (
        <div className={'publishpress-calendar-message-bar'}>
            {showSpinner &&
            <span className={'dashicons dashicons-update-alt publishpress-spinner'}/>
            }
            <span>{message}</span>
        </div>
    )
}
