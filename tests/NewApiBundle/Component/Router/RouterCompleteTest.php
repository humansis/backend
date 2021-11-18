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

    private static $applicationEndpoints = [];
    private static $swaggerEndpoints = [];

    const SWG_IGNORE_METHODS = ['PARAMETERS'];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();
        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $router = $kernel->getContainer()->get('router');

        foreach ($router->getRouteCollection() as $route) {
            foreach ($route->getMethods() as $method) {
                $method = strtoupper($method);
                $path = strtolower($route->getPath());
                self::$applicationEndpoints[$path][] = strtoupper($method);
            }
        }

        $swaggers = $kernel->getContainer()->getParameter('app.swaggers');
        foreach ($swaggers as $swaggerConfig) {
            $swagger = Yaml::parse(file_get_contents($swaggerConfig['path']));
            foreach ($swagger['paths'] as $path => $pathDescription) {
                foreach ($pathDescription as $method => $pathMethodDescription) {
                    $method = strtoupper($method);
                    $normalizedPath = strtolower($swaggerConfig['prefix'].$path);
                    if (in_array($method, self::SWG_IGNORE_METHODS)) continue;
                    if (isset(self::$swaggerEndpoints[$normalizedPath]) && in_array($method, self::$swaggerEndpoints[$normalizedPath])) continue;
                    self::$swaggerEndpoints[$normalizedPath][] = $method;
                }
            }
        }
    }

    public function testApplicationEndpointsAreComplete()
    {
        $correct = 0;
        $failed = 0;
        // TODO: move to dataprovider
        foreach (self::$swaggerEndpoints as $path => $methods) {
            foreach ($methods as $method) {
                $found = $this->assertAppHasEndpoint($path, $method);
                if ($found) $correct++; else $failed++;
            }
        }
        $all = $correct + $failed;
        $this->assertEquals(0, $failed, "There are $failed unimplemented endpoints ($all endpoint is documented)");
    }

    public function testSwaggersAreComplete()
    {
        $correct = 0;
        $failed = 0;
        // TODO: move to dataprovider
        foreach (self::$applicationEndpoints as $path => $methods) {
            foreach ($methods as $method) {
                $found = $this->assertSwgHasEndpoint($path, $method);
                if ($found) $correct++; else $failed++;
            }
        }
        $all = $correct + $failed;
        $this->assertEquals(0, $failed, "There are $failed undocumented endpoints ($all endpoint is implemented)");
    }

    private function assertAppHasEndpoint(string $path, string $method): bool
    {
        if (!array_key_exists($path, self::$applicationEndpoints) || !in_array($method, self::$applicationEndpoints[$path])) {
            echo "Application missing endpoint with path $path [$method]\n";
            return false;
        }
        // $this->assertArrayHasKey($path, self::$applicationEndpoints, "Application missing endpoint with path $path");
        // $this->assertContains($method, self::$applicationEndpoints[$path], "Application missing method $method for endpoint with path $path");
        return true;
    }

    private function assertSwgHasEndpoint(string $path, string $method): bool
    {
        if (!array_key_exists($path, self::$swaggerEndpoints) || !in_array($method, self::$swaggerEndpoints[$path])) {
            echo "Swagger definition missing endpoint with path $path [$method]\n";
            return false;
        }
        // $this->assertArrayHasKey($path, self::$swaggerEndpoints, "Swagger definition missing endpoint with path $path");
        // $this->assertContains($method, self::$swaggerEndpoints[$path], "Swagger definition missing method $method for endpoint with path $path");
        return true;
    }
}
