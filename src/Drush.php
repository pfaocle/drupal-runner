<?php
/**
 * @file
 * Drush trait for Robo task.
 */

namespace Robo;

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
     *
     * @return DrushTask
     *   A new DrushTask object ready to run.
     */
    function taskDrushCommand($command)
    {
        return new DrushTask($command);
    }
}
