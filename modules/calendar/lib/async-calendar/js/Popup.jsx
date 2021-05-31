const $ = jQuery;

export default function Popup(props) {
    const popupRef = React.useRef(null);
    const [top, setTop] = React.useState(0);
    const [left, setLeft] = React.useState(0);
    const offsetX = 4;

    const setPosition = () => {
        const targetPosition = $(props.target.current).position();
        const targetWidth = $(props.target.current).width();

        setTop(targetPosition.top);
        setLeft(targetPosition.left + targetWidth + offsetX);
    }

    const didMount = () => {
        setPosition();

        return () => {

        }
    }

    React.useEffect(didMount, []);

    return (
        <div className="publishpress-calendar-popup" ref={popupRef} style={{top: top, left: left}}>
            {props.title}
        </div>
    )
}
