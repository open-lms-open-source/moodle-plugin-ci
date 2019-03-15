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

namespace MoodlePluginCI\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\ProcessBuilder;

class ESLintCommand extends AbstractMoodleCommand
{
    use ExecuteTrait;

    protected function configure()
    {
        parent::configure();

        $this->setName('eslint')
            ->setDescription('Run eslint')
            ->addOption('max-warnings', null, InputOption::VALUE_REQUIRED,
                'Number of warnings to trigger nonzero exit code - default: -1', -1);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->initializeExecute($output, $this->getHelper('process'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputHeading($output, 'Run eslint on %s');

        $eslint = $this->moodle->directory.'/node_modules/.bin/eslint';
        if (!file_exists($eslint) || !is_executable($eslint)) {
            $output->writeln('<error>Eslint executable not found.</error>');
            return 1;
        }

        $builder = ProcessBuilder::create()
            ->setPrefix($eslint)
            ->add('checkstyle')
            ->add($this->plugin->directory)
            ->setWorkingDirectory($this->moodle->directory)
            ->setTimeout(null);

        if (($maxwarnings = $input->getOption('max-warnings')) >= 0) {
            $builder->add('--max-warnings=' . $maxwarnings);
        }
        if ($output->isDecorated()) {
            $builder->add('--color');
        }
        $process = $this->execute->passThroughProcess($builder->getProcess());

        return $process->isSuccessful() ? 0 : 1;
    }
}
