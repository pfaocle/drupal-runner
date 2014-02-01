<?php
/**
 * @file
 * DrupalRunner - an extension to Robo for building Drupal.
 */

namespace Robo;

use Robo\Drupal\DrupalBuild;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DrupalRunner.
 *
 * @package Robo
 */
class DrupalRunner extends \Robo\Tasks
{
    /**
     * @var string
     *   An absolute path to the directory in which to build.
     */
    protected $buildPath;

    /**
     * @var array
     *   Stores the build configuration.
     */
    protected $config = array();

    /**
     * @var bool
     *   Whether we've already initialised a run.
     */
    protected $initialised;

    /**
     * Every task should run this first to establish we're good to run before any tasks are executed.
     */
    protected function init()
    {
        if (!$this->initialised) {
            $this->config();
            $this->initialised = true;
        }
    }

    /**
     * Runs everything, from nuking the target directory through to working site.
     *
     * @desc Run a complete rebuild
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
     *   A full path to the target build directory.
     *
     * @throws \Exception
     */
    public function drupalBuild($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Target directory $path does not exist.");
        }
        $this->buildPath = $path;

        // Load configuration.
        $config = $this->config();

        // Empty the build directory.
        $this->taskExec(
            "cd {$this->path()} && rm -Rf *"
        )->run();
        $this->taskExec(
            "cd {$this->path()} && rm -f " . implode(' ', DrupalBuild::$drupalHiddenFiles)
        )->run();

        // @todo This errors:
//        $this->taskCleanDir([
//            '.'
//        ])->run();

        // Clone the Git repository.
        $this->taskExec(
            "git clone {$config['Build']['git']} {$this->path('sites/' . $config['Build']['sites-subdir'])}"
        )->run();

        // Drush make.
        //
        // Note that we need to change directory here, so don't wrap the path to make file in a call to path(). We also
        // avoid using $this->drush() as currently this is run on the host machine.
        $this->taskExec(
            "cd {$this->path()} && drush -y make sites/{$config['Build']['sites-subdir']}/{$config['Build']['make']} ."
        )->run();

        // Write $sites.php file.
        $sitesFile = "<?php\n";
        foreach ($config['Build']['sites'] as $site) {
            $sitesFile .= "  \$sites['$site'] = '{$config['Build']['sites-subdir']}';\n";
        }
        file_put_contents($this->path('sites/sites.php'), $sitesFile);
    }

    /**
     * Step 1: install.
     *
     * @desc Install the site profile [1]
     */
    public function drupalInstall()
    {
        $config = $this->config();
        $site = $config['Site'];
        $db = $config['Database'];

        // Site install.
        $cmd = "site-install {$config['Build']['profile']} \\
                    --db-url=mysql://{$db['user']}:{$db['password']}@localhost/{$db['name']} \\
                    --sites-subdir={$config['Build']['sites-subdir']} \\
                    --site-name=\"{$site['name']}\" \\
                    --account-name={$site['rootuser']} \\
                    --account-pass={$site['rootpassword']}";
        $this->drush($cmd);

        // Include settings.$env.php
        $env = 'local';
        $envSettings = <<<EOS

// Include environment specific settings.
if (file_exists(conf_path() . '/settings.$env.php')) {
  include_once 'settings.$env.php';
}
EOS;

        // Write the inclusion of environment specific configuration to main settings.php file.
        $settingsFile = "sites/{$config['Build']['sites-subdir']}/settings.php";
        $this->taskExec("chmod u+w {$this->path($settingsFile)}")->run();
        file_put_contents(
            $this->path($settingsFile),
            $envSettings,
            FILE_APPEND
        );
        $this->taskExec("chmod u-w {$this->path($settingsFile)}")->run();
    }


    /**
     * Step 2: pre-steps. Run Drush commands and enable modules - steps that should happen before anything else.
     *
     * @desc Pre-steps [2]
     */
    public function drupalPre()
    {
        $this->runSteps('Pre');
    }

    /**
     * Step 3: features.
     *
     * @desc Enable features [3]
     */
    public function drupalFeatures()
    {
        $config = $this->config();
        foreach ($config['Features'] as $feature) {
            $this->drush("en $feature");
        }
    }

    /**
     * Step 4: theme. Enable theme and disable the Drupal default.
     *
     * @desc Enable theme [4]
     */
    public function drupalTheme()
    {
        // Enable theme, if set.
        $config = $this->config();
        $this->drush("en {$config['Site']['theme']}");
        $this->drush("vset theme_default {$config['Site']['theme']}", false);
        $this->drush('dis ' . DrupalBuild::$drupalDefaultTheme);
    }

    /**
     * Step 5: migration.
     *
     * @desc Run data migration [5]
     */
    public function drupalMigrate()
    {
        $this->drush('en migrate_ui');

        $config = $this->config();
        $config = $config['Migrate'];

        if (isset($config['Source']['Files'])) {
            $this->drush(
                "vset {$config['Source']['Files']['variable']} \\
                    \"{$config['Source']['Files']['dir']}\""
            );
        }

        foreach ($config['Dependencies'] as $dependency) {
            $this->drush("en $dependency");
        }

        foreach ($config['Groups'] as $group) {
            $this->drush("mi --group=$group");
        }

        foreach ($config['Migrations'] as $migration) {
            $this->drush("mi $migration");
        }
    }

    /**
     * Step 6: post-steps. Drush ommands to run and modules to enable after everything else, but before clean-up.
     *
     * @desc Post-steps [6]
     */
    public function drupalPost()
    {
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
            $this->taskExec("rm -R {$this->path($pattern)}")->run();
        }

        // Revert features and clear caches.
        $this->drush('fra');
        $this->drush('cc all');
    }

    /**
     * Run a Drush command on the remote specified in configuration.
     *
     * @param string $command
     *   Drush command to run, complete with arguments.
     *
     * @param bool $force
     *   Whether to force the command with '-y'.
     */
    protected function drush($command, $force = true)
    {
        $cmd = ($force ? 'drush -y' : 'drush');
        $config = $this->config();
        if (array_key_exists('drush-alias', $config['Build'])) {
            $cmd .= ' ' . $config['Build']['drush-alias'];
        }
        // @todo Where does the output go when using Drush aliases/remotes?
        $this->taskExec($cmd .' ' . $command)->run();
    }

    /**
     * Loads and returns the build configuration.
     *
     * @param bool $refresh
     *   Load in config from file.
     *
     * @return array
     *   Parsed Yaml configuration.
     *
     * @throws \Exception
     */
    protected function config($refresh = false)
    {
        $configFile = getcwd() . '/drupal.build.yml';
        if (!file_exists($configFile)) {
            throw new \Exception('Build configuration could not be found.');
        }
        if ($refresh || empty($this->config)) {
            $this->config = Yaml::parse(file_get_contents($configFile));
        }
        return $this->config;
    }

    /**
     * Returns an absolute path to a given relative one.
     *
     * @param string $path
     *   A path relative to the build directory root. Should not start or end with a /.
     *
     * @return string
     *   The absolute path.
     */
    protected function path($path = '')
    {
        if (empty($path)) {
            return $this->buildPath;
        }
        return $this->buildPath . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Helper function for pre- and post-steps.
     *
     * @param $type
     */
    protected function runSteps($type){
        if ('Pre' == $type || 'Post' == $type) {
            $config = $this->config();
            // Modules.
            foreach ($config[$type]['Modules'] as $module) {
                $this->drush("en $module");
            }
            // Drush commands.
            foreach ($config[$type]['Commands'] as $command) {
                $this->drush($command);
            }
        }
    }
}
