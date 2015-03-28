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

    /**
     * Test a RuntimeException is thrown for an invalid "step type" (i.e. "pre" or "post").
     */
    public function testRunStepsThrowsExceptionIfStepsTypeIsInvalid()
    {
        $runner = new DrupalRunner();
        $class = new \ReflectionClass($runner);
        $method = $class->getMethod("runSteps");
        $method->setAccessible(true);

        $parameter = "this is invalid";
        $this->setExpectedException("RuntimeException", sprintf("%s is not a valid step type.", $parameter));
        $method->invokeArgs($runner, array($parameter));

        $parameter = "";
        $this->setExpectedException("RuntimeException", sprintf("%s is not a valid step type.", $parameter));
        $method->invokeArgs($runner, array($parameter));
    }
}
