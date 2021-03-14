<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Tests\BMSServiceTestCase;

class PersonControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testGet()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var Person[]|null $person */
        $person = $this->container->get('doctrine')->getRepository(Person::class)->findAll();

        if (empty($person)) {
            $this->markTestSkipped('There needs to be at least one person in system to complete this test');
        }

        $this->request('GET', '/api/basic/persons/'.$person[0]->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('profileId', $result);
        $this->assertArrayHasKey('referralType', $result);
        $this->assertArrayHasKey('referralComment', $result);
        $this->assertArrayHasKey('enGivenName', $result);
        $this->assertArrayHasKey('enFamilyName', $result);
        $this->assertArrayHasKey('enParentsName', $result);
        $this->assertArrayHasKey('localGivenName', $result);
        $this->assertArrayHasKey('localFamilyName', $result);
        $this->assertArrayHasKey('localParentsName', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('dateOfBirth', $result);
        $this->assertArrayHasKey('updatedOn', $result);
        $this->assertArrayHasKey('nationalIds', $result);
        $this->assertArrayHasKey('phoneIds', $result);

        return $result['id'];
    }

    /**
     * @depends testGet
     *
     * @param int $id
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/persons?filter[id][]='.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetNationalId()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $nationalId = $em->getRepository(NationalId::class)->findBy([])[0];

        $this->request('GET', '/api/basic/persons/national-ids/'.$nationalId->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('type', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetNationalIds()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $nationalId = $em->getRepository(NationalId::class)->findBy([])[0];

        $this->request('GET', '/api/basic/persons/national-ids?filter[id][]='.$nationalId->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{"totalCount": 1, "data": [{"id": "*"}]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetPhone()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $phone = $em->getRepository(Phone::class)->findBy([])[0];

        $this->request('GET', '/api/basic/persons/phones/'.$phone->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('prefix', $result);
        $this->assertArrayHasKey('proxy', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPhones()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $phone1 = $em->getRepository(Phone::class)->findBy([])[0];
        $phone2 = $em->getRepository(Phone::class)->findBy([])[1];

        $this->request('GET', '/api/basic/persons/phones?filter[id][]='.$phone1->getId().'&filter[id][]='.$phone2->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": 2, 
            "data": [{"id": '.$phone1->getId().'}, {"id": '.$phone2->getId().'}
            ]}', $this->client->getResponse()->getContent());
    }
}
