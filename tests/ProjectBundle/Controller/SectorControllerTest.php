<?php


namespace Tests\ProjectBundle\Controller;


use ProjectBundle\Entity\Sector;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class SectorControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;

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
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testCreateSector()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('PUT', '/api/wsse/sector', $this->body);
        $sector = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        try
        {
            $this->assertArrayHasKey('id', $sector);
            $this->assertArrayHasKey('name', $sector);
            $this->assertSame($sector['name'], $this->name);
        }
        catch (\Exception $exception)
        {
            print_r("\nThe mapping of fields of Sector entity is not correct.\n");
            $this->remove($this->name);
            return false;
        }

        return true;
    }

    /**
     * @depends testCreateSector
     * @throws \Exception
     */
    public function testEditSector($isSuccess = true)
    {
        if (!$isSuccess)
        {
            print_r("\nThe creation of sector failed. We can't test the update.\n");
            $this->markTestIncomplete("The creation of sector failed. We can't test the update.");
        }


        $this->em->clear();
        $sector = $this->em->getRepository(Sector::class)->findOneByName($this->name);
        if (!$sector instanceof Sector)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['name'] .= '(u)';
        $crawler = $this->client->request('POST', '/api/wsse/sector/' . $sector->getId(), $this->body);
        $this->body['name'] = $this->name;

        $sector = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();
        try
        {
            $this->assertArrayHasKey('id', $sector);
            $this->assertArrayHasKey('name', $sector);
            $this->assertSame($sector['name'], $this->name . '(u)');
        }
        catch (\Exception $exception)
        {
            $this->remove($this->name);
            return false;
        }

        return true;
    }

    /**
     * @depends testEditSector
     * @throws \Exception
     */
    public function testGetSectors($isSuccess)
    {
        if (!$isSuccess)
        {
            print_r("\nThe creation of sector failed. We can't test the update.\n");
            $this->markTestIncomplete("The creation of sector failed. We can't test the update.");
        }

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('GET', '/api/wsse/sectors');
        $sectors = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($sectors))
        {
            $sector = $sectors[0];

            $this->assertArrayHasKey('id', $sector);
            $this->assertArrayHasKey('name', $sector);
        }
        else
        {
            $this->markTestIncomplete("You currently don't have any sector in your database.");
        }
        return $this->remove($this->name . '(u)');
    }

    /**
     * @depends testEditSector
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove($name)
    {
        $this->em->clear();
        $donor = $this->em->getRepository(Sector::class)->findOneByName($name);
        if ($donor instanceof Sector)
        {
            $this->em->remove($donor);
            $this->em->flush();
        }
    }
}