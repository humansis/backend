<?php


namespace Tests\CommonBundle\Controller;

use CommonBundle\Entity\Organization;
use CommonBundle\Utils\OrganizationService;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class OrganizationControllerTest extends BMSServiceTestCase
{
    /** @var string $namefullname */
    private $name = "TEST_ORGANIZATION_PHPUNIT";
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testGetOrganization()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/organization');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $organizations = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($organizations, "Organization should be array organization");

        if (!empty($organizations)) {
            $this->assertArrayHasKey('name', $organizations[0]);
            $this->assertArrayHasKey('font', $organizations[0]);

        } else {
            $this->markTestIncomplete("The database is incomplete.");
        }
        return true;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEditOrganization()
    {
        $body = [
            'name' => 'AKEZI',
            'logo' => null,
            'primary_color' => '#4AA896',
            'secondary_color' => '#02617F',
            'font' => 'Courier',
            'footer_content' => 'Powered by reliefApps'
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/organization/1', $body);
        $newOrganization = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $initialOrganization = $this->em->getRepository(Organization::class)->findOneBy([], ['id' => 'asc']);
        $this->assertEquals($initialOrganization->getName(), 'AKEZI');
        $this->assertEquals($initialOrganization->getFont(), 'Courier');
        $this->assertEquals($initialOrganization->getPrimaryColor(), '#4AA896');

        return $newOrganization;
    }

    /**
     */
    public function testGetOrganizationServices()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/organization/1/service');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $organizationService = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($organizationService, "Organization services must be array");

        if (empty($organizationService)) {
            $this->markTestIncomplete("The database is incomplete.");
        }

        foreach ($organizationService as $organizationService) {
            $this->assertArrayHasKey('id', $organizationService);
            $this->assertArrayHasKey('enabled', $organizationService);
            $this->assertArrayHasKey('service', $organizationService);
            $this->assertArrayNotHasKey('parametersValue', $organizationService);

            $service = $organizationService['service'];

            $this->assertArrayHasKey('id', $service);
            $this->assertArrayHasKey('name', $service);
            $this->assertArrayHasKey('country', $service);
            $this->assertArrayHasKey('parameters', $service);
        }
    }
}
