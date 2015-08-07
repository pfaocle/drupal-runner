<?php

namespace Robo\Drupal;

/**
 * Defines data and processes for a Drupal 7 site.
 */
class DrupalSite
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
}
