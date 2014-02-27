<?php
/**
 * @file
 * Provides data about all Drupal 7 site builds.
 */

namespace Robo\Drupal;

use Robo\Task\Exec;
use Robo\Task\FileSystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DrupalBuild.
 *
 * @package Robo\Drupal
 */
class DrupalBuild
{
    use Exec;
    use FileSystem;

    /**
     * @var array
     *   A list of Drupal's hidden files (to remove).
     */
    public static $drupalHiddenFiles = array('.htaccess', '.gitignore');

    /**
     * @var array
     *   List of file patterns to recursively remove during cleanup.
     */
    public static $unwantedFilesPatterns = array(
        '*txt',
        'install.php',
        'scripts',
        'web.config',
    );

    /**
     * @var string
     *   The definition of a line/pattern in $sites.php
     */
    public static $sitesFileLinePattern = "\$sites['%s'] = '%s';";

    /**
     * @var string
     *   Drupal's default theme.
     */
    public static $drupalDefaultTheme = 'bartik';

    /**
     * @var \Robo\DrupalRunner
     *   Stores the calling DrupalRunner instance.
     */
    protected $runner;

    /**
     * @var array
     *   Stores the build configuration.
     */
    protected $config;

    /**
     * @var string
     *   An absolute path to the directory in which to build.
     */
    public $path;

    /**
     * Constructor - initialise configuration.
     */
    public function __construct()
    {
        $this->config();
    }

    /**
     * Loads and returns the build configuration.
     *
     * @param string $section
     *   The section of configuration to load/return.
     * @param bool $refresh
     *   Load in config from file.
     *
     * @return array
     *   Parsed YAML configuration for $section, or the full configuration if $section not set.
     *
     * @throws \Exception
     */
    public function config($section = '', $refresh = false)
    {
        $validConfig = array(
            'Build',
            'Site',
            'Database',
            'Pre',
            'Features',
            'Migrate',
            'Post',
        );

        if (!empty($section) && !in_array($section, $validConfig)) {
            throw new \Exception($section . ' is not a valid build configuration section,');
        }

        // Load the full configuration from disk if either it's currently empty or we've requested it to be refreshed.
        if ($refresh || empty($this->config)) {
            $configFile = getcwd() . '/drupal.build.yml';
            if (!file_exists($configFile)) {
                throw new \Exception('Build configuration could not be found.');
            }
            $this->config = Yaml::parse(file_get_contents($configFile));
        }

        if (!empty($section)) {
            if (array_key_exists($section, $this->config)) {
                return $this->config[$section];
            }
        } else {
            return $this->config;
        }

        // Always return an array (for a valid section) and let the caller handle empty configuration.
        return array();
    }

    /**
     * Returns an absolute path to a given relative one.
     *
     * @param string $path
     *   A path relative to the build directory root. Should not start or end with a /
     *
     * @return string
     *   The absolute path.
     */
    public function path($path = '')
    {
        if (empty($path)) {
            return $this->path;
        }
        return $this->path . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Write the sites.php file for this build.
     */
    public function writeSitesPhpFile()
    {
        $buildConfig = $this->config('Build');

        if (isset($buildConfig['sites']) && count($buildConfig['sites']) > 0) {
            $sitesFilePath = $this->path('sites/sites.php');

            // @todo Template, or combine these two tasks into one write?
            $this->taskWriteToFile($sitesFilePath)
                ->text("<?php\n  %sites")
                ->run();

            $this->taskReplaceInFile($sitesFilePath)
                ->from('%sites')
                ->to(implode("\n  ", array_map(array($this, 'sitesFileLineCallback'), $buildConfig['sites'])))
                ->run();
        }
    }

    /**
     * Helper to generate a line for sites.php file.
     *
     * @param string $hostnamePattern
     *   The hostname pattern to map to this build's site subdirectory.
     *
     * @return string
     *   The corresponding line for sites.php
     */
    protected function sitesFileLineCallback($hostnamePattern)
    {
        $buildConfig = $this->config('Build');
        return sprintf(self::$sitesFileLinePattern, $hostnamePattern, $buildConfig['sites-subdir']);
    }

    /**
     * Write to settings.php to include any environment specific settings files.
     */
    public function writeEnvironmentSettings()
    {
        $buildConfig = $this->config('Build');

        // Include settings.$env.php
        $env = 'local';
        $envSettings = <<<EOS

// Include environment specific settings.
if (file_exists(conf_path() . '/settings.$env.php')) {
  include_once 'settings.$env.php';
}
EOS;

        // Write the inclusion of environment specific configuration to main settings.php file.
        $settingsFile = "sites/{$buildConfig['sites-subdir']}/settings.php";
        $this->taskExec("chmod u+w {$this->path($settingsFile)}")->run();

        // @todo The append() magic method requires this change: https://github.com/Codegyre/Robo/pull/11
        $this->taskWriteToFile($this->path($settingsFile))
            ->text($envSettings)
            ->append()
            ->run();

        $this->taskExec("chmod u-w {$this->path($settingsFile)}")->run();
    }

    /**
     * Empty the build directory completely.
     */
    public function cleanBuildDirectory()
    {
        $buildConfig = $this->config('Build');

        // If the sites subdirectory exists, it may have no write permissions for any user.
        $this->taskExec("cd {$this->path()} && chmod u+w sites/{$buildConfig['sites-subdir']}")->run();

        // Empty the build directory.
        // @todo This errors, sometimes:
        //$this->taskCleanDir([$this->path()])->run();
        $this->taskExec(
            "cd {$this->path()} && rm -Rf *"
        )->run();
        $this->taskExec(
            "cd {$this->path()} && rm -f " . implode(' ', self::$drupalHiddenFiles)
        )->run();
    }
}
