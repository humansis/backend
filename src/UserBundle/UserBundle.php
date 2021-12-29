<?php

namespace UserBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class UserBundle
 * @package UserBundle
 */
class UserBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');

        $mappings = [
            realpath(__DIR__ . '/Resources/config/doctrine/model') => 'FOS\UserBundle\Model',
        ];

        //Compiler pass added for overriding user-bundle User entity mapping of roles.
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                $mappings, ['fos_user.model_manager_name'], false
            )
        );
    }
}
