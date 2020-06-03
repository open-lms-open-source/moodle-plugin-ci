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

/**
 * Abstract Installer create config.
 */
trait TraitInstallerCreateConfig
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper = null;

    /**
     * Create plugin config file.
     *
     * @param string $toFile
     */
    public function createConfigFile($toFile)
    {
        if ($this->configDumper === null) {
            $this->configDumper = new ConfigDumper();
        }
        if (file_exists($toFile)) {
            $this->getOutput()->debug('Config file already exists in plugin, skipping creation of config file.');

            return;
        }
        if (!$this->configDumper->hasConfig()) {
            $this->getOutput()->debug('No config to write out, skipping creation of config file.');

            return;
        }
        $this->configDumper->dump($toFile);
        $this->getOutput()->debug('Created config file at '.$toFile);
    }
}
