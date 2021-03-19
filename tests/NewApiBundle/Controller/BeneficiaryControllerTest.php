<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ProjectBundle\Entity\Project;
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
        $this->assertArrayHasKey('residencyStatus', $result);
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

    public function testGetBeneficiariesByAssistance()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(\DistributionBundle\Entity\Assistance::class)->findOneBy([
            'validated' => true,
        ]);
        $assistanceBeneficiary = $em->getRepository(\DistributionBundle\Entity\AssistanceBeneficiary::class)->findOneBy([
            'assistance' => $assistance,
        ]);

        $this->request('GET', '/api/basic/assistances/'.$assistanceBeneficiary->getAssistance()->getId().'/beneficiaries?sort[]=nationalId');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "dateOfBirth": "*",
                    "localFamilyName": "*",
                    "localGivenName": "*",
                    "localParentsName": "*",
                    "enFamilyName": "*",
                    "enGivenName": "*",
                    "enParentsName": "*",
                    "gender": "*",
                    "nationalIds": "*",
                    "phoneIds": "*",
                    "referralType": "*",
                    "referralComment": "*",
                    "residencyStatus": "*",
                    "isHead": "*",
                    "vulnerabilityCriteria": "*"
                }
            ]}', $this->client->getResponse()->getContent());
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
    public function testGetNationalIds()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $nationalId = $em->getRepository(NationalId::class)->findBy([])[0];

        $this->request('GET', '/api/basic/beneficiaries/national-ids?filter[id][]='.$nationalId->getId());

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

        $this->request('GET', '/api/basic/beneficiaries/phones?filter[id][]='.$phone1->getId().'&filter[id][]='.$phone2->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": 2, 
            "data": [{"id": '.$phone1->getId().'}, {"id": '.$phone2->getId().'}
            ]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testAddBeneficiaryToAssistance()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validated' => true,
            'completed' => false,
            'archived' => false,
        ]);
        $beneficiary = $em->getRepository(Beneficiary::class)->findOneBy([], ['id'=>'desc']);

        $this->request('PUT', '/api/basic/assistances/'.$assistance->getId().'/beneficiaries', [
            'beneficiaryIds' => [$beneficiary->getId()],
            'justification' => 'test',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $beneficiary->getId()];
    }

    /**
     * @depends testAddBeneficiaryToAssistance
     */
    public function testRemoveBeneficiaryToAssistance($data)
    {
        list($assistanceId, $beneficiaryId) = $data;

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/basic/assistances/'.$assistanceId.'/beneficiaries', [
            'beneficiaryIds' => [$beneficiaryId],
            'justification' => 'test remove',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetBeneficiariesByProject()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $project = $em->getRepository(Project::class)->findOneBy([
            'archived' => false,
        ]);

        $this->request('GET', '/api/basic/projects/'.$project->getId().'/beneficiaries');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "dateOfBirth": "*",
                    "localFamilyName": "*",
                    "localGivenName": "*",
                    "localParentsName": "*",
                    "enFamilyName": "*",
                    "enGivenName": "*",
                    "enParentsName": "*",
                    "gender": "*",
                    "nationalIds": "*",
                    "phoneIds": "*",
                    "referralType": "*",
                    "referralComment": "*",
                    "residencyStatus": "*",
                    "isHead": "*",
                    "vulnerabilityCriteria": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }
}
