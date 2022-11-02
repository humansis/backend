<?php

declare(strict_types=1);

namespace DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MapperCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(\Serializer\MapperNormalizer::class)) {
            return;
        }

        $definition = $container->getDefinition(\Serializer\MapperNormalizer::class);

        $taggedServices = $container->findTaggedServiceIds('app.mapper');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerMapper', [new Reference($id)]);
        }
    }
}
