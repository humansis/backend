<?php

namespace NewApiBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use NewApiBundle\DependencyInjection\Compiler\MapperCompilerPass;
use NewApiBundle\Security\Factory\WsseFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NewApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MapperCompilerPass());

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new WsseFactory());

        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine/model') => 'FOS\UserBundle\Model',
        ];

        //Compiler pass added for overriding user-bundle User entity mapping of roles.
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                $mappings, ['fos_user.model_manager_name'], false
            )
        );
    }
}
