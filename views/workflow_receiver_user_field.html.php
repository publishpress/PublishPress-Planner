<?php include __DIR__ . '/workflow_receiver_checkbox_field.html.php'; ?>

<div
        id="publishpress_notif_user_list_filter"
        <?php if (! $context['meta']['selected']) : ?>style="display:none;"<?php endif; ?>
        class="<?php echo esc_attr($context['id']); ?>_filters publishpress-filter-checkbox-list">

    <?php if (! empty($context['users'])) : ?>
        <select multiple="multiple" class="<?php echo esc_attr($context['list_class']); ?>" name="<?php echo esc_attr($context['input_name']); ?>" id="<?php echo esc_attr($context['input_id']); ?>">
            <?php foreach ($context['users'] as $user) : ?>
                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($context['meta']['selected'], true ); ?>><?php echo esc_html($user->display_name); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
</div>
