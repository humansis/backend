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

class DistributionControllerTest extends BMSServiceTestCase
{
    /** @var string $namefullname */
    private $namefullname = "TEST_DISTRIBUTION_NAME_PHPUNIT";

    /** @var DistributionCSVService $distributionCSVService */
    private $distributionCSVService;

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
        $this->distributionCSVService = $this->container->get('distribution.distribution_csv_service');
    }

    /**
     * @throws \Exception
     */
    public function testCreateDistribution()
    {
        $this->removeHousehold($this->namefullnameHousehold);
        $this->createHousehold();

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
            "country_specific_answers" => [
                [
                    "answer" => "MY_ANSWER_TEST1",
                    "country_specific" => [
                        "id" => 1
                    ]
                ]
            ],
            "location_name"=> "",
            "name"=> "TEST_DISTRIBUTION_NAME_PHPUNIT",
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


        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/distributions', $criteria);
        $return = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertArrayHasKey('distribution', $return);
        $this->assertArrayHasKey('data', $return);

        $distribution = $return['distribution'];
        $this->assertArrayHasKey('id', $distribution);
        $this->assertArrayHasKey('name', $distribution);
        $this->assertArrayHasKey('updated_on', $distribution);
        $this->assertArrayHasKey('location', $distribution);
        $this->assertArrayHasKey('project', $distribution);
        $this->assertArrayHasKey('selection_criteria', $distribution);
        $this->assertArrayHasKey('validated', $distribution);

        $data = $this->distributionCSVService
            ->export(
                $this->iso3,
                $this->em->getRepository(DistributionData::class)->find($distribution["id"])
            );

        $distributionId = $this->em->getRepository(DistributionData::class)->getLastId();
        $this->assertSame($distribution['name'], $this->namefullname.$distributionId);
        $rows = str_getcsv($data['content'], "\n");
        foreach ($rows as $index => $row)
        {

            if ($index < 2)
                continue;

            $rowArray = str_getcsv($row, ',');

            $this->assertSame($this->bodyHousehold['beneficiaries'][$index - 2]["given_name"], $rowArray[13]);
            $this->assertSame($this->bodyHousehold['beneficiaries'][$index - 2]["family_name"], $rowArray[14]);
            $this->assertSame($this->bodyHousehold['beneficiaries'][$index - 2]["gender"], intval($rowArray[15]));
            $this->assertSame($this->bodyHousehold['beneficiaries'][$index - 2]["status"], intval($rowArray[16]));
            $this->assertSame($this->bodyHousehold['beneficiaries'][$index - 2]["date_of_birth"], $rowArray[17]);
        }

        $this->removeDistribution($distribution);
        return true;
    }

    /**
     * @param $distribution
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function removeDistribution($distribution)
    {
        $commodity = $this->em->getRepository(Commodity::class)->findOneByUnit("PHPUNIT TEST");
        if ($commodity instanceof Commodity)
        {
            $this->em->remove($commodity);
        }

        $distribution = $this->em->getRepository(DistributionData::class)->find($distribution['id']);
        if ($distribution instanceof DistributionData)
        {

            $distributionBeneficiaries = $this->em
                ->getRepository(DistributionBeneficiary::class)->findByDistributionData($distribution);
            foreach ($distributionBeneficiaries as $distributionBeneficiary)
            {
                $this->em->remove($distributionBeneficiary);

            }

            $selectionCriteria = $this->em->getRepository(SelectionCriteria::class)->findByDistributionData($distribution);
            foreach ($selectionCriteria as $selectionCriterion)
            {
                $this->em->remove($selectionCriterion);

            }
            $this->em->remove($distribution);
        }

        $this->em->flush();
        $this->removeHousehold($this->namefullnameHousehold);
    }
}