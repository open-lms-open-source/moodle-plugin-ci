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
use MoodlePluginCI\Bridge\MoodlePluginCollection;
use MoodlePluginCI\Installer\ConfigDumper;
use MoodlePluginCI\Installer\PluginInstaller;
use MoodlePluginCI\Installer\PluginInstallerNoCopy;
use MoodlePluginCI\Tests\Fake\Bridge\DummyMoodle;
use MoodlePluginCI\Tests\FilesystemTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class PluginInstallerTest extends FilesystemTestCase
{
    public function testInstall()
    {
        $fixture   = __DIR__.'/../Fixture/moodle-local_travis';
        $plugin    = new MoodlePlugin($fixture);
        $installer = new PluginInstaller(new DummyMoodle($this->tempDir), $plugin, '', new ConfigDumper());
        $installer->install();

        $this->assertSame($installer->stepCount(), $installer->getOutput()->getStepCount());

        $installDir = $this->tempDir.'/local/travis';

        $this->assertSame($installDir, $plugin->directory, 'Plugin directory should be absolute path after install');
        $this->assertSame(['PLUGIN_DIR' => $installDir], $installer->getEnv());
    }

    public function testInstallPluginIntoMoodle()
    {
        $fixture    = realpath(__DIR__.'/../Fixture/moodle-local_travis');
        $plugin     = new MoodlePlugin($fixture);
        $installer  = new PluginInstaller(new DummyMoodle($this->tempDir), $plugin, '', new ConfigDumper());
        $installDir = $installer->installPluginIntoMoodle($plugin);

        $this->assertTrue(is_dir($installDir));

        $finder = new Finder();
        $finder->files()->in($fixture);

        /* @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $path = str_replace($fixture, $this->tempDir.'/local/travis', $file->getPathname());

            $this->assertFileExists($path);
            $this->assertFileEquals($file->getPathname(), $path);
        }
    }

    public function testInstallPluginIntoMoodleWithNoClone()
    {
        $pluginsnames = [
            'moodle-local_travis'      => 'local/travis',
            'moodle-local_emptyplugin' => 'local/emptyplugin',
        ];

        $toinstallplugins = [];
        $moodle           = new DummyMoodle($this->tempDir);
        foreach ($pluginsnames as $component => $location) {
            // Copy the plugin in the Moodle directory as expected.
            $fixture           = realpath(__DIR__.'/../Fixture/'.$component);
            $directoryInMoodle = $this->tempDir.'/'.$location;
            $filesystem        = new Filesystem();
            $filesystem->mirror($fixture, $directoryInMoodle);
            $toinstallplugins[] = new MoodlePlugin($directoryInMoodle);
        }
        try {
            $installer = new PluginInstallerNoCopy($moodle, new ConfigDumper(), '', array_values($pluginsnames));
            $installer->install();
            foreach ($toinstallplugins as $plugin) {
                $this->assertFileExists($plugin->directory);
            }

            $config = Yaml::parseFile($this->tempDir.'/.moodle-plugin-ci.yml');
            $this->assertSame(['plugins' => ['list' => array_values($pluginsnames)]], $config, 'The dumped config is wrong');
        } finally {
            foreach ($toinstallplugins as $plugin) {
                $filesystem->remove($plugin->directory);
            }
        }
    }

    public function testInstallPluginIntoMoodleAlreadyExists()
    {
        $this->expectException(\RuntimeException::class);

        $this->fs->mkdir($this->tempDir.'/local/travis');

        $fixture   = realpath(__DIR__.'/../Fixture/moodle-local_travis');
        $plugin    = new MoodlePlugin($fixture);
        $installer = new PluginInstaller(new DummyMoodle($this->tempDir), $plugin, '', new ConfigDumper());
        $installer->installPluginIntoMoodle($plugin);
    }

    public function testInstallPluginIntoMoodleWithNoCloneDontExists()
    {
        $this->expectException(\RuntimeException::class);

        $this->fs->remove($this->tempDir.'/local/travis');
        $installer = new PluginInstallerNoCopy(new DummyMoodle($this->tempDir), new ConfigDumper(), '', ['local/travis']);
        $installer->install();
    }

    public function testCreateIgnoreFile()
    {
        $filename = $this->tempDir.'/.moodle-plugin-ci.yml';
        $expected = ['filter' => [
            'notPaths' => ['foo/bar', 'very/bad.php'],
            'notNames' => ['*-m.js', 'bad.php'],
        ]];

        $dumper = new ConfigDumper();
        $dumper->addSection('filter', 'notPaths', ['foo/bar', 'very/bad.php']);
        $dumper->addSection('filter', 'notNames', ['*-m.js', 'bad.php']);

        $installer = new PluginInstaller(new DummyMoodle($this->tempDir), new MoodlePlugin($this->tempDir), '', $dumper);
        $installer->createConfigFile($filename);

        $this->assertFileExists($filename);
        $this->assertSame($expected, Yaml::parse(file_get_contents($filename)));
    }

    public function testScanForPlugins()
    {
        $fixture = __DIR__.'/../Fixture/moodle-local_travis';

        $this->fs->mirror($fixture, $this->tempDir.'/moodle-local_travis');

        $plugin    = new MoodlePlugin($fixture);
        $installer = new PluginInstaller(new DummyMoodle($this->tempDir), $plugin, $this->tempDir, new ConfigDumper());

        $plugins = $installer->scanForPlugins();
        $this->assertInstanceOf(MoodlePluginCollection::class, $plugins);
        $this->assertCount(1, $plugins);
    }

    public function testScanForPluginsNoCopy()
    {
        $extraPluginDirectory = $this->tempDir.'/plugins';
        $this->fs->mkdir($extraPluginDirectory);
        file_put_contents($extraPluginDirectory.'/plugins.txt', implode("\n", ['local/travis']));
        try {
            $fixture = __DIR__.'/../Fixture/moodle-local_travis';
            $this->fs->mirror($fixture, $this->tempDir.'/local/travis');

            $installer = new PluginInstallerNoCopy(new DummyMoodle($this->tempDir), new ConfigDumper(), $extraPluginDirectory);

            $plugins = $installer->scanForPlugins();
            $this->assertInstanceOf(MoodlePluginCollection::class, $plugins);
            $this->assertCount(1, $plugins);
        } finally {
            $this->fs->remove($extraPluginDirectory.'/plugins.txt');
        }
    }
}
