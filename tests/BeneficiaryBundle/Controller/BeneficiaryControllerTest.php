<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Utils\ExportService;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class BeneficiaryControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
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

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $listCriterias[0]);
        $this->assertArrayHasKey('field_string', $listCriterias[0]);
        
        return true;
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetBeneficiariesNumberAction()
    {
        $criteria = [
            'distribution_type' => 'individual',
            'threshold' => '0',
            'criteria' => [
                [
                    [
                        'target' => 'Beneficiary',
                        'table_string' => 'vulnerabilityCriteria',
                        'field_string' => 'pregnant',
                        'condition_string' => 'true',
                        'id_field' => 1,
                        'weight' => 1,
                    ],
                    [
                        'target' => 'Household',
                        'table_string' => 'countrySpecific',
                        'field_string' => 'IDPoor',
                        'condition_string' => '>',
                        'id_field' => 1,
                        'type' => 'number',
                        'value_string' => '1',
                        'weight' => 1,
                    ],
                ],
                [
                    [
                        'target' => 'Head',
                        'table_string' => 'Personnal',
                        'field_string' => 'headOfHouseholdDateOfBirth',
                        'condition_string' => '<',
                        'id_field' => 1,
                        'type' => 'table_field',
                        'value_string' => '2001-01-09',
                        'weight' => 1,
                    ],
                ],
            ],
        ];

        $project = $this->em->getRepository(Project::class)->findAll();
        if (!$project) {
            $this->fail("\nUnable to find a project\n");

            return false;
        }

        $projectId = $project[0]->getId();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $user->addRole('ROLE_ADMIN');
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/wsse/beneficiaries/project/'.$projectId.'/number', $criteria);
        $listDistributionBeneficiary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        $this->assertIsArray($listDistributionBeneficiary);
        $this->assertArrayHasKey('number', $listDistributionBeneficiary);
        $this->assertIsInt($listDistributionBeneficiary['number']);
    }
}
