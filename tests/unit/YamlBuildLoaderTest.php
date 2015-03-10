<?php

use Robo\Drupal\Config\YamlBuildLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Test components of the YamlBuildLoader class.
 */
class YamlBuildLoaderTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     *   The Tester object being used to test.
     */
    protected $tester;

    /**
     * @var YamlBuildLoader
     *   Store an instantiated YamlBuildLoader object for testing.
     */
    protected $yamlBuildLoader;

    /**
     * Instantiate the YamlBuildLoader class for testing.
     */
    public function _before()
    {
        // Note the FileLocator doesn't need to actually locate or load a file here, so we don't pass any directories
        // to search here.
        $this->yamlBuildLoader = new YamlBuildLoader(new FileLocator(array()));
    }

    /**
     * Test we can instantiate this class, and that it is of expected type.
     */
    public function testClassIsInstantiableAndOfExpectedType()
    {
        $this->assertInstanceOf('\Robo\Drupal\Config\YamlBuildLoader', $this->yamlBuildLoader);
        $this->assertInstanceOf('\Symfony\Component\Config\Loader\FileLoader', $this->yamlBuildLoader);
    }

    /**
     * We expect a null return value for an empty YAML file.
     */
    public function testLoadMethodReturnsNullForEmptyYamlFile()
    {
        $loadedConfig = $this->yamlBuildLoader->load(getcwd() . DIRECTORY_SEPARATOR . 'tests/_data/empty.yml');
        $this->assertNull($loadedConfig);
    }

    /**
     * We expect am array as return value for any given, non-empty YAML file.
     */
    public function testLoadMethodReturnsArrayForNonEmptyYamlFile()
    {
        $loadedConfig = $this->yamlBuildLoader->load(getcwd() . DIRECTORY_SEPARATOR . 'tests/_data/not_empty.yml');
        $this->assertInternalType('array', $loadedConfig);
    }

    /**
     * We expect a return value of TRUE for a file with the .yml extension.
     */
    public function testSupportsMethodReturnsTrueForFileWithYmlExtension()
    {
        $this->assertTrue($this->yamlBuildLoader->supports('_data/empty.yml'));
    }

    /**
     * We expect a return value of FALSE for a file with an extension other than .yml
     */
    public function testSupportsMethodReturnsFalseForFileWithOtherExtension()
    {
        $this->assertFalse($this->yamlBuildLoader->supports('_data/dump.sql'));
    }

    /**
     * Any type other than a string as the first argument to supports() should cause a return value of FALSE.
     */
    public function testSupportsMethodReturnsFalseWhenResourcePassedIsNotAString()
    {
        $notAString = new stdClass();
        $this->assertFalse($this->yamlBuildLoader->supports($notAString));
    }
}
