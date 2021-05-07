/**
 * Base on :
 *     https://stackoverflow.com/questions/16590500/javascript-calculate-date-from-week-number
 */
export function getBeginDateOfWeekByWeekNumber(weekNumber, year, weekStartsOnSunday = true) {
    let simpleDate = new Date(year, 0, 1 + (weekNumber - 1) * 7);
    let dayOfWeek = simpleDate.getDay();
    let weekStartISO = simpleDate;


    if (dayOfWeek <= 4) {
        weekStartISO.setDate(simpleDate.getDate() - simpleDate.getDay() + 1);
    } else {
        weekStartISO.setDate(simpleDate.getDate() + 8 - simpleDate.getDay());
    }

    if (weekStartsOnSunday) {
        weekStartISO.setDate(weekStartISO.getDate() - 1);
    }

    return weekStartISO;
}

/* For a given date, get the ISO week number
 *
 * Based on information at:
 *
 *    http://www.merlyn.demon.co.uk/weekcalc.htm#WNR
 *
 * Algorithm is to find nearest thursday, it's year
 * is the year of the week number. Then get weeks
 * between that date and the first day of that year.
 *
 * Note that dates in one year can be weeks of previous
 * or next year, overlap is up to 3 days.
 *
 * e.g. 2014/12/29 is Monday in week  1 of 2015
 *      2012/1/1   is Sunday in week 52 of 2011
 */
export function getWeekNumberByDate(theDate, weekStartsOnSunday = true) {

    // Copy date so don't modify original
    let theDateCopy = new Date(theDate.getFullYear(), theDate.getMonth(), theDate.getDate(), theDate.getHours(), theDate.getMinutes(), theDate.getSeconds(), theDate.getMilliseconds());

    let dayOfWeek = theDateCopy.getDay();

    // Set to nearest Thursday: current date + 4 - current day number
    // Make Sunday's day number 7
    theDateCopy.setDate(theDateCopy.getDate() + 4 - (theDateCopy.getDay() || 7));

    // Get first day of year
    let yearStart = new Date(theDateCopy.getFullYear(), 0, 1);
    // Calculate full weeks to nearest Thursday
    let weekNo = Math.round((((theDateCopy - yearStart) / 86400000) + 1) / 7);

    if (weekStartsOnSunday && dayOfWeek === 0) {
        weekNo++;
    }

    // Return array of year and week number
    return [theDateCopy.getFullYear(), weekNo];
}

export function getBeginDateOfWeekByDate(theDate, weekStartsOnSunday = true) {
    let weekNumber = getWeekNumberByDate(theDate, weekStartsOnSunday);

    return getBeginDateOfWeekByWeekNumber(weekNumber[1], weekNumber[0], weekStartsOnSunday);
}

export function getHourStringOnFormat(timestamp, timeFormat = 'ga') {
    let hours = timestamp.getHours();

    if (timeFormat === 'ga' || timeFormat === 'ha') {
        if (hours === 0) {
            hours = '12am';
        } else if (hours < 12) {
            if (timeFormat === 'ha') {
                hours = hours.toString().padStart(2, '0');
            }
            hours += 'am';
        } else {
            if (hours > 12) {
                hours -= 12;
            }

            if (timeFormat === 'ha') {
                hours = hours.toString().padStart(2, '0');
            }

            hours += 'pm';
        }
    } else {
        hours = hours.toString().padStart(2, '0');
    }

    return hours;
}

export function getDateAsStringInWpFormat(theDate) {
    return theDate.getFullYear() + '-'
        + (theDate.getMonth() + 1).toString().padStart(2, '0') + '-'
        + theDate.getDate().toString().padStart(2, '0');
}
