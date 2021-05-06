import {getHourStringOnFormat} from './calendar-functions';

class PublishPressAsyncCalendarItem extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            icon: this.props.icon,
            label: this.props.label,
            timestamp: this.props.timestamp,
            id: this.props.id,
        }
    }

    static defaultProps = {
        icon: null,
        label: 'Untitled',
        timestamp: null,
        id: null,
        showTime: true,
        showIcon: true,
        timeFormat: 'g:i a'
    }

    _getHourString() {
        let timestamp = new Date(Date.parse(this.state.timestamp));

        return getHourStringOnFormat(timestamp, this.props.timeFormat);
    }

    render() {
        let iconSpan;
        let timeSpan;

        if (this.props.showIcon && this.state.icon !== null) {
            iconSpan = <span className={'dashicons dashicons-' + this.state.icon}></span>;
        }

        if (this.props.showTime) {
            timeSpan = <time className="publishpress-calendar-item-time"
                             dateTime={this.state.timestamp}
                             title={this.state.timestamp}>{this._getHourString()}</time>;
        }

        return (
            <li className="publishpress-calendar-item"
                data-id={this.state.id}
                data-datetime={this.state.timestamp}>{iconSpan}{timeSpan}{this.state.label}</li>
        )
    }
}

export {PublishPressAsyncCalendarItem};
