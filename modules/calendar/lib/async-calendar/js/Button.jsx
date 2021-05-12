export default function Button(props) {
    return (
        <a href={props.href || '#'} className="button" onClick={props.onClick}>{props.label || 'Do it'}</a>
    )
}
