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
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetBeneficiariesNumberAction(){

        $criteria = array('criteria' => array([

            "kind_beneficiary" => "Beneficiary",
              "table_string" => "vulnerabilityCriteria",
              "field_string" => "pregnant",
              "condition_string" => "true",
              "id_field" => 1,
              "weight" => 1
            ],
            [
               "kind_beneficiary" => "Household",
               "table_string" => "countrySpecific",
               "field_string" => "ID Poor",
               "condition_string" => ">",
               "id_field" => 1,
               "type" => "number",
               "value_string" => "1",
               "weight" => 1
            ]),
            'distribution_type' => 'beneficiary',
            'threshold' => '2'
        );

        $project = $this->em->getRepository(Project::class)->findAll();
        if(!$project){
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

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        return true;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddAction(){
        $criteria = array(
            "adm1" => "",
	        "adm2"=> "",
            "adm3" => "",
	        "adm4" => "",
	        "commodities" =>[],
	        "date_distribution" => "2018-09-13",
	        "location" => [
                "adm1"=> "Banteay Meanchey",
		        "adm2"=> "Mongkol Borei",
		        "adm3"=> "Chamnaom",
		        "adm4"=> "Chamnaom",
		        "country_iso3"=> "KHM"
            ],
	        "location_name"=> "",
	        "name"=> "-Banteay Meanchey-9/13/2018-",
	        "project"=> [
                "donors"=> [],
		        "donors_name"=> [],
		        "id"=> "1",
                "name"=> "",
		        "sectors"=> [],
		        "sectors_name"=> []
	        ],
	        "selection_criteria"=> [
		        [
                    "condition_string"=> "true",
			        "field_string"=> "disabled",
			        "id_field"=> "1",
			        "kind_beneficiary"=> "Beneficiary",
			        "table_string"=> "vulnerabilityCriteria",
                    "weight"=> "1"
		        ],
                [
                    "condition_string"=> "0",
                    "field_string"=> "gender",
			        "id_field"=> "1",
			        "kind_beneficiary"=> "Beneficiary",
			        "table_string"=> "vulnerabilityCriteria",
                    "weight"=> "1"
		        ]
	        ],
	        "type"=> "Household",
            "threshold"=> "1"
        );

        $distributionBefore = $this->em->getRepository(DistributionData::class)->findAll();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/distributions', $criteria);
        $listDistributionBeneficiary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $distributionAfter = $this->em->getRepository(DistributionData::class)->findAll();

        $this->assertTrue($distributionBefore < $distributionAfter);
    }
}