<?php


namespace Tests\DistributionBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\DistributionCSVService;
use DistributionBundle\Utils\DistributionService;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BeneficiaryBundle\Controller\HouseholdControllerTest;
use Tests\BMSServiceTestCase;

class CriteriaDistributionControllerTest extends BMSServiceTestCase
{
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
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetCriteria()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/criteria');
        $criteria = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($criteria[0]) == 'array');
        $this->assertTrue(gettype($criteria[1]) == 'array');
        $this->assertTrue(gettype($criteria[2]) == 'array');
        $this->assertTrue(gettype($criteria[3]) == 'array');
        $this->assertTrue(gettype($criteria[4]) == 'array');
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetBeneficiariesNumberAction()
    {
        $criteria = array('criteria' => array([

            "target" => "Beneficiary",
              "table_string" => "vulnerabilityCriteria",
              "field_string" => "pregnant",
              "condition_string" => "true",
              "id_field" => 1,
              "weight" => 1
            ],
            [
               "target" => "Household",
               "table_string" => "countrySpecific",
               "field_string" => "IDPoor",
               "condition_string" => ">",
               "id_field" => 1,
               "type" => "number",
               "value_string" => "1",
               "weight" => 1
            ]),
            'distribution_type' => 'individual',
            'threshold' => '2'
        );

        $project = $this->em->getRepository(Project::class)->findAll();
        if (!$project) {
            $this->fail("\nUnable to find a project\n");
            return false;
        }

        $projectId = $project[0]->getId();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/distributions/criteria/project/'.$projectId.'/number', $criteria);
        $listDistributionBeneficiary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertTrue(gettype($listDistributionBeneficiary) == "array");

        $this->assertTrue(key_exists('number', $listDistributionBeneficiary));
        $this->assertTrue(gettype($listDistributionBeneficiary['number']) == "integer");

        return true;
    }
}
