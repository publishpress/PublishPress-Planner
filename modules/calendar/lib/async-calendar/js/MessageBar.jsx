export default function MessageBar(props) {
    const showSpinner = props.showSpinner || false;
    const message = props.message || '';

    return (
        <div className={'publishpress-calendar-message-bar'}>
            {showSpinner &&
                <div className="sk-cube-grid">
                    <div className="sk-cube sk-cube1"></div>
                    <div className="sk-cube sk-cube2"></div>
                    <div className="sk-cube sk-cube3"></div>
                    <div className="sk-cube sk-cube4"></div>
                    <div className="sk-cube sk-cube5"></div>
                    <div className="sk-cube sk-cube6"></div>
                    <div className="sk-cube sk-cube7"></div>
                    <div className="sk-cube sk-cube8"></div>
                    <div className="sk-cube sk-cube9"></div>
                </div>
            }

            <span>{message}</span>
        </div>
    )
}
