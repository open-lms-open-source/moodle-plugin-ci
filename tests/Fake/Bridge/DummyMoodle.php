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

namespace MoodlePluginCI\Tests\Fake\Bridge;

use MoodlePluginCI\Bridge\Moodle;

/**
 * Must override to avoid using Moodle API.
 */
class DummyMoodle extends Moodle
{
    public $branch = 33;

    public function requireConfig()
    {
        // Don't do anything.
    }

    public function normalizeComponent($component)
    {
        // It will not work with all the plugin types.
        return explode('_', $component);
    }

    public function getComponentInstallDirectory($component)
    {
        // This doesn't simulate Moodle's \core_component::fetch_plugintypes nicely.
        return $this->directory.'/'.str_replace('_', '/', $component);
    }

    public function getBranch()
    {
        return $this->branch;
    }
}
