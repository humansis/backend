<?php

namespace DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): \Symfony\Component\Config\Definition\Builder\TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('distribution');

        $rootNode
            ->children()
            ->arrayNode('criteria')
            ->arrayPrototype()
            ->scalarPrototype()->end()
            ->end()
            ->end()
            ->scalarNode('retriever')
            ->defaultValue('')
            ->end()
            ->end();

        return $treeBuilder;
    }
}
