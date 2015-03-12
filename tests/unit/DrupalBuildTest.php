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
     * Test expected constants are available.
     */
    public function testExpectedConstantsAreAvailable()
    {
        $this->assertNotEmpty(DrupalBuild::BUILD_CONFIG_FILE);
    }

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
        $this->setExpectedException("Exception", 'The file "drupal.build.yml" does not exist');
        new DrupalBuild();
    }
}
