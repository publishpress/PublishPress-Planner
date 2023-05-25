#!/usr/bin/env php
<?php
/**
 * Bootstrap the plugin files into the test container volume.
 */
$basePath = getcwd();
$wpRootPath = $argv[1];

$bootstrapFile = $basePath . '/tests/bootstrap.config.yml';

if (!file_exists($bootstrapFile)) {
    exit(0);
}

echo "▶ Parsing bootstrap.yml file\n";
/** @noinspection PhpComposerExtensionStubsInspection */
$config = yaml_parse_file($bootstrapFile);

if (isset($config['copy']) && ! empty($config['copy'])) {
    echo "▶ Copying files to the test volume\n";
    echo "\n";

    foreach ($config['copy'] as $source => $destination) {
        $destination = str_replace('%WP_ROOT_FOLDER%', $wpRootPath, $destination);
        $sourcePath = "$basePath/$source";

        // Using rsync because it is faster
        if (file_exists($sourcePath)) {
            if (is_dir($sourcePath)) {
                echo 'Copying dir ' . $source . "\n";

                shell_exec("[ -e $destination ] && chmod -R 777 $destination && rm -rf $destination");
                shell_exec("mkdir -p $destination");
                shell_exec("rsync -r $sourcePath/* $destination");
                shell_exec("[ -e $destination ] && chmod -R 777 $destination");
            } else {
                echo 'Copying file ' . $source . "\n";

                shell_exec("[ -e $destination ] && chmod -R 666 $destination && rm -rf $destination");
                shell_exec("rsync -r $sourcePath $destination");
                shell_exec("[ -e $destination ] && chmod -R 666 $destination");
            }
        } else {
            throw new Exception('Source path not found: ' . $source);
        }
    }

    echo "\n";
}
