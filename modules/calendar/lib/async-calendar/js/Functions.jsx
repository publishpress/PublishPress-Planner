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

export function calculateWeeksInMilliseconds(weeks) {
    return weeks * 7 * 24 * 60 * 60 * 1000;
}

export function getMonthNameByMonthIndex(month) {
    const strings = publishpressCalendarParams.strings;

    const monthNames = [
        strings.monthJan,
        strings.monthFeb,
        strings.monthMar,
        strings.monthApr,
        strings.monthMay,
        strings.monthJun,
        strings.monthJul,
        strings.monthAug,
        strings.monthSep,
        strings.monthOct,
        strings.monthNov,
        strings.monthDec
    ];

    return monthNames[month];
}

export function getDateWithNoTimezoneOffset(dateString) {
    let date = new Date(dateString);
    const browserTimezoneOffset = date.getTimezoneOffset() * 60000;

    return new Date(date.getTime() + browserTimezoneOffset);
}

export function getPostLinksElement(linkData, handleOnClick) {
    if (linkData.url) {
        return (<a href={linkData.url}>{linkData.label}</a>);
    } else if (linkData.action) {
        return (<a href="javascript:void(0);" onClick={(e) => handleOnClick(e, linkData)}>{linkData.label}</a>);
    }
}

export async function callAjaxAction(action, args, ajaxUrl) {
    let dataUrl = ajaxUrl + '?action=' + action;

    for (const argumentName in args) {
        if (!args.hasOwnProperty(argumentName)) {
            continue;
        }

        dataUrl += '&' + argumentName + '=' + args[argumentName];
    }

    const response = await fetch(dataUrl);
    return await response.json();
}

export async function callAjaxPostAction(action, args, ajaxUrl, body) {
    let dataUrl = ajaxUrl + '?action=' + action;

    for (const argumentName in args) {
        if (!args.hasOwnProperty(argumentName)) {
            continue;
        }

        dataUrl += '&' + argumentName + '=' + args[argumentName];
    }

    const response = await fetch(dataUrl, {
        method: 'post',
        body: body
    });
    return await response.json();
}

export function getTodayMidnight() {
    let today = new Date();
    today.setHours(0, 0, 0, 0);

    return today;
}

export function getDateInstanceFromString(dateString) {
    // The "-" char is replaced to make it compatible to Safari browser. Issue #1001.
    return new Date(String(dateString).replace(/-/g, "/"));
}
