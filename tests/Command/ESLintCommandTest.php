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

namespace MoodlePluginCI\Tests\Command;

use MoodlePluginCI\Command\ESLintCommand;
use MoodlePluginCI\Process\Execute;
use MoodlePluginCI\Tests\Fake\Bridge\DummyMoodle;
use MoodlePluginCI\Tests\Fake\Process\DummyExecute;
use MoodlePluginCI\Tests\MoodleTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class ESLintCommandTest extends MoodleTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $helper = new ProcessHelper();
        $helper->setHelperSet(new HelperSet([new DebugFormatterHelper()]));
        $execute = new Execute(new NullOutput(), $helper);
        $execute->mustRun(new Process('npm install eslint --no-progress', $this->moodleDir, null, null, null));
    }

    protected function executeCommand($pluginDir = null, $moodleDir = null)
    {
        if ($pluginDir === null) {
            $pluginDir = $this->pluginDir;
        }
        if ($moodleDir === null) {
            $moodleDir = $this->moodleDir;
        }

        $command          = new ESLintCommand();
        $command->moodle  = new DummyMoodle($moodleDir);
        $command->execute = new DummyExecute();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('eslint'));
        $commandTester->execute([
            'plugin'         => $pluginDir,
            '--moodle'       => $moodleDir,
        ]);

        return $commandTester;
    }

    public function testExecute()
    {
        $commandTester = $this->executeCommand();
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteNoPlugin()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->executeCommand($this->moodleDir.'/no/plugin');
    }

    public function testExecuteNoMoodle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->executeCommand($this->moodleDir.'/no/moodle');
    }
}
