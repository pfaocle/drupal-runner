<?php

use Robo\Drupal\Config\BuildConfiguration;

/**
 * Test components of the BuildConfiguration class.
 */
class BuildConfigurationTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     *   The Tester object being used to test.
     */
    protected $tester;

    /**
     * @var BuildConfiguration
     *   Store an instantiated BuildConfiguration object used in testing.
     */
    protected $buildConfig;

    /**
     * Instantiate a BuildConfiguration class before each test.
     */
    public function _before()
    {
        $this->buildConfig = new BuildConfiguration();
    }

    /**
     * Test class is instantiable and of expected type.
     */
    public function testClassIsInstantiateAndOfExpectedType()
    {
        $this->assertInstanceOf('\Robo\Drupal\Config\BuildConfiguration', $this->buildConfig);
    }

    /**
     * Test results of various methods returns the expected object types.
     */
    public function testMethodReturnTypes()
    {
        $this->assertInstanceOf(
            '\Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $this->buildConfig->getConfigTreeBuilder()
        );
        $this->assertInstanceOf(
            '\Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition',
            $this->buildConfig->addMigrateSection()
        );
    }
}
