import React from 'react';
import PublishPressAsyncCalendarItem from "./async-calendar-item";

let {__} = wp.i18n;

class PublishPressAsyncCalendar extends React.Component {
    componentDidMount() {
        this.refresh();
    }

    refresh() {
        if (typeof this.props.getDataCallback !== 'function') {
            return [];
        }
        s
        return this.props.getDataCallback(
            this.props.numberOfWeeksToDisplay,
            this.props.firstDateToDisplay,
            this
        );
    }

    _getDayItems(dayDate) {
        dayDate = this._getDateFromString(dayDate);

        if (!this._isValidDate(dayDate)) {
            return [];
        }

        let itemsList = [];
        let dateString = this._getDateAsStringInWpFormat(dayDate);
        let dayItems = this.state.items[dateString] ? this.state.items[dateString] : null;

        if (null === dayItems) {
            return [];
        }

        for (let i = 0; i < dayItems.length; i++) {
            itemsList.push(
                <PublishPressAsyncCalendarItem icon={dayItems[i].icon}
                                               time={dayItems[i].time}
                                               label={dayItems[i].label}
                                               id={dayItems[i].id}
                                               timestamp={dayItems[i].timestamp}
                                               timeFormat={this.props.timeFormat}/>
            );
        }

        return itemsList;
    }
}
