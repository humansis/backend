<?php


namespace Tests\ProjectBundle\Controller;

use ProjectBundle\Entity\Sector;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class SectorControllerTest extends BMSServiceTestCase
{

    /** @var string $name */
    private $name = "TEST_DONOR_NAME_PHPUNIT";

    private $body = [
        "name" => "TEST_DONOR_NAME_PHPUNIT"
    ];


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
    public function testGetSectors()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/sectors');
        $sectors = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($sectors)) {
            $sector = $sectors[0];

            $this->assertArrayHasKey('id', $sector);
            $this->assertArrayHasKey('name', $sector);
        } else {
            $this->markTestIncomplete("You currently don't have any sector in your database.");
        }
        return true;
    }
}
