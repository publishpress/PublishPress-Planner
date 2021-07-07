export default function Button(props) {
    let icon;

    if (props.icon) {
        icon = <span className={"dashicons dashicons-" + props.icon}></span>;
    }

    const className = 'publishpress-calendar-button ' + (props.className || '');

    return (
        <a href={props.href || '#'} className={className} onClick={props.onClick}>{icon}{props.label}</a>
    )
}
