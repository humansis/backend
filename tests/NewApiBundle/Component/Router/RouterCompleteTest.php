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

    // TODO: there mustn't be any exception -> fix this
    const EXCEPTION_TO_NOT_DOCUMENT = [
        '/api/{firewall}/web-app/v1/acl/roles/{code}',
        '/api/{firewall}/web-app/v1/languages',
        '/api/{firewall}/web-app/v1/currencies',
        '/api/{firewall}/web-app/v1/households/referrals/types',
        '/api/{firewall}/web-app/v1/households/exports',
        '/api/{firewall}/web-app/v1/users/{id}/countries',
        '/api/{firewall}/web-app/v1/adm2',
        '/api/{firewall}/web-app/v1/adm3',
        '/api/{firewall}/web-app/v1/adm4',
        '/api/{firewall}/web-app/v1/product-categories/types',
        '/api/{firewall}/web-app/v1/product-categories/{id}',
        '/api/{firewall}/web-app/v1/purchased-items/exports',
        '/api/{firewall}/web-app/v1/smartcard-redemption-batches/{id}/legacy-exports',
        '/api/{firewall}/offline-app/v1/assistances/{id}/assistances-institutions',
        '/api/{firewall}/offline-app/v1/assistances/{id}/assistances-communities',
        '/api/{firewall}/offline-app/v1/last-smartcard-deposit/{id}',
        '/api/{firewall}/web-app/v1/smartcard-purchased-items/exports',
        '/api/{firewall}/offline-app/v2/assistances/{id}/assistances-beneficiaries',
        '/api/{firewall}/offline-app/v2/commodities',
        '/api/{firewall}/offline-app/v2/projects/{id}/assistances',
        '/api/{firewall}/offline-app/v2/beneficiaries',
        '/api/{firewall}/offline-app/v2/beneficiary/{id}',
        '/api/{firewall}/offline-app/v1/booklets',
        '/api/{firewall}/offline-app/v2/general-relief-items/{id}',
        '/api/{firewall}/offline-app/v1/general-relief-items',
        '/api/{firewall}/offline-app/v1/users/{id}/logs',
        '/api/{firewall}/offline-app/v1/modality-types',
        '/api/{firewall}/offline-app/v1/smartcard-deposits',
        '/api/{firewall}/offline-app/v2/projects',
        '/api/{firewall}/offline-app/v1/transactions',
        '/api/{firewall}/vendor-app/v2/smartcard-purchases',
        '/api/{firewall}/vendor-app/v1/vendors/{vendorid}/projects/{projectid}/currencies/{currency}/smartcard-purchases',
        '/api/{firewall}/vendor-app/v2/vendors/{id}/smartcard-redemption-candidates',
        '/api/{firewall}/vendor-app/v1/vendors/{id}/logs',
    ];

    // TODO: there mustn't be any exception -> fix this
    const EXCEPTION_TO_NOT_IMPLEMENT = [
        '/api/{firewall}/web-app/v1/acl/roles/{name}',
        '/api/{firewall}/web-app/v1/v1/users/{id}/countries',
        '/api/{firewall}/web-app/v1/assistances/{id}/exports',
        '/api/{firewall}/web-app/v1/assistances/{id}/vulnerability-scores/exports',
        '/api/{firewall}/web-app/households/exports',
        '/api/{firewall}/web-app/assistance-selections/{id}/selection-criteria',
        '/api/{firewall}/web-app/assistance-selections/{id}',
        '/api/{firewall}/web-app/v1/smartcard-purchased-items',
        '/api/{firewall}/web-app/v1/smartcard-purchased-items/export',
        '/api/{firewall}/offline-app/v1/projects/{id}/distributions',
        '/api/{firewall}/offline-app/v1/projects',
        '/api/{firewall}/vendor-app/v1/vendors/{id}/projects/{projectid}/currencies/{currency}/smartcard-purchases',
        '/api/{firewall}/vendor-app/v1/deactivated-booklets',
        '/api/{firewall}/vendor-app/v1/protected-booklets',
        '/api/{firewall}/vendor-app/v1/vouchers/purchase',
        '/api/{firewall}/vendor-app/v1/deactivate-booklets',
        '/api/{firewall}/vendor-app/v1/smartcards/blocked',
        '/api/{firewall}/vendor-app/v1/vendors/{id}/incomplete-smartcard-purchases',
        '/api/{firewall}/offline-app/v1/vendors/{id}/logs',
        '/api/{firewall}/offline-app/v1/distributions/generalrelief/distributed',
        '/api/{firewall}/offline-app/v1/booklets/assign/{assistanceid}/{beneficiaryid}',
        '/api/{firewall}/offline-app/v1/beneficiaries/{beneficiaryid}',
        '/api/{firewall}/offline-app/v1/beneficiaries/{beneficiaryid}',
        '/api/{firewall}/offline-app/v1/smartcards',
        '/api/{firewall}/offline-app/v1/smartcards/{serialnumber}',
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
        // TODO: move to dataprovider
        foreach (self::$applicationEndpoints as $path => $methods) {
            foreach ($methods as $method) {
                $this->assertSwgHasEndpoint($path, $method);
            }
        }
    }

    /**
     * @dataProvider swaggerEndpointProvider
     * @param string $path
     * @param string $method
     */
    public function testAppHasEndpoint(string $path, string $method): void
    {
        // TODO: remove after solve exceptions
        if (in_array($path, self::EXCEPTION_TO_NOT_IMPLEMENT)) {
            $this->markTestSkipped("We are missing implementation of endpoint: $path [$method]. Should be fixed soon!");
        }
        $this->assertArrayHasKey($path, self::$applicationEndpoints, "Application missing endpoint with path $path [$method]");
        $this->assertContains($method, self::$applicationEndpoints[$path], "Application missing method $method for endpoint with path $path [$method]");
    }

    private function assertSwgHasEndpoint(string $path, string $method): void
    {
        // TODO: remove after solve exceptions
        if (in_array($path, self::EXCEPTION_TO_NOT_DOCUMENT)) {
            echo "Swagger definition missing endpoint with path: $path [$method]. Should be fixed soon!\n";
            return;
        }
        // TODO: after old FE removal remove this check too
        if (!self::isNewEndpoint($path)) {
            echo "We don't document old endpoints: $path\n";
            return;
        }
        $this->assertArrayHasKey($path, self::$swaggerEndpoints, "Swagger definition missing endpoint $path [$method]");
        $this->assertContains($method, self::$swaggerEndpoints[$path], "Swagger definition missing endpoint $path [$method]");
    }

    public function implementationExceptionProvider()
    {
        foreach (self::EXCEPTION_TO_NOT_DOCUMENT as $exception) {
            yield $exception => [$exception];
        }
    }

    public function swaggerExceptionProvider()
    {
        foreach (self::EXCEPTION_TO_NOT_IMPLEMENT as $exception) {
            yield $exception => [$exception];
        }
    }

    /**
     * @dataProvider implementationExceptionProvider
     */
    public function testExceptionalImplementationWithoutDocumentationExists(string $path): void
    {
        $this->assertArrayHasKey($path, self::$applicationEndpoints, "Application missing exception with path $path");
    }

    /**
     * @dataProvider swaggerExceptionProvider
     */
    public function testExceptionalDocumentationWithoutImplementationExists(string $path): void
    {
        // $this->assertArrayHasKey($path, self::$applicationEndpoints, "Application missing exception with path $path");
        $this->assertArrayHasKey($path, self::$swaggerEndpoints, "Documentation missing exception with path $path");
    }

}
