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
use MoodlePluginCI\Bridge\MoodleConfig;
use MoodlePluginCI\Bridge\MoodlePlugin;
use MoodlePluginCI\Installer\Database\AbstractDatabase;
use MoodlePluginCI\Process\Execute;

/**
 * Installer Factory.
 */
class InstallerFactory
{
    /**
     * @var Moodle
     */
    public $moodle;

    /**
     * @var MoodlePlugin
     */
    public $plugin;

    /**
     * @var Execute
     */
    public $execute;

    /**
     * @var AbstractDatabase
     */
    public $database;

    /**
     * @var string
     */
    public $repo;

    /**
     * @var string
     */
    public $branch;

    /**
     * @var string
     */
    public $dataDir;

    /**
     * @var ConfigDumper
     */
    public $dumper;

    /**
     * @var string
     */
    public $pluginsDir;

    /**
     * @var bool
     */
    public $noInit;

    /**
     * Given a big bag of install options, add installers to the collection.
     *
     * @param InstallerCollection $installers Installers will be added to this
     */
    public function addInstallers(InstallerCollection $installers)
    {
        $installers->add(new MoodleInstaller($this->execute, $this->database, $this->moodle, new MoodleConfig(), $this->repo, $this->branch, $this->dataDir));
        $installers->add(new PluginInstaller($this->moodle, $this->plugin, $this->pluginsDir, $this->dumper));
        $installers->add(new VendorInstaller($this->moodle, $this->plugin, $this->execute));

        if ($this->noInit) {
            return;
        }
        if ($this->plugin->hasBehatFeatures() || $this->plugin->hasUnitTests()) {
            $installers->add(new TestSuiteInstaller($this->moodle, $this->plugin, $this->execute));
        }
    }
}
