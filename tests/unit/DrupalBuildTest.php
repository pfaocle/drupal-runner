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
     * Test expected public statics are available.
     */
    public function testExpectedPublicStaticVariablesAreAvailable()
    {
        $this->assertNotEmpty(DrupalBuild::$drupalHiddenFiles);
        $this->assertNotEmpty(DrupalBuild::$unwantedFilesPatterns);
        $this->assertNotEmpty(DrupalBuild::$sitesFileLinePattern);
        $this->assertNotEmpty(DrupalBuild::$drupalDefaultTheme);
    }

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