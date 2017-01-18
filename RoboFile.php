<?php

use Robo\Exception\TaskExitException;


/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    const SOURCE_PATH = 'src';

    const PACKAGE_PATH = 'package';

    const PLUGIN_NAME = 'publishpress';

    /**
     * Get the current version of the plugin
     */
    protected function getVersion()
    {
        $file = file_get_contents(self::SOURCE_PATH . '/' .  self::PLUGIN_NAME . '.php');

        preg_match('/Version:\s*([0-9\.a-z]*)/i', $file, $matches);

        return $matches[1];
    }

    /**
     * Register a change on the changelog for the current version
     */
    public function changelog()
    {
        $version = $this->getVersion();

        return $this->taskChangelog()
            ->version($version)
            ->askForChanges()
            ->run();
    }

    /**
     * Build the ZIP package
     *
     * @param string $destination Destination for the package. The ZIP file will be moved to that path.
     */
    public function build($destination = null)
    {
        $this->say('Building the package');

        // Build the package
        $filename = self::PLUGIN_NAME . '.zip';
        $packPath = self::PACKAGE_PATH . '/'. $filename;
        $pack     = $this->taskPack($packPath);

        $srcContent = scandir(self::SOURCE_PATH);
        foreach ($srcContent as $content) {
            if (! in_array($content, array('.', '..'))) {
                $path = self::SOURCE_PATH . '/' . $content;

                if (is_file($path)) {
                    $pack->addFile($content, $path);
                } else {
                    $pack->addDir($content, $path);
                }
            }
        }

        $return = $pack->run();

        // Should we move to any specific destination?
        if (!is_null($destination)) {
            if (!realpath($destination)) {
                throw new RuntimeException('Invalid destination path');
            }

            rename(self::PACKAGE_PATH . '/' . $filename, realpath($destination) . '/' . $filename);
        }

        // if ($return->)
        $this->say("Package built");

        return $return;
    }

    /**
     * Watch language files and convert the change ones to .mo files.
     */
    public function lang()
    {
        $languageDir = 'src/languages';

        $return = null;
        foreach (glob($languageDir . '/*.po') as $file) {
            $moFile = str_replace('.po', '.mo', $file);

            $return = $this->taskExec('msgfmt')
                ->arg('-o' . $moFile)
                ->arg($file)
                ->run();

            $this->say($moFile . ' created');
        }

        return $return;
    }

    /**
     * Watch language files and convert the change ones to .mo files.
     */
    public function wlang()
    {
        $languageDir = 'src/languages';

        $task = $this->taskWatch();

        foreach (glob($languageDir . '/*.po') as $file) {
            $task->monitor($file, function() use ($file) {
                    $moFile = str_replace('.po', '.mo', $file);

                    $this->taskExec('msgfmt')
                        ->arg('-o' . $moFile)
                        ->arg($file)
                        ->run();

                    $this->say($moFile . ' created');
                });
        }

        $task->run();

        return $return;
    }
}
