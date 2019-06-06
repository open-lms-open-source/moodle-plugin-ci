<?php

/*
 * This file is part of the Moodle Plugin CI package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace MoodlePluginCI\Installer;

use MoodlePluginCI\Bridge\Moodle;
use MoodlePluginCI\Bridge\MoodlePlugin;
use MoodlePluginCI\Bridge\MoodlePluginCollection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Moodle plugins installer. This will copy plugins to Moodle root.
 * This will use the $plugindir as a plugin to install and configure, and scan the $extraPluginsDir to install more plugins.
 */
class PluginInstaller extends AbstractPluginInstaller
{
    use TraitInstallerCreateConfig;
    /**
     * @var Moodle
     */
    private $moodle;

    /**
     * @var string
     */
    private $plugindir;

    /**
     * @var string
     */
    private $extraPluginsDir;
    /**
     * @var MoodlePlugin
     */
    private $pluginSingleton;

    /**
     * @param Moodle       $moodle
     * @param string       $plugindir
     * @param string       $extraPluginsDir
     * @param ConfigDumper $configDumper
     */
    public function __construct(Moodle $moodle, $plugindir, $extraPluginsDir, ConfigDumper $configDumper)
    {
        $this->moodle = $moodle;

        // No Backward compatibility.
        if ($plugindir instanceof MoodlePlugin) {
            throw new \InvalidArgumentException('plugindir should be a string and not a plugin, then use getLocalPluginSingleton to get the plugin.');
        }
        $this->plugindir       = $plugindir;
        $this->extraPluginsDir = $extraPluginsDir;
        $this->configDumper    = $configDumper;
    }

    public function install()
    {
        $this->getOutput()->step('Install plugins');

        $plugins = $this->pluginsToInstall();
        $sorted  = $plugins->sortByDependencies();

        foreach ($sorted->all() as $plugin) {
            $directory = $this->installPluginIntoMoodle($plugin);

            if ($plugin->getComponent() === $this->getLocalPluginSingleton()->getComponent()) {
                $this->addEnv('PLUGIN_DIR', $directory);
                $this->createConfigFile($directory.'/.moodle-plugin-ci.yml');

                // Update plugin so other installers use the installed path.
                $this->getLocalPluginSingleton()->directory = $directory;
            }
        }
    }

    /**
     * @return MoodlePluginCollection
     */
    public function pluginsToInstall()
    {
        $plugins = new MoodlePluginCollection();
        $plugins->add($this->getLocalPluginSingleton());

        if (empty($this->extraPluginsDir)) {
            return $plugins;
        }

        /** @var SplFileInfo[] $files */
        $files = Finder::create()->directories()->in($this->extraPluginsDir)->depth(0);
        foreach ($files as $file) {
            $plugins->add(new MoodlePlugin($file->getRealPath()));
        }

        return $plugins;
    }

    /**
     * @return MoodlePlugin[]
     */
    public function pluginsToPrepare()
    {
        return [$this->getLocalPluginSingleton()];
    }

    /**
     * Install the plugin into Moodle.
     *
     * @param MoodlePlugin $plugin
     *
     * @return string
     */
    public function installPluginIntoMoodle(MoodlePlugin $plugin)
    {
        $this->getOutput()->info(sprintf('Installing %s', $plugin->getComponent()));

        $directory = $this->moodle->getComponentInstallDirectory($plugin->getComponent());

        if (is_dir($directory)) {
            throw new \RuntimeException('Plugin is already installed in standard Moodle');
        }

        $this->getOutput()->info(sprintf('Copying plugin from %s to %s', $plugin->directory, $directory));

        // Install the plugin.
        $filesystem = new Filesystem();
        $filesystem->mirror($plugin->directory, $directory);

        return $directory;
    }

    public function stepCount()
    {
        return 1;
    }

    /**
     * @return MoodlePlugin
     */
    public function getLocalPluginSingleton()
    {
        if ($this->pluginSingleton === null) {
            $this->pluginSingleton = new MoodlePlugin($this->plugindir);
        }

        return $this->pluginSingleton;
    }
}
