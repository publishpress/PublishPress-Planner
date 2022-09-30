<div>
    <input
            type="checkbox"
            name="<?php echo esc_attr($context['name']); ?>"
            id="<?php echo esc_attr($context['id']); ?>"
            value="<?php echo esc_attr($context['value']); ?>"
            <?php checked($context['meta']['selected'], 1 ); ?> />

    <label for="<?php echo esc_attr($context['id']); ?>"><?php echo esc_html($context['label']); ?></label>
</div>
