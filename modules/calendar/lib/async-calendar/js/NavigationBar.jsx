import Button from './Button'

export default function NavigationBar(props) {
    return (
        <div className="publishpress-calendar-navigation-bar">
            <Button label={'Refresh'} onClick={props.refreshOnClick}/>
            <Button label={'<<'} onClick={props.backPageOnClick}/>
            <Button label={'<'} onClick={props.backOnClick}/>
            <Button label={'Today'} onClick={props.todayOnClick}/>
            <Button label={'>'} onClick={props.forwardOnClick}/>
            <Button label={'>>'} onClick={props.forwardPageOnClick}/>
        </div>
    )
}
