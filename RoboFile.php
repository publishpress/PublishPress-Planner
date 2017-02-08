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

    const PLUGIN_SLUG = 'publishpress';

    /**
     * Get the current version of the plugin
     */
    protected function getVersion()
    {
        $file = file_get_contents(self::SOURCE_PATH . '/' .  self::PLUGIN_SLUG . '.php');

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
    public function packBuild($destination = null)
    {
        $this->say('Building the package');

        // Build the package
        $filename = self::PLUGIN_SLUG . '.zip';
        $packPath = self::PACKAGE_PATH . '/'. $filename;
        $pack     = $this->taskPack($packPath);

        $srcContent = scandir(self::SOURCE_PATH);
        foreach ($srcContent as $content) {
            if (! in_array($content, array('.', '..'))) {
                $path = self::SOURCE_PATH . '/' . $content;

                if ($content !== 'node_modules') {
                    if (is_file($path)) {
                        $pack->addFile($content, $path);
                    } else {
                        $pack->addDir($content, $path);
                    }
                }
            }
        }

        $return = $pack->run();

        // Should we move to any specific destination?
        if (!is_null($destination)) {
            if (!realpath($destination)) {
                throw new RuntimeException('Invalid destination path');
            }

            $destFile = realpath($destination) . '/' . $filename;

            $this->say('Moving the new package to ' . $destFile);

            rename(self::PACKAGE_PATH . '/' . $filename, $destFile);
        }

        $this->say("Package built successfully");

        return $return;
    }

    /**
     * Copy the folder to the wordpress given location
     *
     * @param string $wordPressPath Path for the WordPress installation
     */
    public function packInstall($wordPressPath)
    {
        $this->say('Building the package');

        // Build the package
        $packPath = realpath(self::PACKAGE_PATH) . '/'. self::PLUGIN_SLUG;

        if (is_dir($packPath)) {
            $this->_exec('rm -rf ' . $packPath);
        }

        $this->packBuild();

        // Unzip it
        $this->_exec('unzip ' . $packPath . '.zip -d ' . $packPath);

        // Installing the package
        $this->say('Installing the package');

        if (!realpath($wordPressPath)) {
            throw new RuntimeException('Invalid WordPress path');
        }

        $dest = realpath($wordPressPath) . '/wp-content/plugins/' . self::PLUGIN_SLUG;
        // Remove existent plugin directory
        if (is_dir($dest)) {
            $this->_exec('rm -rf ' . $dest);
        }

        $this->_exec('mv ' . $packPath . ' ' . $dest);

        $this->say('Package installed');
        $this->_exec('say "pack installed!"');

        return;
    }

    /**
     * Watch for changes and copy the folder to the wordpress given location
     *
     * @param string $wordPressPath Path for the WordPress installation
     */
    public function packWatchInstall($wordPressPath)
    {
        $return = $this->taskWatch()
            ->monitor('src', function() use ($wordPressPath) {
                $this->packInstall($wordPressPath);
            })
            ->run();

        return $return;
    }

    /**
     * Return a list of PO files from the languages dir
     *
     * @return string
     */
    protected function getPoFiles()
    {
        $languageDir = 'src/languages';

        return glob($languageDir . '/*.po');
    }

    /**
     * Compile language MO files from PO files.
     *
     * @param string $poFile
     * @return Result
     */
    protected function compileMOFromPO($poFile)
    {
        $moFile = str_replace('.po', '.mo', $poFile);

        return $this->_exec('msgfmt --output-file=' . $moFile . ' ' . $poFile);
    }

    /**
     * Compile all PO language files
     */
    public function langCompile()
    {
        $return = null;
        $files  = $this->getPoFiles();

        foreach ($files as $file) {
            $return = $this->compileMOFromPO($file);

            $this->say('Language file compiled');
        }

        return $return;
    }

    /**
     * Watch language files and compile the changed ones to MO files.
     */
    public function langWatch()
    {
        $return = null;
        $task   = $this->taskWatch();
        $files  = $this->getPoFiles();

        foreach ($files as $file) {
            $task->monitor($file, function() use ($file) {
                $return = $this->compileMOFromPO($file);

                $this->say('Language file compiled');
            });
        }

        $task->run();

        return $return;
    }
}
