<?php
/**
 * @file
 * DrupalRunner - an extension to Robo for building Drupal.
 */

namespace Robo;

use Robo\Drupal\DrupalBuild;

/**
 * Class DrupalRunner.
 *
 * @package Robo
 */
class DrupalRunner extends Tasks
{
    use Drush;

    /**
     * @var \Robo\Drupal\DrupalBuild
     *   Stores the current build.
     */
    protected $build;

    /**
     * Every task should run this first to establish we're good to run before any tasks are executed.
     */
    protected function init()
    {
        if (!$this->build) {
            $this->build = new DrupalBuild($this);
        }
    }

    /**
     * Runs everything, from nuking the target directory through to working site.
     *
     * @desc Run a complete rebuild
     *
     * @param $path
     *   An absolute file path to the target build directory.
     */
    public function drupalMagic($path)
    {
        $this->drupalBuild($path);
        $this->drupalInstall();
        $this->drupalPre();
        $this->drupalFeatures();
        $this->drupalTheme();
        $this->drupalMigrate();
        $this->drupalPost();
        $this->drupalCleanup();
    }

    /**
     * Step 0: build. Clone the Git repository and run drush make.
     *
     * @desc Build the site [0]
     *
     * @param $path
     *   An absolute file path to the target build directory.
     *
     * @throws \Exception
     */
    public function drupalBuild($path)
    {
        $this->init();

        if (!file_exists($path)) {
            throw new \Exception("Target directory $path does not exist.");
        }
        $this->build->path = $path;

        // Load build configuration.
        $buildConfig = $this->build->config('Build');

        // If the sites subdirectory exists, it may have no write permissions for any user.
        $this->taskExec("cd {$this->build->path()} && chmod u+w sites/{$buildConfig['sites-subdir']}")->run();

        // Empty the build directory.
        $this->taskExec(
            "cd {$this->build->path()} && rm -Rf *"
        )->run();
        $this->taskExec(
            "cd {$this->build->path()} && rm -f " . implode(' ', DrupalBuild::$drupalHiddenFiles)
        )->run();

        // @todo This errors:
//        $this->taskCleanDir([
//            '.'
//        ])->run();

        // Clone the Git repository.
        $this->taskExec(
            "git clone {$buildConfig['git']} {$this->build->path('sites/' . $buildConfig['sites-subdir'])}"
        )->run();

        // Drush make.
        //
        // Note that we need to change directory here, so don't wrap the path to make file in a call to path(). We also
        // avoid using $this->drush() as currently this is run on the host machine.
        $this->taskExec(
            "cd {$this->build->path()} && drush -y make sites/{$buildConfig['sites-subdir']}/{$buildConfig['make']} ."
        )->run();

        $this->build->writeSitesPhpFile();
    }

    /**
     * Step 1: install.
     *
     * @desc Install the site profile [1]
     */
    public function drupalInstall()
    {
        $this->init();
        $config = $this->build->config();
        $site = $config['Site'];
        $db = $config['Database'];

        // Site install.
        $cmd = "site-install {$config['Build']['profile']} \\
                    --db-url=mysql://{$db['user']}:{$db['password']}@localhost/{$db['name']} \\
                    --sites-subdir={$config['Build']['sites-subdir']} \\
                    --site-name=\"{$site['name']}\" \\
                    --account-name={$site['rootuser']} \\
                    --account-pass={$site['rootpassword']}";

        $this->taskDrushCommand($cmd, $this->build)
            ->force()
            ->run();

        $this->build->writeEnvironmentSettings();
    }


    /**
     * Step 2: pre-steps. Run Drush commands and enable modules - steps that should happen before anything else.
     *
     * @desc Pre-steps [2]
     */
    public function drupalPre()
    {
        $this->init();
        $this->runSteps('Pre');
    }

    /**
     * Step 3: features.
     *
     * @desc Enable features [3]
     */
    public function drupalFeatures()
    {
        $this->init();
        foreach ($this->build->config('Features') as $feature) {
            $this->taskDrushCommand("en $feature", $this->build)
                ->force()
                ->run();
        }
    }

    /**
     * Step 4: theme. Enable theme and disable the Drupal default.
     *
     * @desc Enable theme [4]
     */
    public function drupalTheme()
    {
        $this->init();

        // Enable theme, if set.
        $siteConfig = $this->build->config('Site');
        if (isset($siteConfig['theme'])) {
            $this->taskDrushCommand("en {$siteConfig['theme']}", $this->build)
                ->force()
                ->run();
            $this->taskDrushCommand("vset theme_default {$siteConfig['theme']}")
                ->run();
            $this->taskDrushCommand('dis ' . DrupalBuild::$drupalDefaultTheme, $this->build)
                ->force()
                ->run();
        }
    }

