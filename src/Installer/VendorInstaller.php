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
use MoodlePluginCI\Process\Execute;
use Symfony\Component\Process\Process;

/**
 * Vendor installer.
 */
class VendorInstaller extends AbstractInstaller
{
    /**
     * @var Moodle
     */
    private $moodle;

    /**
     * @var MoodlePlugin[]
     */
    private $plugins;

    /**
     * @var Execute
     */
    private $execute;

    /**
     * VendorInstaller constructor.
     *
     * @param Moodle         $moodle
     * @param MoodlePlugin[] $plugins
     * @param Execute        $execute
     */
    public function __construct(Moodle $moodle, array $plugins, Execute $execute)
    {
        $this->moodle  = $moodle;
        $this->plugins = $plugins;
        $this->execute = $execute;
    }

    public function install()
    {
        $this->getOutput()->step('Install global dependencies');

        $processes = [];
        if ($this->shouldInstallComposer()) {
            $this->getOutput()->info(sprintf('Install composer packages on the Moodle directory: %s', $this->moodle->directory));
            $processes[] = new Process('composer install --no-interaction --prefer-dist', $this->moodle->directory, null, null, null);
        }
        $sudo        = getenv('NPM_SUDO') ? 'sudo ' : '';
        $processes[] = new Process($sudo.'npm install -g --no-progress grunt', null, null, null, null);

        $this->execute->mustRunAll($processes);

        $this->getOutput()->step('Install npm dependencies');

        $this->execute->mustRun(new Process('npm install --no-progress', $this->moodle->directory, null, null, null));

        foreach ($this->plugins as $plugin) {
            if ($plugin->hasNodeDependencies()) {
                $this->execute->mustRun(new Process('npm install --no-progress', $plugin->directory, null, null, null));
            }
        }

        $this->execute->mustRun(new Process('grunt ignorefiles', $this->moodle->directory, null, null, null));
    }

    public function stepCount()
    {
        return 2;
    }

    /**
     * @return bool
     */
    private function shouldInstallComposer()
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->hasBehatFeatures() || $plugin->hasUnitTests()) {
                return true;
            }
        }

        return false;
    }
}
