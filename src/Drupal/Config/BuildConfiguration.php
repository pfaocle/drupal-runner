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
     *   The tree builder.
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
                ->scalarNode("drush_alias")
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                    ->ifTrue(function ($alias) { return substr($alias, 0, 1) !== "@";
                    })
                        ->thenInvalid("Drush aliases must be specified with a leading @")
                    ->end()
                ->end()
                ->scalarNode("profile")->end()
                ->scalarNode("install_db")
                    ->defaultNull()
                ->end()
                ->scalarNode("sites_subdir")->end()

                ->arrayNode("make")
                    ->beforeNormalization()
                    // If 'make' is a string, use this as the 'file' child.
                    ->ifString()
                        ->then(
                            function ($v) {
                                return array('file' => $v);
                            }
                        )
                    ->end()
                    ->children()
                        ->scalarNode("file")
                            ->isRequired()
                        ->end()
                        ->scalarNode("path")->end()
                        // 'options' is a set of key-value pairs where the key is the make option name and value is the
                        // make option value.
                        ->arrayNode("options")
                            // Make sure hyphens are preserved.
                            ->normalizeKeys(false)
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()

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
                        ->scalarNode("db_name")
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode("db_username")
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode("db_password")
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()

                // Pre steps.
                ->append($this->addPreOrPostSteps("pre"))

                // Enable Features modules.
                ->arrayNode("features")
                    ->prototype("scalar")->end()
                ->end()

                // Migration.
                ->append($this->addMigrateSection())

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
            ->canBeEnabled()
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

    /**
     * Build and return the migrate section node.
     *
     * @return ArrayNodeDefinition|NodeDefinition
     *   The built node definition.
     */
    public function addMigrateSection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root("migrate");

        $node
            ->canBeEnabled()
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
        ;

        return $node;
    }
}