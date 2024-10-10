export default function TimeField(props) {
    const editField = () => {
        return (
            <input type="text"
                   id={props.id}
                   placeholder={props.placeholder || null}
                   value={props.value}/>
        )
    };

    const viewField = () => {
        return (
            <span id={props.id}>{props.value}</span>
        );
    };
    
    const initInputMask = () => {
        const selector = '#' + props.id;
        const $input = jQuery(selector);
    
        // Ensure input exists
        if (!$input.length) return;
    
        // Set placeholder if provided
        $input.attr('placeholder', props.placeholder || 'HH:MM');
    
        // Input event for real-time validation
        $input.on('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-numeric characters
    
            // Ensure the first hour character is valid (0, 1, 2)
            if (value.length === 1 && parseInt(value[0], 10) > 2) {
                $input.val('');  // Clear input if first hour is invalid
                return;
            }
    
            // Automatically format as HH:MM when at least 3 digits are entered
            if (value.length >= 3) {
                value = value.slice(0, 2) + ':' + value.slice(2, 4);
            }
    
            // Limit to 5 characters
            $input.val(value.slice(0, 5));
    
            // Split value into hours and minutes for further validation
            const parts = value.split(':');
    
            if (parts[0]) {
                const hours = parts[0];
                
                // Validate the second hour character if the first is '2' (should only be 0-3)
                if (hours.length === 2 && hours[0] === '2' && parseInt(hours[1], 10) > 3) {
                    $input.val(hours[0]);  // Remove invalid second digit
                    return;
                }
            }
    
            if (parts[1]) {
                const minutes = parts[1];
    
                // Validate the first minute character (should be 0-5)
                if (minutes.length === 1 && parseInt(minutes[0], 10) > 5) {
                    $input.val(parts[0] + ':');  // Remove invalid first minute digit
                    return;
                }
    
                // Limit to valid minute values (00-59)
                if (minutes.length === 2 && parseInt(minutes, 10) > 59) {
                    $input.val(parts[0] + ':' + minutes[0]);  // Remove second invalid minute digit
                }
            }
        });
    
        // Change event for final validation (optional)
        $input.on('change', (e) => {
            const value = $input.val();
            const timePattern = /^([01][0-9]|2[0-3]):[0-5][0-9]$/;  // Validate HH:MM
    
            if (!timePattern.test(value)) {
                alert('Invalid time format. Please enter a time between 00:00 and 23:59.');
                $input.val('');  // Clear invalid input
            }
    
            // Call onChange if provided
            if (props.onChange) {
                props.onChange(e, value);
            }
        });
    };

    React.useEffect(initInputMask);

    return props.isEditing ? editField() : viewField();
}
