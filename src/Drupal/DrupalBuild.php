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
     * @var array
     *   Stores the new, refactored build configuration from symfony/config (temporary).
     */
    protected $newConfig;

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
     * @param bool $new
     *   Use the new symfony/config based configuration when TRUE.
     *
     * @return array
     *   Parsed YAML configuration for $section, or the full configuration if $section not set.
     *
     * @throws \Exception
     */
    public function config($section = '', $refresh = false, $new = true)
    {
        if (!$new) {
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
        }

        // Load the full configuration from disk if either it's currently empty or we've requested it to be refreshed.
        if ($refresh || empty($this->config)) {
            // Load the NEW symfony/config based configuration.
            $this->loadConfig();
            // Load the OLD configuration.
            $this->loadOldConfig();
        }

        if ($new) {
            // @todo We've "moved" the old Build key... this needs refactoring once the switch is complete.
            // @todo Also covers the request where $section == "", i.e. get entire config. Needs sorting.
            return ($section === "build" || $section === "") ? $this->newConfig : $this->newConfig[$section];
        } else {
            // Old config.
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
    }

    /**
     * Loads the OLD build configuration fully into $this->config
     *
     * @throws \Exception
     */
    protected function loadOldConfig()
    {
        $configFile = getcwd() . '/drupal.build.yml';
        if (!file_exists($configFile)) {
            throw new \Exception('Build configuration could not be found.');
        }
        $this->config = Yaml::parse(file_get_contents($configFile));
    }

    /**
     * Loads the build configuration fully into $this->newConfig
     */
    protected function loadConfig()
    {
        $configDirectories = array(getcwd());
        $locator = new FileLocator($configDirectories);

        // Convert the config file into an array.
        $loader = new YamlBuildLoader($locator);
        $configValues = $loader->load($locator->locate('new.drupal.build.yml'));

        // Process the array using the defined configuration.
        $processor = new Processor();
        $configuration = new BuildConfiguration();
//            try {
        $processedConfiguration = $processor->processConfiguration(
            $configuration,
            $configValues
        );

        // Configuration, validated:
        $this->newConfig = $processedConfiguration;
//            } catch (InvalidConfigurationException $e) {
//                // Validation error.
//                echo $e->getMessage() . PHP_EOL;
//            }
    }

    /**
     * Returns an individual piece of build configuration.
     *
     * @param string $section
     *   The section of configuration, e.g. 'Build', 'Site' etc.
     * @param string $key
     *   The key of the configuration element to get.
     * @param bool $new
     *   Use the new symfony/config based configuration when TRUE.
     *
     * @return mixed
     *   The value of the configuration key, or null if not found.
     */
    public function getConfig($section, $key, $new = true)
    {
        if ($new) {
            // @todo We've "moved" the old Build key... this needs refactoring once the switch is complete.
            if ($section == "build") {
                // @todo Check null return value here...
                return isset($this->newConfig[$key]) ? $this->newConfig[$key] : null;
            } else {
                // @todo Check null return value here...
                return isset($this->newConfig[$section][$key]) ? $this->newConfig[$section][$key] : null;
            }
        } else {
            // NOTE ensure we pass $new = false as the third parameter here, we MUST get the old config.
            $sectionConfig = $this->config($section, false, false);
            return isset($sectionConfig[$key]) ? $sectionConfig[$key] : null;
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
        if (isset($this->newConfig['sites']) && count($this->newConfig['sites']) > 0) {
            $sitesFilePath = $this->path('sites/sites.php');

            // @todo Template, or combine these two tasks into one write?
            $this->taskWriteToFile($sitesFilePath)
                ->text("<?php\n  %sites")
                ->run();

            $this->taskReplaceInFile($sitesFilePath)
                ->from('%sites')
                ->to(implode("\n  ", array_map(array($this, 'sitesFileLineCallback'), $this->newConfig['sites'])))
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
        return sprintf(self::$sitesFileLinePattern, $hostnamePattern, $this->newConfig['sites_subdir']);
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
        $settingsFilePath = "sites/{$this->newConfig['sites_subdir']}/settings.php";
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
        $sitesSubdirPath = $this->path('sites/' . $this->newConfig['sites_subdir']);
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
