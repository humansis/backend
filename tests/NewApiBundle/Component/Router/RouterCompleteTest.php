<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Router;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

class RouterCompleteTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private static $entityManager;

    /** @var RouteCollection */
    private static $routes;
    private static $applicationEndpoints = [];
    private static $swaggerEndpoints = [];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();
        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $router = $kernel->getContainer()->get('router');

        foreach ($router->getRouteCollection() as $route) {
            foreach ($route->getMethods() as $method) {
                self::$applicationEndpoints[$route->getPath()][] = $method;
            }
        }

        $swaggers = $kernel->getContainer()->getParameter('app.swaggers');
        foreach ($swaggers as $swaggerConfig) {
            $swagger = Yaml::parse(file_get_contents($swaggerConfig['path']));
            foreach ($swagger['paths'] as $path => $pathDescription) {
                foreach ($pathDescription as $method => $pathMethodDescription) {
                    self::$swaggerEndpoints[$swaggerConfig['prefix'].$path][] = $method;
                }
            }
        }
    }

    public function testApplicationEndpointsAreComplete()
    {
        // TODO: move to dataprovider
        foreach (self::$swaggerEndpoints as $path => $methods) {
            foreach ($methods as $method) {
                $this->assertAppHasEndpoint($path, $method);
            }
        }
    }

    public function testSwaggersAreComplete()
    {
        // TODO: move to dataprovider
        foreach (self::$applicationEndpoints as $path => $methods) {
            foreach ($methods as $method) {
                $this->assertSwgHasEndpoint($path, $method);
            }
        }
    }

    private function assertAppHasEndpoint(string $path, string $method)
    {
        $this->assertArrayHasKey($path, self::$applicationEndpoints, "Application missing endpoint with path $path");
        $this->assertContains($method, self::$applicationEndpoints[$path], "Application missing method $method for endpoint with path $path");
    }

    private function assertSwgHasEndpoint(string $path, string $method)
    {
        $this->assertArrayHasKey($path, self::$swaggerEndpoints, "Swagger definition missing endpoint with path $path");
        $this->assertContains($method, self::$swaggerEndpoints[$path], "Swagger definition missing method $method for endpoint with path $path");
    }
}
