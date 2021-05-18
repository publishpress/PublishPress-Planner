export default function Button(props) {
    let icon;

    if (props.icon) {
        icon = <span className={"dashicons dashicons-" + props.icon}></span>;
    }

    return (
        <a href={props.href || '#'} className="" onClick={props.onClick}>{icon}{props.label}</a>
    )
}
