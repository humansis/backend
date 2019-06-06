<?php


namespace DistributionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('distribution');

        $rootNode
            ->children()
                ->arrayNode('criteria')
                    ->children()
                        ->arrayNode('gender')
                            ->children()
                                ->scalarNode('type')->end()
                                ->scalarNode('target')->end()
                            ->end()
                        ->end()
                        ->arrayNode('dateOfBirth')
                            ->children()
                                ->scalarNode('type')->end()
                                ->scalarNode('target')->end()
                            ->end()
                        ->end()
                        ->arrayNode('vulnerabilityCriteria')
                            ->children()
                                ->scalarNode('type')->end()
                                ->scalarNode('target')->end()
                            ->end()
                        ->end()
                        ->arrayNode('countrySpecific')
                            ->children()
                                ->scalarNode('type')->end()
                                ->scalarNode('target')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('retriever')
                    ->defaultValue('')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
