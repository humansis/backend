<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class BeneficiaryControllerTest extends BMSServiceTestCase
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
     * @throws Exception
     */
    public function testGetBeneficiary()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $beneficiary = $em->getRepository(Beneficiary::class)->findBy([])[0];

        $this->request('GET', '/api/basic/beneficiaries/'.$beneficiary->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('dateOfBirth', $result);
        $this->assertArrayHasKey('localFamilyName', $result);
        $this->assertArrayHasKey('localGivenName', $result);
        $this->assertArrayHasKey('localParentsName', $result);
        $this->assertArrayHasKey('enFamilyName', $result);
        $this->assertArrayHasKey('enGivenName', $result);
        $this->assertArrayHasKey('enParentsName', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('nationalIds', $result);
        $this->assertArrayHasKey('phoneIds', $result);
        $this->assertArrayHasKey('referralType', $result);
        $this->assertArrayHasKey('referralComment', $result);
        $this->assertArrayHasKey('isHead', $result);
        $this->assertArrayHasKey('vulnerabilityCriteria', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetBeneficiaries()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $beneficiary = $em->getRepository(Beneficiary::class)->findBy([])[0];

        $this->request('GET', '/api/basic/beneficiaries?filter[id][]='.$beneficiary->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
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

        $this->request('GET', '/api/basic/beneficiaries/national-ids/'.$nationalId->getId());

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
    public function testGetPhone()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $phone = $em->getRepository(Phone::class)->findBy([])[0];

        $this->request('GET', '/api/basic/beneficiaries/phones/'.$phone->getId());

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
    public function testGetCamp()
    {
        $this->markTestSkipped('There is no camp');

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $camp = $em->getRepository(HouseholdLocation::class)->findBy(['type' => HouseholdLocation::LOCATION_TYPE_CAMP])[0];

        $this->request('GET', '/api/basic/beneficiaries/addresses/camps/'.$camp->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('locationGroup', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('tentNumber', $result);
        $this->assertArrayHasKey('adm1', $result);
        $this->assertArrayHasKey('adm2', $result);
        $this->assertArrayHasKey('adm3', $result);
        $this->assertArrayHasKey('adm4', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetResidence()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $residence = $em->getRepository(HouseholdLocation::class)->findBy(['type' => HouseholdLocation::LOCATION_TYPE_RESIDENCE])[0];

        $this->request('GET', '/api/basic/beneficiaries/addresses/residencies/'.$residence->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('locationGroup', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('street', $result);
        $this->assertArrayHasKey('postcode', $result);
        $this->assertArrayHasKey('adm1', $result);
        $this->assertArrayHasKey('adm2', $result);
        $this->assertArrayHasKey('adm3', $result);
        $this->assertArrayHasKey('adm4', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTemporarySettlement()
    {
        $this->markTestSkipped('There is no temporary settlement');

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $settlement = $em->getRepository(HouseholdLocation::class)->findBy(['type' => HouseholdLocation::LOCATION_TYPE_SETTLEMENT]);

        $this->request('GET', '/api/basic/beneficiaries/addresses/temporary-settlements/'.$settlement->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('locationGroup', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('street', $result);
        $this->assertArrayHasKey('postcode', $result);
        $this->assertArrayHasKey('adm1', $result);
        $this->assertArrayHasKey('adm2', $result);
        $this->assertArrayHasKey('adm3', $result);
        $this->assertArrayHasKey('adm4', $result);
    }
}
