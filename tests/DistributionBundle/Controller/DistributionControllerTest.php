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
use TransactionBundle\Entity\Transaction;

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
            "commodities" => [
                [
                    "modality" => "Cash",
                    "modality_type" => [
                        "id" => "1"
                    ],
                    "type" => "Mobile Money",
                    "unit" => "USD",
                    "value" => "150"
                ]
            ],
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
        $this->assertArrayHasKey('location', $distribution);
        $this->assertArrayHasKey('project', $distribution);
        $this->assertArrayHasKey('selection_criteria', $distribution);
        $this->assertArrayHasKey('validated', $distribution);

        return $distribution;
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetRandomBeneficiaries($distribution) {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'. $distribution['id'] .'/random?size=2');
        $randomBenef = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($randomBenef[0]) == 'array');
        $this->assertTrue(gettype($randomBenef[1]) == 'array');
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testValidate($distribution) {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'. $distribution['id'] .'/validate');
        $validate = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertArrayHasKey('id', $validate);
        $this->assertArrayHasKey('name', $validate);
        $this->assertArrayHasKey('updated_on', $validate);
        $this->assertArrayHasKey('date_distribution', $validate);
        $this->assertArrayHasKey('location', $validate);
        $this->assertArrayHasKey('project', $validate);
        $this->assertArrayHasKey('selection_criteria', $validate);
        $this->assertArrayHasKey('archived', $validate);
        $this->assertArrayHasKey('validated', $validate);
        $this->assertArrayHasKey('type', $validate);
        $this->assertArrayHasKey('commodities', $validate);
        $this->assertArrayHasKey('distribution_beneficiaries', $validate);
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddBeneficiary($distribution) {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/distributions/'. $distribution['id'] .'/beneficiary');
        $randomBenef = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($randomBenef[0]) == 'array');
        $this->assertTrue(gettype($randomBenef[1]) == 'array');
    }

//    /**
//     * @depends testCreateDistribution
//     * @param $distribution
//     * @return void
//     * @throws \Doctrine\ORM\ORMException
//     * @throws \Doctrine\ORM\OptimisticLockException
//     */
//    public function testPostTransaction($distribution) {
//        // Fake connection with a token for the user tester (ADMIN)
//        $user = $this->getTestUser(self::USER_TESTER);
//        $token = $this->getUserToken($user);
//        $this->tokenStorage->setToken($token);
//
//        $body = array(
//            'code' => '265094'
//        );
//
//        // Second step
//        // Create the user with the email and the salted password. The user should be enable
//        $crawler = $this->request('POST', '/api/wsse/transaction/distribution/'. $distribution['id'].'/send', $body);
//        $transaction = json_decode($this->client->getResponse()->getContent(), true);
//
//        // Check if the second step succeed
//        var_dump($transaction);
//        $this->assertTrue(gettype($transaction[0]) == 'array');
//        $this->assertTrue(gettype($transaction[1]) == 'array');
//        $this->assertTrue(gettype($transaction[2]) == 'array');
//        $this->assertTrue(gettype($transaction[3]) == 'array');
//    }

//    /**
//     * @depends testCreateDistribution
//     * @param $distribution
//     * @return void
//     * @throws \Doctrine\ORM\ORMException
//     * @throws \Doctrine\ORM\OptimisticLockException
//     */
//    public function testSendVerificationEmail($distribution) {
//        // Fake connection with a token for the user tester (ADMIN)
//        $user = $this->getTestUser(self::USER_TESTER);
//        $token = $this->getUserToken($user);
//        $this->tokenStorage->setToken($token);
//
//        $body = array(
//            'code' => '265094'
//        );
//
//        // Second step
//        // Create the user with the email and the salted password. The user should be enable
//        $crawler = $this->request('POST', '/api/wsse/transaction/distribution/'. $distribution['id'].'/email', $body);
//        $email = json_decode($this->client->getResponse()->getContent(), true);
//
//        // Check if the second step succeed
//        $this->assertEquals($email,'Email sent');
//    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeDistribution($distribution)
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
//                $transaction = $this->em->getRepository(Transaction::class)->findOneByDistributionBeneficiary($distributionBeneficiary);
//                $this->em->remove($transaction);
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