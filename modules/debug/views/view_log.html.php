<h1><?php echo esc_html($context['label']['title']); ?></h1>

<?php if (! empty($context['messages'])) : ?>
    <div id="message" class="upstream-messages notice is-dismissible">
        <?php foreach ($context['messages'] as $message) : ?>
            <p><?php echo $message; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="upstream-debug-data">
    <h2><?php echo esc_html($context['label']['debug_data']); ?></h2>
    <textarea readonly><?php echo $context['debug_data']; ?></textarea>
</div>

<hr>

<div id="upstream-debug-log">
    <h2><?php echo $context['label']['log_file']; ?></h2>

    <?php if (! $context['is_log_found']) : ?>
        <p><?php echo esc_html($context['message']['log_not_found']); ?></p>

        <p><?php echo esc_html($context['message']['contact_support_tip']);?> <a href="mailto:<?php echo esc_attr($context['contact_email']); ?>"><?php echo esc_html($context['contact_email']); ?></a></p>
    <?php endif; ?>

    <?php if ($context['is_log_found']) : ?>
        <h3><?php echo esc_html($context['label']['file_info']); ?></h3>
        <table id="upstream-debug-log-info">
            <tr>
                <th><?php echo esc_html($context['label']['path']); ?>:</th>
                <td><?php echo esc_html($context['file']['path']); ?></td>
            </tr>
            <tr>
                <th><?php echo esc_html($context['label']['size']); ?>:</th>
                <td><?php echo esc_html($context['file']['size']); ?> KB</td>
            </tr>
            <tr>
                <th><?php echo esc_html($context['label']['modification_time']); ?>:</th>
                <td><?php echo esc_html($context['file']['modification_time']); ?></td>
            </tr>
        </table>

        <p><?php echo esc_html($context['message']['click_to_delete']); ?></p>
        <a class="button button-danger" href="<?php echo esc_url($context['link_delete']); ?>"><?php echo esc_html($context['label']['delete_file']); ?></a>

        <h3><?php echo esc_html($context['label']['log_content']); ?></h3>
        <pre id="upstream-debug-log"><?php echo esc_html($context['file']['content']); ?></pre>
    <?php endif; ?>
</div>
wh
