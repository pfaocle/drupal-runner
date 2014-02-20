<?php
/**
 * @file
 * Run Drush commands (in a stack?).
 */

namespace Robo;

/**
 * Class DrushTask.
 *
 * @package Robo
 */
class DrushTask implements Task\TaskInterface
{
    /**
     * @var string
     *   Store the Drush command to be run.
     */
    protected $command;

    /**
     * @var string
     *   Store the alias on which to run the command.
     */
    protected $alias;

    /**
     * @var bool
     *   Whether to force the command with -f
     */
    protected $force;

    /**
     * Constructor.
     *
     * @param $command
     *   Drush command to be run.
     * @param string $alias
     *   Drush alias to use. Optional.
     */
    public function __construct($command, $alias = '')
    {
        $this->command = $command;
        $this->alias = $alias;
    }

    /**
     * Set the command to be forced with -y
     *
     * @return $this
     *   Return this instance for command chaining.
     */
    public function force()
    {
        $this->force = true;
        return $this;
    }

    /**
     * Run the Drush command.
     *
     * @return Result
     *   Result data.
     */
    public function run()
    {
        var_dump('Will run Drush command: ' . $this->command);
        return Result::success($this, "Ran Drush command: " . $this->command);
    }
}
