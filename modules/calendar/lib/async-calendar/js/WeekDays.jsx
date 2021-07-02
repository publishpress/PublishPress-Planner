const {__} = wp.i18n;

export default function WeekDays(props) {
    const DAYS_ON_A_WEEK = 7;
    const weekDayLabels = [
        __('Sun', 'publishpress'),
        __('Mon', 'publishpress'),
        __('Tue', 'publishpress'),
        __('Wed', 'publishpress'),
        __('Thu', 'publishpress'),
        __('Fri', 'publishpress'),
        __('Sat', 'publishpress'),
    ];

    let weekDays = [];
    let currentNumberOfDayOfWeek = (props.weekStartsOnSunday ? 0 : 1) - 1;

    for (let i = 0; i < DAYS_ON_A_WEEK; i++) {
        currentNumberOfDayOfWeek++;

        if (i === 6 && !props.weekStartsOnSunday) {
            currentNumberOfDayOfWeek = 0;
        }

        weekDays.push({
            key: currentNumberOfDayOfWeek.toString(),
            label: weekDayLabels[currentNumberOfDayOfWeek]
        });
    }

    return (
        <>
            {weekDays.map(item =>
                <th key={item.key.toString()}>{item.label}</th>
            )}
        </>
    )
}
