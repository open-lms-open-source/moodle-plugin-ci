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

namespace MoodlePluginCI\Tests\Fake\Process;

use MoodlePluginCI\Process\Execute;
use Symfony\Component\Process\Process;

class DummyExecute extends Execute
{
    /**
     * @var string[] histroy of the command to run
     */
    public $history = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
        // Do nothing.
    }

    protected function addToHistory($cmd)
    {
        $this->history[] = $cmd instanceof Process ? $cmd->getCommandLine() : $cmd;
    }

    public function run($cmd, $error = null)
    {
        $this->addToHistory($cmd);

        return new DummyProcess('dummy');
    }

    public function mustRun($cmd, $error = null)
    {
        return $this->run($cmd, $error);
    }

    public function runAll($processes)
    {
        $this->mustRunAll($processes);
    }

    public function mustRunAll($processes)
    {
        foreach ($processes as $process) {
            $this->addToHistory($process);
        }
    }

    public function passThrough($commandline, $cwd = null, $timeout = null)
    {
        return $this->passThroughProcess(new DummyProcess($commandline, $cwd, null, null, $timeout));
    }

    public function passThroughProcess(Process $process)
    {
        if ($process instanceof DummyProcess) {
            return $process;
        }

        return new DummyProcess($process->getCommandLine(), $process->getWorkingDirectory(), null, null, $process->getTimeout());
    }

    /**
     * @return string[]
     */
    public function getHistory()
    {
        return $this->history;
    }
}
