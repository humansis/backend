<?php
/**
 * Created by PhpStorm.
 * User: developer3
 * Date: 3/12/18
 * Time: 11:26
 */

namespace CommonBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class logService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * ExportService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function logData(string $url) {
        $router = $this->container->get('router');
        $route = $router->match($url)['_route'];
        $controllerName = $route->getDefault('_controller');

        dump($controllerName);
    }
}