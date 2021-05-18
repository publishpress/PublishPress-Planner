import Button from './Button'

export default function NavigationBar(props) {
    return (
        <div className="publishpress-calendar-navigation-bar">
            <Button label={'Refresh'} onClick={props.refreshOnClickCallback}/>
            <Button label={'<<'} onClick={props.backPageOnClickCallback}/>
            <Button label={'<'} onClick={props.backOnClickCallback}/>
            <Button label={'Today'} onClick={props.todayOnClickCallback}/>
            <Button label={'>'} onClick={props.forwardOnClickCallback}/>
            <Button label={'>>'} onClick={props.forwardPageOnClickCallback}/>
        </div>
    )
}
