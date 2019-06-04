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

use MoodlePluginCI\Bridge\MoodleConfig;
use MoodlePluginCI\Installer\Database\MySQLDatabase;
use MoodlePluginCI\Installer\InstallOutput;
use MoodlePluginCI\Installer\MoodleInstaller;
use MoodlePluginCI\Tests\Fake\Bridge\DummyMoodle;
use MoodlePluginCI\Tests\Fake\Process\DummyExecute;
use MoodlePluginCI\Tests\FilesystemTestCase;
use Symfony\Component\Debug\BufferingLogger;

class MoodleInstallerTest extends FilesystemTestCase
{
    public function testInstall()
    {
        $dataDir   = $this->tempDir.'/moodledata';
        $moodle    = new DummyMoodle($this->tempDir);
        $installer = new MoodleInstaller(
            new DummyExecute(),
            new MySQLDatabase(),
            $moodle,
            new MoodleConfig(),
            'git@github.com:moodle/moodle.git',
            'MOODLE_27_STABLE',
            $dataDir
        );
        $installer->install();

        $this->assertSame($installer->stepCount(), $installer->getOutput()->getStepCount());

        $this->assertTrue(is_dir($dataDir));
        $this->assertTrue(is_dir($dataDir.'/phpu_moodledata'));
        $this->assertTrue(is_dir($dataDir.'/behat_moodledata'));
        $this->assertTrue(is_file($this->tempDir.'/config.php'));
        $this->assertSame($this->tempDir, $moodle->directory, 'Moodle directory should be absolute path after install');
        $this->assertSame(['MOODLE_DIR' => $this->tempDir], $installer->getEnv());
    }

    public function testInstallNoClone()
    {
        $dataDir   = $this->tempDir.'/moodledata';
        $moodle    = new DummyMoodle($this->tempDir);
        $installer = new MoodleInstaller(
            new DummyExecute(),
            new MySQLDatabase(),
            $moodle,
            new MoodleConfig(),
            null,
            null,
            $dataDir
        );

        $logger = new BufferingLogger();
        $installer->setOutput(new InstallOutput($logger));
        $installer->install();
        $logs = $logger->cleanLogs();
        $this->assertSame(['info', 'Using local Moodle setup', []], $logs[0], '"Using local Moodle setup" should be in the output');

        $this->assertSame($installer->stepCount(), $installer->getOutput()->getStepCount());

        $this->assertTrue(is_dir($dataDir));
        $this->assertTrue(is_dir($dataDir.'/phpu_moodledata'));
        $this->assertTrue(is_dir($dataDir.'/behat_moodledata'));
        $this->assertTrue(is_file($this->tempDir.'/config.php'));
        $this->assertSame($this->tempDir, $moodle->directory, 'Moodle directory should be absolute path after install');
        $this->assertSame(['MOODLE_DIR' => $this->tempDir], $installer->getEnv());
    }
}
