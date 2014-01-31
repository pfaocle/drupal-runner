<?php
/**
 * @file
 * DrupalRunner - an extension to Robo for building Drupal.
 */

namespace Robo;

use Symfony\Component\Yaml\Yaml;

/**
 * Class DrupalRunner.
 *
 * @package Robo
 */
class DrupalRunner extends \Robo\Tasks
{
    /**
     * @var array
     *   A list of Drupal's hidden files (to remove).
     */
    protected $drupalHiddenFiles = array('.htaccess', '.gitignore');

    /**
     * @var array
     *   List of file patterns to recursively remove during cleanup.
     */
    protected $unwantedFilesPatterns = array(
        '*txt',
        'install.php',
        'scripts',
        'web.config',
    );

    /**
     * @var string
     *   Drupal's default theme.
     */
    protected $drupalDefaultTheme = 'bartik';

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
     * Runs everything, from nuking the target directory through to working site.
     *
     * @desc Run all the things.
     */
    public function magic($path)
    {
        $this->build($path);
        $this->install();
        $this->pre();
        $this->features();

        // Enable theme, if set.
        $config = $this->config();
        $this->drush("en {$config['Site']['theme']}");
        $this->drush("vset theme_default {$config['Site']['theme']}", false);
        $this->drush("dis {$this->drupalDefaultTheme}");

        $this->migrate();
        $this->post();
        $this->cleanup();
    }

    /**
     * Step 0: build. Clone the Git repository and run drush make.
     *
     * @desc [0] Build the site
     */
    public function build($path)
    {
        // @todo Check if $path exists.
        $this->buildPath = $path;

        // Load configuration.
        $config = $this->config();

        // Empty the build directory.
        $this->taskExec(
            "cd {$this->path()} && rm -Rf *"
        )->run();
        $this->taskExec(
            "cd {$this->path()} && rm -f " . implode(' ', $this->drupalHiddenFiles)
        )->run();

        // @todo This errors:
//        $this->taskCleanDir([
//            '.'
//        ])->run();
sleep(4);
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
    }

    /**
     * Step 1: install.
     *
     * @desc [1] Install the site profile
     */
    public function install()
    {
        $config = $this->config();
        $site = $config['Site'];
        $db = $config['Database'];

        // Site install.
        $cmd = "site-install minimal \\
                    --db-url=mysql://{$db['user']}:{$db['password']}@localhost/{$db['name']} \\
                    --sites-subdir={$config['Build']['sites-subdir']} \\
                    --site-name=\"{$site['name']}\" \\
                    --account-name={$site['rootuser']} \\
                    --account-pass={$site['rootpassword']}";
        $this->drush($cmd);

        // Write the sites.php file.
        $sitesFile = "<?php\n";
        foreach ($config['Build']['sites'] as $site) {
            $sitesFile .= "  \$sites['$site'] = '{$config['Build']['sites-subdir']}';\n";
        }
        file_put_contents($this->path('sites/sites.php'), $sitesFile);

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
     * Step 2: Pre-steps.
     *
     * @desc [2] Pre-steps.
     */
    public function pre()
    {
        $config = $this->config();
        // Modules that need to be enabled before anything else.
        foreach ($config['Pre']['Modules'] as $module) {
            $this->drush("en $module");
        }
        // Pre-commands.
        foreach ($config['Pre']['Commands'] as $command) {
            $this->drush($command);
        }
    }

    /**
     * Step 3: Features.
     *
     * @desc [3] Enable features..
     */
    public function features()
    {
        $config = $this->config();
        foreach ($config['Features'] as $feature) {
            $this->drush("en $feature");
        }
    }

    /**
     * Step 4: Migration.
     *
     * @desc [4] Migration.
     */
    public function migrate()
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
     * Step 5: Post-steps.
     *
     * @desc [5] Post-steps.
     */
    public function post()
    {
        $config = $this->config();
        foreach ($config['Post']['Commands'] as $command) {
            $this->drush($command);
        }
    }

    /**
     * Step 6: cleanup.
     *
     * @desc [6] Cleans uo unwanted files
     */
    public function cleanup()
    {
        // Remove unwanted files.
        foreach ($this->unwantedFilesPatterns as $pattern) {
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
     */
    protected function config($refresh = false)
    {
        if ($refresh || empty($this->config)) {
            $this->config = Yaml::parse(file_get_contents(getcwd() . '/drupal.build.yml'));
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
}
