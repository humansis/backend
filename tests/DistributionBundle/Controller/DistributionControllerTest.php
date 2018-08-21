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

    private $body = [
        "name" => "TEST_DISTRIBUTION_NAME_PHPUNIT",
        "date_distribution" => "2018-08-10",
        "type" => 0,
        "location" => [
            "adm1" => "Rhone-Alpes",
            "adm2" => "Savoie",
            "adm3" => "Chambery",
            "adm4" => "Sainte Hélène sur Isère"
        ],
        "selection_criteria" => [
            [
                "table_string" => "default",
                "field_string" => "dateOfBirth",
                "value_string" => "1976-10-06",
                "condition_string" => "=",
                "kind_beneficiary" => "beneficiary",
                "field_id" => null
            ],
            [
                "table_string" => "default",
                "field_string" => "gender",
                "value_string" => "1",
                "condition_string" => "=",
                "kind_beneficiary" => "beneficiary",
                "field_id" => null
            ]
        ],
        "commodities" => [
            [
                "unit" => "PHPUNIT TEST",
                "value" => 999999999,
                "modality_type" => []
            ]
        ]
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
        $this->distributionCSVService = $this->container->get('distribution.distribution_csv_service');
    }

    /**
     * @throws \Exception
     */
    public function testCreateDistribution()
    {
        $this->removeHousehold($this->namefullnameHousehold);
        $this->createHousehold();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("\nThere is no project inside the database\n");
            return false;
        }
        $this->body['project']['id'] = current($projects)->getId();

        $modalityTypes = $this->em->getRepository(ModalityType::class)->findAll();
        if (empty($modalityTypes))
        {
            print_r("\nThere is no modality type inside the database\n");
            return false;
        }
        $this->body['commodities'][0]['modality_type']['id'] = current($modalityTypes)->getId();

        $crawler = $this->request('PUT', '/api/wsse/distributions', $this->body);
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

            if ($index === 2)
            {
                $indexAnswerCountrySpecific = 11;
                $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($this->iso3);
                $household = $this->em->getRepository(Household::class)->findOneBy([
                    "addressStreet" => $this->bodyHousehold['address_street'],
                    "addressNumber" => $this->bodyHousehold['address_number']
                ]);
                /** @var CountrySpecific $countrySpecific */
                foreach ($countrySpecifics as $countrySpecific)
                {
                    /** @var CountrySpecificAnswer $answer */
                    $answer = $this->em->getRepository(CountrySpecificAnswer::class)->findOneBy([
                        "countrySpecific" => $countrySpecific,
                        "household" => $household
                    ]);

                    if (!$answer instanceof CountrySpecificAnswer)
                        continue;

                    $this->assertSame($answer->getAnswer(), $rowArray[$indexAnswerCountrySpecific]);
                    $indexAnswerCountrySpecific++;
                }
                $this->assertSame($household->getId(), intval($rowArray[21]));
            }

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