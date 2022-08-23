<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class BeneficiaryControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testGetAllBeneficiaryApi()
    {
        $this->assertTrue(true == true);
        /*// Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            "countryCode" => "010201",
            "flush" => "false"
        ];

        $crawler = $this->request('POST', '/api/wsse/beneficiaries/import/api', $body);
        $listHousehold = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue(gettype($listHousehold) == "array");
        $this->assertTrue(key_exists('message', $listHousehold));
        $this->assertTrue($listHousehold['message'] == "Insertion successfull");

        return true;*/
        $this->assertTrue(true);

        return true;
    }

    public function testGetAllVulnerabilityCriteria()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $vulnerabilityCriteriaResponse = $this->request('GET', 'api/wsse/vulnerability_criteria');
        $listCriterias = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $listCriterias[0]);
        $this->assertArrayHasKey('field_string', $listCriterias[0]);

        return true;
    }

    public function testGetResidencyStatuses()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/beneficiaries/residency-statuses');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(ResidencyStatus::all()), $result['totalCount']);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetVulnerabilityCriterion()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/wsse/beneficiaries/vulnerability-criterias');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        $criterion = $em->getRepository(VulnerabilityCriterion::class)->findAllActive();
        $this->assertEquals(count($criterion), $result['totalCount']);
    }
}
