<?php

use Robo\Exception\TaskExitException;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    const SOURCE_PATH = '../src';

    const PACKAGE_PATH = 'packages';

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
     * Build the ZIP package
     *
     * @param string $destination Destination for the package. The ZIP file will be moved to that path.
     */
    public function packBuild($destination = null)
    {
        $this->say('Building the package');

        // Build the package
        $filename = self::PLUGIN_NAME . '.zip';
        $packPath = self::PACKAGE_PATH . '/'. $filename;
        $pack     = $this->taskPack($packPath);

        // Remove existent package
        if (file_exists($packPath)) {
            unlink($packPath);
        }

        $srcContent = scandir(self::SOURCE_PATH);
        foreach ($srcContent as $content) {
            $ignore = array(
                '.',
                '..',
                'build',
                'tests',
                '.git',
                '.gitignore',
                'README',
                '.DS_Store',
                '.babelrc',
                'package.json',
            );

            if (! in_array($content, $ignore)) {
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

            $destFile = realpath($destination) . '/' . $filename;

            $this->say('Moving the new package to ' . $destFile);

            rename(self::PACKAGE_PATH . '/' . $filename, $destFile);
        }

        $this->say("Package built successfully");

        return $return;
    }

    /**
     * Build and move the package to a global path, set by
     * PS_GLOBAL_PACKAGES_PATH
     */
    public function packBuildGlobal() {
        $new_path = getenv('PS_GLOBAL_PACKAGES_PATH');

        if (! empty($new_path)) {
            $this->packBuild($new_path);
        }
    }

    /**
     * Build and move the package to an S4 bucket. After moving, display and
     * copy the shared link for the file.
     *
     * Tested on linux only.
     *
     * Requirements:
     *
     *    - s3cmd <http://s3tools.org>
     *    - xclip
     *
     * Configuring:
     *
     *    - Run: s3cmd --configure
     *    - Set the environment variables:
     *        - PS_S3_BUCKET
     *
     */
    public function packBuildS3() {
        $s3Bucket = getenv('PS_S3_BUCKET');
        $filename = self::PLUGIN_NAME . '.zip';
        $packPath = self::PACKAGE_PATH . '/'. $filename;

        $this->packBuild();

        // Create new prefix
        $prefix = md5(microtime());

        // Upload the new package to s3
        $s3Path = sprintf(
            's3://%s/%s/%s',
            $s3Bucket,
            $prefix,
            $filename
        );
        $cmd    = sprintf(
            's3cmd put --acl-public --reduced-redundancy %s %s',
            $packPath,
            $s3Path
        );
        $this->_exec($cmd);

        // Copy the public link to the clipboard
        $this->_exec('s3cmd info ' . $s3Path . ' | grep "URL:" | awk \'{ print $2 }\' | xclip');
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
        $packPath = realpath(self::PACKAGE_PATH) . '/'. self::PLUGIN_NAME;

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

        $dest = realpath($wordPressPath) . '/wp-content/plugins/' . self::PLUGIN_NAME;
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
        return glob(SOURCE_PATH . 'languages' . '/*.po');
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

        return $this->taskExec('msgfmt')
                ->arg('-o' . $moFile)
                ->arg($poFile)
                ->run();
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

    /**
     * Sync src files with staging files
     */
    public function syncSrc()
    {
        $wpPath      = getenv('PS_WP_PATH');
        $stagingPath = $wpPath  . '/wp-content/plugins/' . self::PLUGIN_NAME;

        if (empty($wpPath)) {
            throw new RuntimeException('Invalid WordPress path. Please, set the environment variable: PS_WP_PATH');
        }

        if (!file_exists($wpPath . '/wp-config.php')) {
            throw new RuntimeException('WordPress not found on: ' . $wpPath . '. Check the PS_WP_PATH environment variable');
        }

        $this->say('Removing current source files...');
        $this->_exec('rm -rf ' . self::SOURCE_PATH . '/*');

        $this->say('Copying files from ' . $stagingPath . ' to ' . self::SOURCE_PATH);
        $return = $this->_exec('cp -R ' . $stagingPath . '/* ' . self::SOURCE_PATH);

        return $return;
    }

    /**
     * Sync staging files with src files
     */
    public function syncStaging()
    {
        $wpPath      = getenv('PS_WP_PATH');
        $stagingPath = $wpPath  . '/wp-content/plugins/' . self::PLUGIN_NAME;

        if (empty($wpPath)) {
            throw new RuntimeException('Invalid WordPress path. Please, set the environment variable: PS_WP_PATH');
        }

        if (!file_exists($wpPath . '/wp-config.php')) {
            throw new RuntimeException('WordPress not found on: ' . $wpPath . '. Check the PS_WP_PATH environment variable');
        }

        if (is_dir($stagingPath)) {
            $this->say('Removing current files...');
            $this->_exec('rm -rf ' . $stagingPath . '/*');
        } else {
            mkdir($stagingPath);
        }

        $this->say('Copying files from ' . self::SOURCE_PATH . ' to ' . $stagingPath);
        $return = $this->_exec('cp -R ' . self::SOURCE_PATH . '/* ' . $stagingPath);

        return $return;
    }

    public function packCleanup()
    {
        shell_exec('git clean -xdf ' . self::SOURCE_PATH);
    }
}
