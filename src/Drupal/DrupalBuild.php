<?php
/**
 * @file
 * Provides data about all Drupal 7 site builds.
 */

namespace Robo\Drupal;

use Symfony\Component\Yaml\Yaml;

/**
 * Class DrupalBuild.
 *
 * @package Robo\Drupal
 */
class DrupalBuild
{
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
     * @var array
     *   Stores the build configuration.
     */
    protected $config;

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
}
