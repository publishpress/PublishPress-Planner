const $ = jQuery;

export default function Popup(props) {
    const popupRef = React.useRef(null);
    const [top, setTop] = React.useState(0);
    const [left, setLeft] = React.useState(0);
    const offsetX = 10;
    const offsetWidth = 300;

    const setPosition = () => {
        const targetPosition = $(props.target.current).position();
        const targetOffset = $(props.target.current).offset();
        const targetWidth = $(props.target.current).width();
        const popupWidth = $(popupRef.current).width();

        const isWiderThanParentWidth = () => {
            return (targetOffset.left + popupWidth + offsetX) >= $(document).width() - offsetWidth;
        }

        const getPositionOnRightSide = () => {
            return targetPosition.left + targetWidth + offsetX;
        }

        const getPositionOnLeftSide = () => {
            return targetPosition.left - (offsetX * 3) - popupWidth;
        }

        setTop(targetPosition.top);
        setLeft(isWiderThanParentWidth() ? getPositionOnLeftSide() : getPositionOnRightSide());

    }

    const didMount = () => {
        setPosition();

        return () => {

        }
    }

    React.useEffect(didMount, []);

    return (
        <div className="publishpress-calendar-popup" ref={popupRef} style={{top: top, left: left}}>
            <div className="publishpress-calendar-popup-title">{props.title}</div>

        </div>
    )
}
