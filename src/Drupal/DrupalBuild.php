<?php
/**
 * @file
 * Provides data about all Drupal 7 site builds.
 */

namespace Robo\Drupal;

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
     *   Drupal's default theme.
     */
    public static $drupalDefaultTheme = 'bartik';
}
