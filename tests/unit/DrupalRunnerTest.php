<?php

use \Robo\DrupalRunner;

/**
 * Test components of the DrupalRunner class.
 */
class DrupalRunnerTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     *   The Tester object being used to test.
     */
    protected $tester;

    /**
     * Test we can instantiate this class, and that it is of expected type.
     */
    public function testClassIsInstantiableAndOfExpectedType()
    {
        $runner = new DrupalRunner();
        $this->assertInstanceOf('\Robo\DrupalRunner', $runner);
        $this->assertInstanceOf('\Robo\Tasks', $runner);
    }

    /**
     * Test the expected constants are available.
     */
    public function testExpectedConstantsAreAvailable()
    {
        $this->assertNotEmpty(DrupalRunner::DEFAULT_GIT_REMOTE);
        $this->assertNotEmpty(DrupalRunner::GIT_CLEAN_MSG);
    }
}
