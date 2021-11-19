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
    const SWG_FILES = [
        ['prefix' => '/api/{firewall}/web-app', 'path' => 'vendor/humansis/web-api/swagger.yaml'],
        ['prefix' => '/api/{firewall}/vendor-app', 'path' => 'vendor/humansis/vendor-app-api/swagger.yaml'],
        ['prefix' => '/api/{firewall}/offline-app', 'path' => 'vendor/humansis/user-app-api/swagger.yaml'],
        ['prefix' => '/api/{firewall}/vendor-app', 'path' => 'vendor/humansis/vendor-app-legacy-api/swagger.yaml'],
        ['prefix' => '/api/{firewall}/offline-app', 'path' => 'vendor/humansis/user-app-legacy-api/swagger.yaml'],
    ];

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
    }
    
    public function swaggerEndpointProvider(): iterable
    {
        foreach (self::SWG_FILES as $swaggerConfig) {
            $swagger = Yaml::parse(file_get_contents($swaggerConfig['path']));
            foreach ($swagger['paths'] as $path => $pathDescription) {
                foreach ($pathDescription as $method => $pathMethodDescription) {
                    $method = strtoupper($method);
                    $normalizedPath = strtolower($swaggerConfig['prefix'].$path);
                    // ignore path parameter which aren't methods
                    if (in_array($method, self::SWG_IGNORE_METHODS)) continue;
                    // ignore method duplicities between swaggers
                    if (isset(self::$swaggerEndpoints[$normalizedPath]) && in_array($method, self::$swaggerEndpoints[$normalizedPath])) continue;

                    self::$swaggerEndpoints[$normalizedPath][] = $method;
                    yield $pathMethodDescription['summary'] ?? $normalizedPath => [$normalizedPath, $method];
                }
            }
        }
    }

    // TODO: after old FE removal remove this check too
    private static function isNewEndpoint(string $path): bool
    {
        foreach (self::SWG_FILES as $swgConfig) {
            if (strpos($path, $swgConfig['prefix']) === 0) {
                return true;
            }
        }
        return false;
    }

    public function testSwaggersAreComplete()
    {
        $correct = 0;
        $failed = 0;
        // TODO: move to dataprovider
        foreach (self::$applicationEndpoints as $path => $methods) {
            foreach ($methods as $method) {
                $found = $this->assertSwgHasEndpoint($path, $method);
                if ($found === null) continue;
                if ($found) $correct++; else $failed++;
            }
        }
        $all = $correct + $failed;
        $this->assertEquals(0, $failed, "There are $failed undocumented endpoints ($all endpoint is implemented)");
    }

    /**
     * @dataProvider swaggerEndpointProvider
     * @param string $path
     * @param string $method
     */
    public function testAppHasEndpoint(string $path, string $method): void
    {
        $this->assertArrayHasKey($path, self::$applicationEndpoints, "Application missing endpoint with path $path");
        $this->assertContains($method, self::$applicationEndpoints[$path], "Application missing method $method for endpoint with path $path");
    }

    private function assertSwgHasEndpoint(string $path, string $method): ?bool
    {
        // TODO: after old FE removal remove this check too
        if (!self::isNewEndpoint($path)) {
            echo "We don't document old endpoints: $path\n";
            return null;
        }
        if (!array_key_exists($path, self::$swaggerEndpoints) || !in_array($method, self::$swaggerEndpoints[$path])) {
            echo "Swagger definition missing endpoint with path $path [$method]\n";
            return false;
        }
        // $this->assertArrayHasKey($path, self::$swaggerEndpoints, "Swagger definition missing endpoint with path $path");
        // $this->assertContains($method, self::$swaggerEndpoints[$path], "Swagger definition missing method $method for endpoint with path $path");
        return true;
    }
}
