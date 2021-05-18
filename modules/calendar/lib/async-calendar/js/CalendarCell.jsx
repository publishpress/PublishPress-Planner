import {getMonthNameByMonthIndex} from "./Functions";
import CalendarItem from "./CalendarItem";

export default function CalendarCell(props) {
    const calendarCell = React.useRef(null);
    let itemIndex = 0;

    const getDayItemClassName = (dayDate, todayDate) => {
        const businessDays = [1, 2, 3, 4, 5];

        let dayItemClassName = businessDays.indexOf(dayDate.getDay()) >= 0 ? 'business-day' : 'weekend-day'

        if (todayDate.getFullYear() === dayDate.getFullYear()
            && todayDate.getMonth() === dayDate.getMonth()
            && todayDate.getDate() === dayDate.getDate()
        ) {
            dayItemClassName += ' publishpress-calendar-today';
        }

        if (props.isLoading) {
            dayItemClassName += ' publishpress-calendar-day-loading';
        }

        return 'publishpress-calendar-' + dayItemClassName;
    }

    return (
        <li
            ref={calendarCell}
            className={getDayItemClassName(props.date, props.todayDate)}
            data-year={props.date.getFullYear()}
            data-month={props.date.getMonth() + 1}
            data-day={props.date.getDate()}>

            <div className="publishpress-calendar-date">
                {props.shouldDisplayMonthName &&
                <span
                    className="publishpress-calendar-month-name">{getMonthNameByMonthIndex(props.date.getMonth())}</span>
                }
                {props.date.getDate()}
            </div>

            <ul className="publishpress-calendar-day-items">
                {props.items.map((item) => {
                    return (
                        <CalendarItem
                            key={'item-' + item.id + '-' + props.date.getTime()}
                            icon={item.icon}
                            color={item.color}
                            label={item.label}
                            id={item.id}
                            timestamp={item.timestamp}
                            timeFormat={props.timeFormat}
                            showTime={item.showTime}
                            showIcon={true}
                            index={itemIndex++}/>
                    )
                })}
            </ul>
        </li>
    )
}