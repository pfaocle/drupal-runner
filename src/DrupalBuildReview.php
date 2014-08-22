<?php

namespace Robo;

use Boedah\Robo\Task\Drush;
use Robo\Drupal\DrupalBuild;
use Robo\Task\Shared\TaskException;

/**
 * Trait to provide some basic 'build review' methods to Drupal Runner.
 *
 * @package Robo
 *
 * @method void init()
 * @method Result taskDrushStack()
 * @method void say()
 */
trait DrupalBuildReview
{
    /**
     * Provide a command to review the current build.
     *
     * Note: this does not (currently) require knowing the physical location of the site's webroot.
     */
    public function buildReview()
    {
        $this->init();
        $this->enabledFeaturesCheck($this->build);
    }

    /**
     * Build review: compare build-defined features vs. the current build (via Drush alias).
     *
     * @param DrupalBuild $build
     *   The build definition to use.
     */
    protected function enabledFeaturesCheck($build)
    {
        // Get the build-defined features.
        $features = $build->config('Features');

        /** @var Result $test */
        $test = $this->taskDrushStack()
            ->siteAlias($build->getConfig('Build', 'drush-alias'))
            ->exec("pm-list --format=json")
            ->run();

        // Get an associative array of all modules, features and themes.
        $modules = json_decode($test->getMessage(), true);

        // Filter out disabled items.
        $modules = array_filter($modules, function ($item) {
            return $item['status'] == 'Enabled';
        });

        // Filter out any already enabled features from the build-defined list.
        $disabledFeatures = array_filter($features, function ($item) use ($modules) {
            return !array_key_exists($item, $modules);
        });

        if (!empty($disabledFeatures)) {
            foreach ($disabledFeatures as $feature) {
                $this->say(sprintf("%s is not enabled in your build, and should be.", $feature));
            }
        }
    }
}