    /**
     * Step 5: migration.
     *
     * @desc Run data migration [5]
     */
    public function drupalMigrate()
    {
        $this->init();
        $migrateConfig = $this->build->config('Migrate');

        if (!empty($migrateConfig)) {
            // We assume we'll want both Migrate UI and Migrate modules.
            $this->taskDrushCommand('en migrate_ui', $this->build)
                ->force()
                ->run();
            if (isset($migrateConfig['Source']['Files'])) {
                $cmd = "vset {$migrateConfig['Source']['Files']['variable']} \\
                        \"{$migrateConfig['Source']['Files']['dir']}\"";
                $this->taskDrushCommand($cmd, $this->build)
                    ->force()
                    ->run();
            }

            if (isset($migrateConfig['Dependencies'])) {
                foreach ($migrateConfig['Dependencies'] as $dependency) {
                    $this->taskDrushCommand("en $dependency", $this->build)
                        ->force()
                        ->run();
                }
            }

            if (isset($migrateConfig['Groups'])) {
                foreach ($migrateConfig['Groups'] as $group) {
                    $this->taskDrushCommand("mi --group=$group", $this->build)
                        ->force()
                        ->run();
                }
            }

            if (isset($migrateConfig['Migrations'])) {
                foreach ($migrateConfig['Migrations'] as $migration) {
                    $this->taskDrushCommand("mi $migration", $this->build)
                        ->force()
                        ->run();
                }
            }
        }
    }

    /**
     * Step 6: post-steps. Drush commands to run and modules to enable after everything else, but before clean-up.
     *
     * @desc Post-steps [6]
     */
    public function drupalPost()
    {
        $this->init();
        $this->runSteps('Post');
    }

    /**
     * Step 7: cleanup.
     *
     * @desc Clean up unwanted files [7]
     */
    public function drupalCleanup()
    {
        $this->init();
        // Remove unwanted files.
        foreach (DrupalBuild::$unwantedFilesPatterns as $pattern) {
            $this->taskExec("rm -R {$this->build->path($pattern)}")->run();
        }

        // Revert features and clear caches.
        $featuresConfig = $this->build->config('Features');
        if (!empty($featuresConfig)) {
            $this->taskDrushCommand('fra', $this->build)
                ->force()
                ->run();
        }
        $this->taskDrushCommand('cc all', $this->build)
            ->force()
            ->run();
    }

    /**
     * Helper function for pre- and post-steps.
     *
     * @param string $type
     *   The type of steps to run, either 'Pre' or 'Post'.
     */
    protected function runSteps($type)
    {
        if ('Pre' == $type || 'Post' == $type) {
            $stepsConfig = $this->build->config($type);

            foreach (array('Modules', 'Commands') as $section) {
                if (isset($stepsConfig[$section])) {
                    foreach ($stepsConfig[$section] as $arg) {
                        $cmd = ('Modules' == $section ? 'en ' : '') . $arg;
                        $this->taskDrushCommand($cmd, $this->build)
                            ->force()
                            ->run();
                    }
                }
            }
        }
    }

    /**
     * Allow a DrupalBuild instance to run some protected DrupalRunner (Robo) methods.
     *
     * @param string $task
     *   The Robo task name. Can be one of 'Exec' or 'ReplaceInFile'.
     * @param array $arg
     *   A single argument to pass to the task.
     *
     * @return \Robo\Task\TaskInterface
     *   Instance implementing TaskInterface from the Robo task.
     *
     * @throws \Exception
     */
    public function roboTask($task, $arg)
    {
        $allowedTasks = array(
            'Exec' => 1,
            'ReplaceInFile' => 1,
        );

        if (array_key_exists($task, $allowedTasks)) {
            $methodName = 'task' . $task;
            if (method_exists($this, $methodName)) {
                // @todo Better argument handling.
                if (empty($arg)) {
                    return $this->$methodName();
                } else {
                    return $this->$methodName($arg);
                }
            } else {
                throw new \Exception(sprintf('Method %s was not found in class %s', $methodName, __CLASS__));
            }
        } else {
            throw new \Exception(sprintf('The task %s is not permitted.', $task));
        }
    }
}
