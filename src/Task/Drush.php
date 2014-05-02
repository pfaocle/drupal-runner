<?php
/**
 * @file
 * Run Drush commands as a Robo Task.
 */

namespace Robo\Task;

use Robo\Output;
use Robo\Result;
use Robo\Task\Shared\TaskInterface;
use Robo\Task\Shared\DynamicConfig;
use Robo\Task\Exec;

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

/**
 * Class DrushTask.
 *
 * @package Robo
 */
class DrushTask implements TaskInterface
{
    use DynamicConfig;
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
     *   Whether to force the command with -f. Note setting of this property is handled by DynamicConfig and we set
     *   an initial value here to force the type as well as provide a default.
     *
     * @see Robo\Task\DynamicConfig
     */
    protected $force = false;

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
     * Run the Drush command.
     *
     * @return Result
     *   Result data.
     *
     * @throws Shared\TaskException
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
            throw new Shared\TaskException($this, "The Drush command $shortCmd was not successful.");
        }

        return Result::success($this, "Ran Drush command: " . $this->command);
    }
}
