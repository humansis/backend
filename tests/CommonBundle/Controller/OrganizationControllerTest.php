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
        $this->client = $this->container->get('test.client');
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
        $organization = json_decode($this->client->getResponse()->getContent(), true);
        if (!empty($organization)) {
            $this->assertArrayHasKey('name', $organization[0]);
            $this->assertArrayHasKey('font', $organization[0]);

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

        $initialOrganization = $this->em->getRepository(Organization::class)->findOneBy([]);
        $this->assertEquals($initialOrganization->getName(), 'AKEZI');
        $this->assertEquals($initialOrganization->getFont(), 'Courier');
        $this->assertEquals($initialOrganization->getPrimaryColor(), '#4AA896');

        return $newOrganization;
    }
}
