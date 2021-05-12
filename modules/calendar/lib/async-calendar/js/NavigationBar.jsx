import Button from './Button'

export default function NavigationBar(props) {
    return (
        <div className="publishpress-calendar-navigation-bar">
            <Button label={'Refresh'} onClick={props.refreshFunction}/>
            <Button label={'<<'}/>
            <Button label={'<'}/>
            <Button label={'Today'}/>
            <Button label={'>'}/>
            <Button label={'>>'}/>
        </div>
    )
}
