<?php
/**
 * @file
 * Provides data about all Drupal 7 site builds.
 */

namespace Robo\Drupal;

use Robo\Drupal\Config\BuildConfiguration;
use Robo\Drupal\Config\YamlBuildLoader;
use Robo\Task\Exec;
use Robo\Task\FileSystem;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
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
     * Name of the build configuration file.
     */
    const BUILD_CONFIG_FILE = "drupal.build.yml";

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
     *   Stores the build configuration, as loaded and validated by symfony/config
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
     */
    public function config($section = '', $refresh = false)
    {
        // Load the full configuration from disk if either it's currently empty or we've requested it to be refreshed.
        if ($refresh || empty($this->config)) {
            $this->loadConfig();
        }

        // @todo We've "moved" the old Build key... this needs refactoring once the switch is complete.
        // @todo Also covers the request where $section == "", i.e. get entire config. Needs sorting.
        return ($section === "build" || $section === "") ? $this->config : $this->config[$section];
    }

    /**
     * Loads the build configuration fully into $this->config
     *
     * @throws InvalidConfigurationException
     *   If build configuration doesn't validate
     */
    protected function loadConfig()
    {
        $configDirectories = array(getcwd());
        $locator = new FileLocator($configDirectories);

        // Convert the config file into an array.
        $loader = new YamlBuildLoader($locator);
        $configValues = $loader->load($locator->locate(self::BUILD_CONFIG_FILE));

        // Process the array using the defined configuration.
        $processor = new Processor();
        $configuration = new BuildConfiguration();

        // Configuration, validated. Will throw an InvalidConfigurationException if the configuration is invalid.
        $this->config = $processor->processConfiguration($configuration, $configValues);
    }

    /**
     * Returns an individual piece of build configuration.
     *
     * @param string $section
     *   The section of configuration, e.g. 'Build', 'Site' etc.
     * @param string $key
     *   The key of the configuration element to get.
     *
     * @return mixed
     *   The value of the configuration key, or null if not found.
     */
    public function getConfig($section, $key)
    {
        // @todo We've "moved" the old Build key... this needs refactoring once the switch is complete.
        if ($section == "build") {
            // @todo Check null return value here...
            return isset($this->config[$key]) ? $this->config[$key] : null;
        } else {
            // @todo Check null return value here...
            return isset($this->config[$section][$key]) ? $this->config[$section][$key] : null;
        }
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
        if (isset($this->config['sites']) && count($this->config['sites']) > 0) {
            $sitesFilePath = $this->path('sites/sites.php');

            // @todo Template, or combine these two tasks into one write?
            $this->taskWriteToFile($sitesFilePath)
                ->text("<?php\n  %sites")
                ->run();

            $this->taskReplaceInFile($sitesFilePath)
                ->from('%sites')
                ->to(implode("\n  ", array_map(array($this, 'sitesFileLineCallback'), $this->config['sites'])))
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
        return sprintf(self::$sitesFileLinePattern, $hostnamePattern, $this->config['sites_subdir']);
    }

    /**
     * Write to settings.php to include any environment specific settings files.
     */
    public function writeEnvironmentSettings()
    {
        // Include settings.$env.php
        $env = 'local';
        $envSettings = <<<EOS

// Include environment specific settings.
if (file_exists(conf_path() . '/settings.$env.php')) {
  include_once 'settings.$env.php';
}
EOS;

        // Write the inclusion of environment specific configuration to main settings.php file.
        $settingsFilePath = "sites/{$this->config['sites_subdir']}/settings.php";
        $this->taskExec("chmod u+w {$this->path($settingsFilePath)}")->run();

        $this->taskWriteToFile($this->path($settingsFilePath))
            ->text($envSettings)
            ->append()
            ->run();

        $this->taskExec("chmod u-w {$this->path($settingsFilePath)}")->run();
    }

    /**
     * Empty the build directory completely.
     */
    public function cleanBuildDirectory()
    {
        // If the sites subdirectory exists, it may have no write permissions for any user.
        $sitesSubdirPath = $this->path('sites/' . $this->config['sites_subdir']);
        if (file_exists($sitesSubdirPath)) {
            $this->taskExec("cd {$this->path()} && chmod u+w $sitesSubdirPath")->run();
        }

        // Empty the build directory.
        $this->taskCleanDir([$this->path()])->run();
        $this->taskExec(
            "cd {$this->path()} && rm -f " . implode(' ', self::$drupalHiddenFiles)
        )->run();
    }
}
