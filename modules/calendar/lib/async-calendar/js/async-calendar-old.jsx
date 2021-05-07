import React from 'react';
import PublishPressAsyncCalendarItem from "./async-calendar-item";

let {__} = wp.i18n;

class PublishPressAsyncCalendar extends React.Component {
    constructor(props) {
        super(props);

        // Compensate the timezone in the browser with the server's timezone
        const timezoneOffset = (new Date().getTimezoneOffset() / 60) + parseInt(this.props.timezoneOffset);

        this.props.todayDate.setHours(this.props.todayDate.getHours() + timezoneOffset);
        this.props.firstDateToDisplay = this._getFirstDayOfWeek(this.props.firstDateToDisplay);

        this.state = {
            error: null,
            isLoaded: false,
            items: []
        };
    }

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


    _getDaysCells() {
        const firstDayOfTheFirstWeek = this._getFirstDayOfWeek(this.props.firstDateToDisplay);
        const numberOfDaysToDisplay = this.props.numberOfWeeksToDisplay * 7;

        let daysCells = [];
        let dayDate;
        let lastMonthDisplayed = firstDayOfTheFirstWeek.getMonth();
        let shouldDisplayMonthName;

        for (let i = 0; i < numberOfDaysToDisplay; i++) {
            dayDate = new Date(firstDayOfTheFirstWeek);
            dayDate.setDate(dayDate.getDate() + i);

            shouldDisplayMonthName = lastMonthDisplayed !== dayDate.getMonth() || i === 0;

            daysCells.push(
                <li
                    className={this._getDayItemClassName(dayDate)}
                    data-year={dayDate.getFullYear()}
                    data-month={dayDate.getMonth() + 1}
                    data-day={dayDate.getDate()}>
                    <div className="publishpress-calendar-date">
                        {shouldDisplayMonthName &&
                        <span
                            className="publishpress-calendar-month-name">{this._getMonthName(dayDate.getMonth())}</span>
                        }
                        {dayDate.getDate()}
                    </div>
                    <ul className="publishpress-calendar-day-items">{this._getDayItems(dayDate)}</ul>
                </li>
            );

            lastMonthDisplayed = dayDate.getMonth();
        }

        return daysCells;
    }
}
