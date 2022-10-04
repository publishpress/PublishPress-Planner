<div>
    <?php if (! empty($context['label'])) : ?>
        <label for="<?php echo esc_attr($context['id']); ?>"><?php echo esc_html($context['label']); ?></label>
    <?php endif; ?>

    <input
            type="text"
            name="<?php echo esc_attr($context['name']); ?>"
            id="<?php echo esc_attr($context['id']); ?>"
            value="%value%"
            placeholder="<?php echo esc_attr($context['placeholder']); ?>"
            <?php if (! empty($context['description'])) : ?>
                title="<?php echo esc_attr($context['description']); ?>"
            <?php endif; ?>
    />
</div>
