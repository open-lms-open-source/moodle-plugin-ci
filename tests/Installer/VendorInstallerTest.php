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

namespace MoodlePluginCI\Tests\Installer;

use MoodlePluginCI\Bridge\MoodlePlugin;
use MoodlePluginCI\Installer\VendorInstaller;
use MoodlePluginCI\Tests\Fake\Bridge\DummyMoodle;
use MoodlePluginCI\Tests\Fake\Process\DummyExecute;

class VendorInstallerTest extends \PHPUnit_Framework_TestCase
{
    public function testInstall()
    {
        $installer = new VendorInstaller(
            new DummyMoodle(''),
            [new MoodlePlugin(__DIR__.'/../Fixture/moodle-local_travis')],
            new DummyExecute()
        );
        $installer->install();

        $this->assertSame($installer->stepCount(), $installer->getOutput()->getStepCount());
    }

    public function testInstallSudo()
    {
        $env = getenv('NPM_SUDO');
        try {
            putenv('NPM_SUDO=1');
            $dummy     = new DummyExecute();
            $installer = new VendorInstaller(
                new DummyMoodle(''),
                [new MoodlePlugin(__DIR__.'/../Fixture/moodle-local_travis')],
                $dummy
            );
            $installer->install();
            $commands = $dummy->getHistory();
            $this->assertSame('sudo npm install -g --no-progress grunt', $commands[1]);
        } finally {
            putenv('NPM_SUDO='.$env);
        }
    }

    public function testComposerInstall()
    {
        $dummy  = new DummyExecute();
        $plugin = new MoodlePlugin(__DIR__.'/../Fixture/moodle-local_travis');
        $this->assertTrue($plugin->hasUnitTests());
        $this->assertTrue($plugin->hasBehatFeatures());
        $installer = new VendorInstaller(
            new DummyMoodle(''),
            [$plugin],
            $dummy
        );
        $installer->install();
        $commands = $dummy->getHistory();
        $this->assertSame('composer install --no-interaction --prefer-dist', $commands[0]);
    }

    public function testNoComposerInstall()
    {
        $plugin = new MoodlePlugin(__DIR__.'/../Fixture/moodle-local_emptyplugin');
        $this->assertFalse($plugin->hasUnitTests());
        $this->assertFalse($plugin->hasBehatFeatures());
        $dummy     = new DummyExecute();
        $installer = new VendorInstaller(
            new DummyMoodle(''),
            [$plugin],
            $dummy
        );
        $installer->install();
        $commands = $dummy->getHistory();
        $this->assertNotTrue('composer install --no-interaction --prefer-dist' === $commands[0]);
    }
}
