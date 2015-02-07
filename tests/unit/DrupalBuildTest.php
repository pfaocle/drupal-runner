<?php

use \Robo\Drupal\DrupalBuild;

/**
 * Test the DrupalBuild class.
 */
class DrupalBuildTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     *   The Tester object being used to test.
     */
    protected $tester;

    /**
     * Instantiating this class currently tries to load configuration, which will throw an exception here.
     *
     * @test
     */
    public function seeExpectedExceptionAsConfigCannotBeRead()
    {
        $this->setExpectedException("Exception", "Build configuration could not be found.");
        new DrupalBuild();
    }
}
