export default function Button(props) {
    return (
        <a href={props.href || '#'} className="button">{props.label || 'Do it'}</a>
    )
}
