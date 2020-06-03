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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Handles output for installation process.
 */
class InstallOutput implements LoggerInterface
{
    /**
     * @var ProgressBar|null
     */
    private $progressBar;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * Number of steps taken.
     *
     * @var int
     */
    private $stepCount = 0;

    public function __construct(LoggerInterface $logger = null, ProgressBar $progressBar = null)
    {
        $this->progressBar = $progressBar;

        // Ignore logger completely when we have a progress bar.
        if (!$this->progressBar instanceof ProgressBar) {
            $this->logger = $logger;
        }
    }

    /**
     * Get the number of steps taken.
     *
     * @return int
     */
    public function getStepCount()
    {
        return $this->stepCount;
    }

    /**
     * Starting the install process.
     *
     * @param string $message  Start message
     * @param int    $maxSteps The number of steps that will be taken
     */
    public function start($message, $maxSteps)
    {
        $this->info($message);

        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->start($maxSteps);
        }
    }

    /**
     * Signify the move to the next step in the install.
     *
     * @param string $message Very short message about the step
     */
    public function step($message)
    {
        ++$this->stepCount;

        $this->info($message);

        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->advance();
        }
    }

    /**
     * Ending the install process.
     *
     * @param string $message End message
     */
    public function end($message)
    {
        $this->info($message);

        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->finish();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message, $context);
        }
    }
}
