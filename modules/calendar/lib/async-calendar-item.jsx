class PublishPressAsyncCalendarItem extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            icon: this.props.icon,
            time: this.props.time,
            label: this.props.label
        }
    }

    static defaultProps = {
        icon: null,
        time: null,
        label: 'Untitled'
    }

    render() {
        let iconSpan = null;
        let timeSpan = null;

        if (this.state.icon !== null) {
            iconSpan = <span className={'dashicons dashicons-' + this.state.icon}></span>;
        }

        if (this.state.time !== null) {
            timeSpan = <span className="publishpress-calendar-item-time">{this.state.time}</span>;
        }

        return (
            <li className="publishpress-calendar-item">{iconSpan}{timeSpan}{this.state.label}</li>
        )
    }
}

export {PublishPressAsyncCalendarItem};
