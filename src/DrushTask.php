<?php
/**
 * @file
 * Run Drush commands (in a stack?).
 */

namespace Robo;

use Robo\Task\Exec;

/**
 * Class DrushTask.
 *
 * @package Robo
 */
class DrushTask implements Task\TaskInterface
{
    use Exec;

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
        $drushCmd = ($this->force ? 'drush -y' : 'drush');
        if ($this->alias) {
            $drushCmd .= ' ' . $this->alias;
        }

        // @todo Where does the output go when using Drush aliases/remotes?
        // @todo Look at $ret; handle failures (see old drush method).
        $ret = $this->taskExec("$drushCmd $this->command")->run();
        // @todo Proper return Result.
        return Result::success($this, "Ran Drush command: " . $this->command);
    }
}
