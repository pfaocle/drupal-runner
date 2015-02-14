<?php

namespace Robo\Drupal\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Define the tree for a Drupal Runner build configuration.
 */
class BuildConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
     *   The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        /** @var ArrayNodeDefinition|NodeDefinition */
        $rootNode = $treeBuilder->root('build');

        // Add node definitions to the root of the tree.
        $rootNode
            ->children()
                ->scalarNode("git")->end()
                ->scalarNode("drush_alias")->end()
                ->scalarNode("profile")->end()
                ->scalarNode("sites_subdir")->end()
                ->scalarNode("make")->end()
                ->scalarNode("make_path")->end()
                ->arrayNode("sites")
                    ->prototype("scalar")->end()
                ->end()

                // Site details.
                ->arrayNode("site")
                    ->children()
                        ->scalarNode("site_name")->end()
                        ->scalarNode("root_username")->end()
                        ->scalarNode("root_password")->end()
                        ->scalarNode("theme")->end()
                    ->end()
                ->end()

                // Database details.
                ->arrayNode("database")
                    ->children()
                        ->scalarNode("db_name")->end()
                        ->scalarNode("db_username")->end()
                        ->scalarNode("db_password")->end()
                    ->end()
                ->end()

                // Pre steps.
                ->append($this->addPreOrPostSteps("pre"))

                // Enable Features modules.
                ->arrayNode("features")
                    ->prototype("scalar")->end()
                ->end()

                // Migration.
                ->arrayNode("migrate")
                    ->children()
                        ->arrayNode("dependencies")
                            ->prototype("scalar")->end()
                        ->end()
                        ->arrayNode("source")
                            ->children()
                                ->arrayNode("files")
                                    ->children()
                                        ->scalarNode("variable")->end()
                                        ->scalarNode("dir")->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode("groups")
                            ->prototype("scalar")->end()
                        ->end()
                        ->arrayNode("migrations")
                            ->prototype("scalar")->end()
                        ->end()
                    ->end()
                ->end()

                // Post steps.
                ->append($this->addPreOrPostSteps("post"))
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Build and return a 'pre' or 'post' step configuration node.
     *
     * @param string $step
     *   The step to build, either 'pre' or 'post'.
     *
     * @return ArrayNodeDefinition|NodeDefinition
     *   The built node definition.
     *
     * @throws \Exception
     */
    public function addPreOrPostSteps($step)
    {
        if ($step != "pre" && $step != 'post') {
            throw new \Exception("$step is not a valid build step, must be 'pre' or 'post'.");
        }

        $builder = new TreeBuilder();
        $node = $builder->root($step);

        $node
            ->children()
                ->arrayNode("modules")
                    ->prototype("scalar")->end()
                ->end()
                ->arrayNode("commands")
                    ->prototype("scalar")->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
