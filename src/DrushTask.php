<?php
/**
 * @file
 * Run Drush commands (in a stack?).
 */

namespace Robo;

use Robo\Output;
use Robo\Drupal\DrupalBuild;
use Robo\Task\Exec;
use Robo\Task\TaskException;

/**
 * Class DrushTask.
 *
 * @package Robo
 */
class DrushTask implements Task\TaskInterface
{
    use Exec;
    use Output;

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
     * @param DrupalBuild $build
     *   Pass in the current Drupal build to obtain any required configuration.
     */
    public function __construct($command, DrupalBuild $build)
    {
        $this->command = $command;

        $buildConfig = $build->config('Build');
        if (array_key_exists('drush-alias', $buildConfig)) {
            $this->alias = $buildConfig['drush-alias'];
        }
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
     *
     * @throws Task\TaskException
     */
    public function run()
    {
        $drushCmd = ($this->force ? 'drush -y' : 'drush');
        if ($this->alias) {
            $drushCmd .= ' ' . $this->alias;
        }

        // @todo Where does the output go when using Drush aliases/remotes?
        $ret = $this->taskExec("$drushCmd $this->command")->run();

        // The above will error and display a message, however we should also check the return status and throw
        // a TaskException to halt the process if the caller doesn't catch and handle it.
        if (!$ret->wasSuccessful()) {
            // Clean up the output a bit...
            $shortCmd = str_replace("\n", '', preg_replace('/\s+/', ' ', $this->command));
            if (strlen($shortCmd) > 50) {
                $shortCmd = substr($shortCmd, 0, 50) . '...';
            }
            throw new TaskException($this, "The Drush command $shortCmd was not successful.");
        }

        return Result::success($this, "Ran Drush command: " . $this->command);
    }
}
