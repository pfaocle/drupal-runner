<?php
/**
 * @file
 * DrupalRunner - an extension to Robo for building Drupal.
 */

namespace Robo;

use Boedah\Robo\Task\Drush;
use Robo\Drupal\DrupalBuild;
use Robo\Task\Shared\TaskException;

/**
 * Class DrupalRunner.
 *
 * @package Robo
 */
class DrupalRunner extends Tasks
{
    use Drush;

    /**
     * Default Git remote to use.
     */
    const DEFAULT_GIT_REMOTE = 'origin';

    /**
     * String used to identify when the Git working directory is clean.
     */
    const GIT_CLEAN_MSG = 'nothing to commit, working directory clean';

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
            $this->build = new DrupalBuild();
        }
    }

    /**
     * Runs everything, from nuking the target directory through to working site.
     *
     * @desc Run a complete rebuild
     *
     * @param $path
     *   An absolute file path to the target build directory.
     * @param array $opts
     *   Array of command-line options and default values.
     */
    public function drupalMagic($path, $opts = ['nuke' => false])
    {
        $this->drupalBuild($path, $opts);
        $this->drupalMake();
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
     * @param array $opts
     *   An array of options (and default values) passed to the command.
     *
     * @throws \Exception
     */
    public function drupalBuild($path, $opts = ['nuke' => false])
    {
        $this->init();

        if (!file_exists($path)) {
            throw new \Exception("Target directory $path does not exist.");
        }
        $this->build->path = $path;

        $buildConfig = $this->build->config('Build');
        $sitesSubdir = 'sites/' . $buildConfig['sites-subdir'];

        if ($opts['nuke']) {
            // If we're actually running within the directory we've been asked to nuke, things will most certainly go
            // awry. Check this and throw an exception if this is the case.
            if (strpos(getcwd(), realpath($this->build->path())) !== false) {
                throw new TaskException(__CLASS__, "You cannot use --nuke from a build within the target directory.");
            }

            // Perform a few checks on the local repository - if we're in a state where the user is likely to lose local
            // changes, given them the opportunity to quit.
            //
            // We assume a remote named GIT_REMOTE ('origin') by not passing anything as the second parameter. This is
            // currently acceptable as we're cloning the repository afresh each time and the remote will be named
            // 'origin'.
            $this->checkLocalGit($this->build->path($sitesSubdir));

            // If we're this far, the user is OK with us emptying target directory and continuing.
            $this->build->cleanBuildDirectory();

            // Clone the Git repository.
            $this->taskGitStack()
                ->cloneRepo($buildConfig['git'], $this->build->path($sitesSubdir))
                ->run();
        }

        if (!file_exists($this->build->path('sites/sites.php'))) {
            $this->say('Writing new sites.php file.');
            $this->build->writeSitesPhpFile();
        }
    }

    /**
     * Step 1: Drush Make
     *
     * @desc Run Drush Make [1]
     */
    public function drupalMake()
    {
        $this->init();
        $buildConfig = $this->build->config('Build');

        // Build file can specify a different location for the make file
        // if not in the usual sites/sitename dir.
        if (!isset($buildConfig['make-path'])) {
            $path = "sites/{$buildConfig['sites-subdir']}";
        } else {
            $path = $buildConfig['make-path'];
        }

        // Note that we need to change directory here, so don't wrap the path to make file in a call to path(). We also
        // avoid using $this->drush() as currently this is run on the host machine.
        $this->taskExec(
            "cd {$this->build->path()} && drush -y make $path/{$buildConfig['make']} ."
        )->run();
    }

    /**
     * Step 2: install.
     *
     * @desc Install the site profile [2]
     */
    public function drupalInstall()
    {
        $this->init();
        $config = $this->build->config();
        $site = $config['Site'];
        $db = $config['Database'];

        // For database builds, we install Drupal core using the minimal profile (to handle settings.php etc), then
        // import the source database.
        if ($installDb = $this->build->getConfig('Build', 'install-db')) {
            if (!file_exists($installDb)) {
                throw new TaskException(
                    __CLASS__,
                    sprintf('Source database dump %s was not be found.', $installDb)
                );
            }
            $config['Build']['profile'] = 'minimal';
        }

        // Site install.
        $this->taskDrushStack()
            ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
            ->dbUrl("mysql://{$db['user']}:{$db['password']}@localhost/{$db['name']}")
            ->sitesSubdir($config['Build']['sites-subdir'])
            ->siteName($site['name'])
            ->accountName($site['rootuser'])
            ->accountPass($site['rootpassword'])
            ->siteInstall($config['Build']['profile'])
            ->run();

        if ($installDb) {
            // @todo Backup DB?

            // Import DB. Note we drop the entire database here, as there could be tables in the minimal install
            // that aren't present in the imported SQL (nor are there any DROP TABLE x IF EXISTS... statements).
            $this->taskDrushStack()
                ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                ->exec('sql-drop')
                ->exec('sql-cli < ' . $installDb)
                ->run();
        }

        $this->build->writeEnvironmentSettings();
    }


    /**
     * Step 3: pre-steps. Run Drush commands and enable modules - steps that should happen before anything else.
     *
     * @desc Pre-steps [3]
     */
    public function drupalPre()
    {
        $this->init();
        $this->runSteps('Pre');
    }

    /**
     * Step 4: features.
     *
     * @desc Enable features [4]
     */
    public function drupalFeatures()
    {
        $this->init();
        $this->enableModuleList($this->build->config('Features'));
    }

    /**
     * Step 5: theme. Enable theme and disable the Drupal default.
     *
     * @desc Enable theme [5]
     */
    public function drupalTheme()
    {
        $this->init();

        // Enable theme, if set.
        if ($this->build->getConfig('site', 'theme', true)) {
            $this->taskDrushStack()
                ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                ->exec('en ' . $this->build->getConfig('site', 'theme', true))
                ->exec('vset theme_default  ' . $this->build->getConfig('site', 'theme', true))
                ->exec('dis ' . DrupalBuild::$drupalDefaultTheme)
                ->run();
        }
    }

    /**
     * Step 6: migration.
     *
     * @desc Run data migration [6]
     */
    public function drupalMigrate()
    {
        $this->init();
        $migrateConfig = $this->build->config('Migrate');

        if (!empty($migrateConfig)) {
            // We assume we'll want both Migrate UI and Migrate modules.
            $this->taskDrushStack()
                ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                ->exec('en migrate_ui')
                ->run();

            if (isset($migrateConfig['Source']['Files'])) {
                $cmd = "vset {$migrateConfig['Source']['Files']['variable']} \\
                        \"{$migrateConfig['Source']['Files']['dir']}\"";
                $this->taskDrushStack()
                    ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                    ->exec($cmd)
                    ->run();

            }

            if (isset($migrateConfig['Dependencies'])) {
                foreach ($migrateConfig['Dependencies'] as $dependency) {
                    $this->taskDrushStack()
                        ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                        ->exec("en $dependency")
                        ->run();
                }
            }

            if (isset($migrateConfig['Groups'])) {
                foreach ($migrateConfig['Groups'] as $group) {
                    $this->taskDrushStack()
                        ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                        ->exec("mi --group=$group")
                        ->run();
                }
            }

            if (isset($migrateConfig['Migrations'])) {
                foreach ($migrateConfig['Migrations'] as $migration) {
                    $this->taskDrushStack()
                        ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                        ->exec("mi $migration")
                        ->run();
                }
            }
        }
    }

    /**
     * Step 7: post-steps. Drush commands to run and modules to enable after everything else, but before clean-up.
     *
     * @desc Post-steps [7]
     */
    public function drupalPost()
    {
        $this->init();
        $this->runSteps('Post');
    }

    /**
     * Step 8: cleanup.
     *
     * @desc Clean up unwanted files [8]
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
            $this->taskDrushStack()
                ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                ->revertAllFeatures()
                ->run();
        }
        $this->taskDrushStack()
            ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
            ->clearCache()
            ->run();
    }

    /**
     * Do some quick checks on the local repository before proceeding.
     *
     * Warn the user if their Git repository is dirty or contains changes not yet pushed to the (default) remote.
     *
     * @param string $repositoryPath
     *   Absolute path to the Git repository to check.
     * @param string $remote
     *   Name of the remote to check against.
     */
    protected function checkLocalGit($repositoryPath, $remote = self::DEFAULT_GIT_REMOTE)
    {
        $ret = $this->taskExec("cd $repositoryPath && git status")->run();
        if (!strpos($ret->getMessage(), self::GIT_CLEAN_MSG)) {
            $this->askContinueQuestion(
                'Working directory not clean. Continuing will result in these changes being lost.',
                'working directory not clean'
            );
        }

        // If comparing the local branch to remote with cherry returns something other than an empty message, we have
        // local changes not pushed to remote yet.
        //
        // Note that this should also cover the case where we're on a local branch that hasn't been pushed at all,
        // PROVIDING there are commits on the local branch. `git cherry [REMOTE]` still returns a list of local-only
        // commits even without an upstream copy of the branch. 'Empty' local branches will be lost.
        $ret = $this->taskExec("cd $repositoryPath && git cherry $remote")->run();
        if ($ret->getMessage() != null) {
            $this->askContinueQuestion(
                "You have local changes that haven't yet been published to a remote repository." .
                'Continuing will result in these changes being lost.',
                'unpublished local changes'
            );
        }
    }

    /**
     * Helper function to ask a 'Do you want to continue?' question.
     *
     * @param string $question
     *   The question to present the user.
     * @param string $exitMessage
     *   A response to include in the output when not continuing.
     */
    protected function askContinueQuestion($question, $exitMessage)
    {
        $answer = $this->ask($question . ' Do you want to continue (y/n)?');
        if ($answer != 'Y' && $answer != 'y') {
            $this->printTaskInfo(sprintf('Cancelled by user: %s', $exitMessage));
            exit(1);
        }
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
            if (isset($stepsConfig["Commands"])) {
                foreach ($stepsConfig["Commands"] as $cmd) {
                    $this->taskDrushStack()
                        ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                        ->exec($cmd)
                        ->run();
                }
            }
            if (isset($stepsConfig['Modules'])) {
                $this->enableModuleList($stepsConfig['Modules']);
            }
        }
    }

    /**
     * Helper function to enable a list of modules/themes/features.
     *
     * @param array $list
     *   An nested array of things to enable. If the item is a string, enable it on it's own. If the item is an array,
     *   implode it and enable them all at once.
     */
    protected function enableModuleList($list)
    {
        foreach ($list as $item) {
            if (is_array($item)) {
                $list = implode(' ', $item);
            } else {
                $list = $item;
            }
            $this->taskDrushStack()
                ->siteAlias($this->build->getConfig('build', 'drush_alias', true))
                ->exec("en $list")
                ->run();
        }
    }
}
