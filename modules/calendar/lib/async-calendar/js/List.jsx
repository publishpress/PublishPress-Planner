import ListItem from "./ListItem";

export default function List(props) {
    return (
        <ul className={props.className}>
            {props.items.map((item) =>
                <ListItem key={item.key.toString()} label={item.label}/>
            )}
        </ul>
    );
}
