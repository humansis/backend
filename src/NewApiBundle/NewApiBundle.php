<?php

namespace NewApiBundle;

use NewApiBundle\DependencyInjection\Compiler\MapperCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NewApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MapperCompilerPass());
    }
}
