import Button from './Button'

export default function NavigationBar() {
    return (
        <div className="publishpress-calendar-navigation-bar">
            <Button label={'<<'}/>
            <Button label={'<'}/>
            <Button label={'Today'}/>
            <Button label={'>'}/>
            <Button label={'>>'}/>
        </div>
    )
}
