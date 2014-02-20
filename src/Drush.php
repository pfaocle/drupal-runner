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
     * @param string $alias
     *   Drush alias to use. Optional.
     *
     * @return DrushTask
     *   A new DrushTask object ready to run.
     */
    function taskDrushCommand($command, $alias = '')
    {
        return new DrushTask($command, $alias);
    }
}
