<?php

namespace Robo\Drupal\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @todo
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
                ->arrayNode("sites")
                    ->prototype("scalar")->end()
                ->end()

                ->arrayNode("site")
                    ->children()
                        ->scalarNode("site_name")->end()
                        ->scalarNode("root_username")->end()
                        ->scalarNode("root_password")->end()
                        ->scalarNode("theme")->end()
                    ->end()
                ->end()

                ->arrayNode("database")
                    ->children()
                        ->scalarNode("db_name")->end()
                        ->scalarNode("db_username")->end()
                        ->scalarNode("db_password")->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
