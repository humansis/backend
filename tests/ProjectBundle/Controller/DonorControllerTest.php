<?php


namespace Tests\ProjectBundle\Controller;

use ProjectBundle\Entity\Donor;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class DonorControllerTest extends BMSServiceTestCase
{
    /** @var string $namefullname */
    private $namefullname = "TEST_DONOR_NAME_PHPUNIT";

    private $body = [
        "fullname" => "TEST_DONOR_NAME_PHPUNIT",
        "shortname" => "TEST_DONOR_NAME",
        "date_added" => "01-04-2018 11:20:13",
        "logo" => "https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/donors/5ce65b74992a5.png",
        "notes" => "This is a note"
    ];


    /**
     * @throws \Exception
     */
    public function setUp(): void
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
    public function testCreate()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/donors', $this->body);
        $project = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        try {
            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('fullname', $project);
            $this->assertArrayHasKey('shortname', $project);
            $this->assertArrayHasKey('date_added', $project);
            $this->assertArrayHasKey('notes', $project);
            $this->assertSame($project['fullname'], $this->namefullname);
        } catch (\Exception $exception) {
            print_r("\nThe mapping of fields of Donor entity is not correct.\n");
            $this->remove($this->namefullname);
            return false;
        }

        return true;
    }

    /**
     * @depends testCreate
     * @throws \Exception
     */
    public function testUpdate($isSuccess = true)
    {
        if (!$isSuccess) {
            print_r("\nThe creation of donor failed. We can't test the update.\n");
            $this->markTestIncomplete("The creation of donor failed. We can't test the update.");
        }


        $this->em->clear();
        $donor = $this->em->getRepository(Donor::class)->findOneByFullname($this->namefullname);
        if (!$donor instanceof Donor) {
            $this->fail("ISSUE : This test must be executed after the createTest");
        }

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['fullname'] .= '(u)';
        $crawler = $this->request('POST', '/api/wsse/donors/' . $donor->getId(), $this->body);
        $this->body['fullname'] = $this->namefullname;

        $donor = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->em->clear();
        try {
            $this->assertArrayHasKey('id', $donor);
            $this->assertArrayHasKey('fullname', $donor);
            $this->assertArrayHasKey('shortname', $donor);
            $this->assertArrayHasKey('date_added', $donor);
            $this->assertArrayHasKey('notes', $donor);
            $this->assertSame($donor['fullname'], $this->namefullname . '(u)');
        } catch (\Exception $exception) {
            $this->remove($this->namefullname);
            return false;
        }

        return true;
    }

    /**
     * @depends testUpdate
     * @throws \Exception
     */
    public function testGetAll($isSuccess)
    {
        if (!$isSuccess) {
            print_r("\nThe edition of donor failed. We can't test the update.\n");
            $this->markTestIncomplete("The edition of donor failed. We can't test the update.");
        }

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/donors');
        $donors = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($donors)) {
            $project = $donors[0];

            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('fullname', $project);
            $this->assertArrayHasKey('shortname', $project);
            $this->assertArrayHasKey('date_added', $project);
            $this->assertArrayHasKey('notes', $project);
        } else {
            $this->markTestIncomplete("You currently don't have any donor in your database.");
        }

        return $this->remove($this->namefullname . '(u)');
    }

    /**
     * @depends testGetAll
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove($name)
    {
        $this->em->clear();
        $donor = $this->em->getRepository(Donor::class)->findOneByFullname($name);
        if ($donor instanceof Donor) {
            $this->em->remove($donor);
            $this->em->flush();
        }
    }
}
