<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    const SOURCE_PATH = 'src';

    const PACKAGE_PATH = 'package';

    const PUGLIN_NAME = 'publishpress';

    /**
     * Get the current version of the plugin
     */
    protected function getVersion()
    {
        $file = file_get_contents(self::SOURCE_PATH . '/' .  self::PUGLIN_NAME . '.php');

        preg_match('/Version:\s*([0-9\.a-z]*)/i', $file, $matches);

        return $matches[1];
    }

    /**
     * Register a change on the changelog for the current version
     */
    public function changelog()
    {
        $version = $this->getVersion();

        $this->taskChangelog()
            ->version($version)
            ->askForChanges()
            ->run();
    }

    /**
     * Build the ZIP package
     */
    public function build()
    {
        $this->say('Building the package');

        $pack = $this->taskPack(self::PACKAGE_PATH . '/'. self::PUGLIN_NAME . '.zip');

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

        $pack->run();

        $this->say('Package built!');
    }
}
