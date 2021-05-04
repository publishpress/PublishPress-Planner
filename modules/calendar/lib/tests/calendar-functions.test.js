import {getBeginDateOfWeekByWeekNumber, getWeekNumberByDate} from "../calendar-functions";

/*
 * getBeginDateOfWeekByWeekNumber, sunday as first day of week
 */
test('getBeginDateOfWeekByWeekNumber for week 13 in 2020 when sunday is the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(13, 2020, true)
    ).toStrictEqual(new Date(2020, 2, 22));
})

test('getBeginDateOfWeekByWeekNumber for week 33 in 2020 when sunday is the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(33, 2020, true)
    ).toStrictEqual(new Date(2020, 7, 9));
})

test('getBeginDateOfWeekByWeekNumber for week 18 in 2021 when sunday is the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(18, 2021, true)
    ).toStrictEqual(new Date(2021, 4, 2));
})

test('getBeginDateOfWeekByWeekNumber for week 51 in 2021 when sunday is the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(51, 2021, true)
    ).toStrictEqual(new Date(2021, 11, 19));
})

test('getBeginDateOfWeekByWeekNumber for week 10 in 2022 when sunday is the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(10, 2022, true)
    ).toStrictEqual(new Date(2022, 2, 6));
})

/*
 * getBeginDateOfWeekByWeekNumber, monday as first day of week
 */
test('getBeginDateOfWeekByWeekNumber for week 13 in 2020 when sunday is not the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(13, 2020, false)
    ).toStrictEqual(new Date(2020, 2, 23));
})

test('getBeginDateOfWeekByWeekNumber for week 8 in 2021 when sunday is not the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(8, 2021, false)
    ).toStrictEqual(new Date(2021, 1, 22));
})

test('getBeginDateOfWeekByWeekNumber for week 9 in 2021 when sunday is not the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(9, 2021, false)
    ).toStrictEqual(new Date(2021, 2, 1));
})

test('getBeginDateOfWeekByWeekNumber for week 18 in 2021 when sunday is not the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(18, 2021, false)
    ).toStrictEqual(new Date(2021, 4, 3));
})

test('getBeginDateOfWeekByWeekNumber for week 45 in 2021 when sunday is not the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(45, 2021, false)
    ).toStrictEqual(new Date(2021, 10, 8));
})

test('getBeginDateOfWeekByWeekNumber for week 10 in 2022 when sunday is not the first day of week', () => {
    expect(
        getBeginDateOfWeekByWeekNumber(10, 2022, false)
    ).toStrictEqual(new Date(2022, 2, 7));
})

/*
 * getWeekNumberByDate, sunday as first day of week
 */
test('getWeekNumberByDate for 2021-05-02 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 4, 2), true)
    ).toStrictEqual([2021, 18]);
})

test('getWeekNumberByDate for 2021-05-03 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 4, 3), true)
    ).toStrictEqual([2021, 18]);
})

test('getWeekNumberByDate for 2021-10-12 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 9, 12), true)
    ).toStrictEqual([2021, 41]);
})

test('getWeekNumberByDate for 2021-11-7 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 10, 7), true)
    ).toStrictEqual([2021, 45]);
})

/*
 * getWeekNumberByDate, monday as first day of week
 */
test('getWeekNumberByDate for 2021-05-02 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 4, 2), false)
    ).toStrictEqual([2021, 17]);
})

test('getWeekNumberByDate for 2021-05-03 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 4, 3), false)
    ).toStrictEqual([2021, 18]);
})

test('getWeekNumberByDate for 2021-10-12 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 9, 12), false)
    ).toStrictEqual([2021, 41]);
})

test('getWeekNumberByDate for 2021-11-7 when sunday is the first day of week', () => {
    expect(
        getWeekNumberByDate(new Date(2021, 10, 7), false)
    ).toStrictEqual([2021, 44]);
})
