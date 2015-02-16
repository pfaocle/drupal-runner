<?php

namespace Robo\Drupal\Config;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and parses a configuration file as YAML.
 */
class YamlBuildLoader extends FileLoader
{
    /**
     * Loads, parses and returns the given resource.
     *
     * @param mixed $resource
     *   The resource to load.
     * @param null $type
     *   The resource type or null if unknown.
     *
     * @return array
     *   The configuration file, parsed as YAML.
     */
    public function load($resource, $type = null)
    {
        return Yaml::parse(file_get_contents($resource));
    }

    /**
     * @param mixed $resource
     *   The resource to load.
     * @param null $type
     *   The resource type or null if unknown.
     *
     * @return bool
     *   TRUE if the resource is supported.
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }
}
