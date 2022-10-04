<div id="psppno-workflow-metabox-section-<?php echo esc_attr($context['id']); ?>"
     class="<?php echo esc_attr(isset($context['class']) ? $context['class'] : ''); ?> psppno-workflow-metabox-section">
    <?php if (! empty($context['header'])) : ?>
        <div class="psppno_workflow_metabox_section_header">
            <?php echo $context['header']; ?>
        </div>
    <?php endif; ?>

    <?php echo $context['html']; ?>
</div>
