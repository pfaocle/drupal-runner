<?php
/**
 * @file
 * Drush trait for Robo task.
 */

namespace Robo;

use Robo\Drupal\DrupalBuild;

/**
 * Trait Drush.
 *
 * @package Robo
 */
trait Drush
{
    /**
     * Define a task that RoboFile.php can run.
     *
     * @param $command
     *   The Drush command to run.
     * @param DrupalBuild $build
     *   Pass in the current Drupal build to obtain any required configuration.
     *
     * @return DrushTask
     *   A new DrushTask object ready to run.
     */
    function taskDrushCommand($command, DrupalBuild $build)
    {
        return new DrushTask($command, $build);
    }
}
