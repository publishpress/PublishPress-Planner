import {getMonthNameByMonthIndex} from "./Functions";

export default function CalendarCell(props) {
    function getDayItemClassName(dayDate, todayDate) {
        const businessDays = [1, 2, 3, 4, 5];

        let dayItemClassName = businessDays.indexOf(dayDate.getDay()) >= 0 ? 'business-day' : 'weekend-day'

        if (todayDate.getFullYear() === dayDate.getFullYear()
            && todayDate.getMonth() === dayDate.getMonth()
            && todayDate.getDate() === dayDate.getDate()
        ) {
            dayItemClassName += ' publishpress-calendar-today';
        }

        return 'publishpress-calendar-' + dayItemClassName;
    }

    return (
        <li
            key={props.date.toString()}
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

            <ul className="publishpress-calendar-day-items">{props.items}</ul>
        </li>
    )
}
