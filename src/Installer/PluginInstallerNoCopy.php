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

/**
 * Moodle plugins installer.
 * This will install a plugin without copying it to the Moodle directory. Assuming it's already there.
 * The list of plugin to install is read from plugin.txt inside the externalplugindir.
 */
class PluginInstallerNoCopy extends AbstractInstaller
{
    use TraitInstallerCreateConfig;

    /**
     * @var Moodle
     */
    private $moodle;
    /**
     * @var array
     */
    private $extraplugins;
    /**
     * @var string
     */
    private $extraPluginsDir;

    /**
     * @param Moodle       $moodle
     * @param ConfigDumper $configDumper
     * @param string       $extraPluginsDir
     * @param array        $extraplugins
     */
    public function __construct(Moodle $moodle, ConfigDumper $configDumper, $extraPluginsDir = '', $extraplugins = [])
    {
        $this->moodle          = $moodle;
        $this->extraPluginsDir = $extraPluginsDir;
        $this->configDumper    = $configDumper;
        $this->extraplugins    = $extraplugins;
    }

    public function install()
    {
        $this->getOutput()->step('Dump configuration');

        $list    = [];
        $plugins = $this->scanForPlugins();
        foreach ($plugins->sortByDependencies()->all() as $plugin) {
            $directory = $this->moodle->getComponentInstallDirectory($plugin->getComponent());
            $list[]    = str_replace($this->moodle->directory.'/', '', $directory);
        }
        $this->configDumper->addSection('plugins', 'list', $list);
        $this->createConfigFile($this->moodle->directory.'/.moodle-plugin-ci.yml');
    }

    /**
     * @return MoodlePluginCollection
     */
    public function scanForPlugins()
    {
        $plugins      = new MoodlePluginCollection();
        $pluginsNames = $this->extraplugins;

        // Load additional plugins from the plugins.txt file.
        if (file_exists($this->extraPluginsDir.'/plugins.txt')) {
            $pluginsTxt = explode("\n", file_get_contents($this->extraPluginsDir.'/plugins.txt'));
            foreach ($pluginsTxt as $pluginName) {
                $pluginName = trim($pluginName);
                if ($pluginName === '') {
                    continue;
                }
                $pluginsNames[] = $pluginName;
            }
        }

        $pluginsNames = array_unique($pluginsNames);
        // Add plugins to the collections.
        foreach ($pluginsNames as $pluginName) {
            $file = new \SplFileInfo($this->moodle->directory.'/'.$pluginName);
            if (!$file->isDir()) {
                throw new \RuntimeException(sprintf('Plugin %s is not installed in standard Moodle', $pluginName));
            }
            $plugins->add(new MoodlePlugin($file->getRealPath()));
        }

        return $plugins;
    }

    public function stepCount()
    {
        return 2;
    }
}
