<input type="checkbox" id="<?php echo esc_attr($context['name']); ?>" name="<?php echo esc_attr($context['name']); ?>"
       value="1" <?php echo $context['value'] == 1 ? 'checked="checked"' : ''; ?>/>

<label for="<?php echo esc_attr($context['name']); ?>"><?php echo esc_html($context['label']); ?></label>
<?php if (! empty($context['description'])) : ?>
    <div class="psppno_workflow_metabox_option_description"><?php echo esc_html($context['description']); ?></div>
<?php endif; ?>
