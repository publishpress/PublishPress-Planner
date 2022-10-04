<div class="wrap publishpress-admin">
    <div>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($context['modules'] as $module) : ?>
                <?php if (! empty($module->options_page) && $module->options->enabled === 'on') : ?>
                    <a
                            href="?page=<?php echo esc_attr($context['slug']); ?>&settings_module=<?php echo esc_attr($module->settings_slug); ?>"
                            class="nav-tab <?php if ($context['settings_slug'] === $module->settings_slug) : ?>nav-tab-active<?php endif; ?>">

                        <?php echo esc_html($module->title); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </h2>

        <div class="pp-module-settings"><?php echo $context['module_output']; ?></div>
    </div>
</div>
