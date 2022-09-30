<div>
    <input
            type="checkbox"
            name="<?php echo esc_attr($context['name']); ?>"
            id="<?php echo esc_attr($context['id']); ?>"
            value="<?php echo esc_attr($context['value']); ?>"
            <?php checked( $context['selected'], true ); ?> />

    <label for="<?php echo esc_attr($context['id']); ?>"><?php echo esc_html($context['label']); ?></label>

</div>
