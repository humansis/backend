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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\BeneficiaryBundle\Controller\HouseholdControllerTest;
use Tests\BMSServiceTestCase;
use TransactionBundle\Entity\Transaction;

class DistributionControllerTest extends BMSServiceTestCase
{
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
//        $this->removeHousehold($this->namefullnameHousehold);
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
                    "value" => "150",
                    "description" => null
                ]
            ],
            "date_distribution" => "13-09-2018",
            "location" => [
                "adm1"=> 1,
                "adm2"=> 1,
                "adm3"=> 1,
                "adm4"=> 1,
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
                    "target"=> "Beneficiary",
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

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetRandomBeneficiaries($distribution)
    {
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testValidate($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distribution['id'] .'/validate', array());
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddBeneficiary($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'beneficiaries' => array(
                array(
                    'date_of_birth' => '10-06-1976',
                    'en_family_name' => 'NAME_TEST',
                    'local_family_name' => 'NAME_TEST',
                    'gender' => "1",
                    'en_given_name' => 'FIRSTNAME_TEST',
                    'local_given_name' => 'FIRSTNAME_TEST',
                    'id' => 12,
                    'national_ids' => [],
                    'phones' => [],
                    'status' => '0',
                    'residency_status' => 'resident',
                    'vulnerability_criteria' => [
                        [
                            "id" => 1,
                            "field_string" => "disabled"
                        ]                
                    ]
                )
            ),
            'justification' => 'Justification for addition'
        );

        $crawler = $this->request('PUT', '/api/wsse/distributions/'. $distribution['id'] .'/beneficiary', $body);
        $error = $this->client->getResponse()->getContent();
        $this->assertEquals($error, 'This beneficiary/household is already part of the distribution');
        
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testRemoveOneBeneficiary($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'justification' => 'Jusitification for deletion'
        );

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distribution['id'] .'/beneficiaries/11/remove' , $body);
        $remove = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($remove);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAll()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions');
        $all = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertArrayHasKey('id', $all[0]);
        $this->assertArrayHasKey('name', $all[0]);
        $this->assertArrayHasKey('updated_on', $all[0]);
        $this->assertArrayHasKey('date_distribution', $all[0]);
        $this->assertArrayHasKey('location', $all[0]);
        $this->assertArrayHasKey('project', $all[0]);
        $this->assertArrayHasKey('selection_criteria', $all[0]);
        $this->assertArrayHasKey('archived', $all[0]);
        $this->assertArrayHasKey('validated', $all[0]);
        $this->assertArrayHasKey('type', $all[0]);
        $this->assertArrayHasKey('commodities', $all[0]);
        $this->assertArrayHasKey('distribution_beneficiaries', $all[0]);
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetOne($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'. $distribution['id']);
        $one = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertArrayHasKey('id', $one);
        $this->assertArrayHasKey('name', $one);
        $this->assertArrayHasKey('updated_on', $one);
        $this->assertArrayHasKey('date_distribution', $one);
        $this->assertArrayHasKey('location', $one);
        $this->assertArrayHasKey('project', $one);
        $this->assertArrayHasKey('selection_criteria', $one);
        $this->assertArrayHasKey('archived', $one);
        $this->assertArrayHasKey('validated', $one);
        $this->assertArrayHasKey('type', $one);
        $this->assertArrayHasKey('commodities', $one);
        $this->assertArrayHasKey('distribution_beneficiaries', $one);
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributionBeneficiaries($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'. $distribution['id'] .'/beneficiaries');
        $beneficiaries = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($beneficiaries['beneficiaries']) == "array");
        $this->assertArrayHasKey('id', $beneficiaries['beneficiaries'][0]);
        $this->assertArrayHasKey('beneficiary', $beneficiaries['beneficiaries'][0]);
        $this->assertArrayHasKey('transactions', $beneficiaries['beneficiaries'][0]);
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUpdate($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'archived' => false,
            'date_distribution' => '09-12-2019',
            'id' => $distribution['id'],
            "location" => [
                "adm1"=> 2,
                "adm2"=> 2,
                "adm3"=> 2,
                "adm4"=> 2,
                "country_iso3"=> "KHM"
            ],
            'name' => 'TEST_DISTRIBUTION_NAME_PHPUNIT',
            "project"=> $distribution['project'],
            "selection_criteria"=> $distribution['selection_criteria'],
            'type' => 0,
            'updated_on' => '28-11-2018 11:11:11',
            'validated' => false,
        );
        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distribution['id'], $body);
        $update = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertArrayHasKey('id', $update);
        $this->assertArrayHasKey('name', $update);
        $this->assertArrayHasKey('updated_on', $update);
        $this->assertArrayHasKey('date_distribution', $update);
        $this->assertArrayHasKey('location', $update);
        $this->assertArrayHasKey('project', $update);
        $this->assertArrayHasKey('selection_criteria', $update);
        $this->assertArrayHasKey('archived', $update);
        $this->assertArrayHasKey('validated', $update);
        // $this->assertArrayHasKey('reporting_distribution', $update); // Not in the group fullDistribution any more
        $this->assertArrayHasKey('type', $update);
        $this->assertArrayHasKey('commodities', $update);
        $this->assertArrayHasKey('distribution_beneficiaries', $update);
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testArchived($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distribution['id'] . '/archive');
        $archive = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertEquals($archive, "Archived");
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributions($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/projects/'. $distribution['project']['id']);
        $distributions = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($distributions) == "array");
        $this->assertArrayHasKey('id', $distributions[0]);
        $this->assertArrayHasKey('updated_on', $distributions[0]);
        $this->assertArrayHasKey('date_distribution', $distributions[0]);
        $this->assertArrayHasKey('location', $distributions[0]);
        $this->assertArrayHasKey('project', $distributions[0]);
        $this->assertArrayHasKey('selection_criteria', $distributions[0]);
        $this->assertArrayHasKey('archived', $distributions[0]);
        $this->assertArrayHasKey('validated', $distributions[0]);
        $this->assertArrayHasKey('type', $distributions[0]);
        $this->assertArrayHasKey('commodities', $distributions[0]);
        $this->assertArrayHasKey('distribution_beneficiaries', $distributions[0]);
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testImport($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $distributionCSVService = $this->container->get('distribution.distribution_csv_service');

        $countryIso3 = 'KHM';

        //distributionData will be used in the function "parseCSV" to get all the beneficiaries in a project :
        $distributionData = $this->em->getRepository(DistributionData::class)->findOneById($distribution['id']);
        $distributionBeneficiaryService = $this->container->get('distribution.distribution_beneficiary_service');

        //beneficiaries contains all beneficiaries in a distribution :
        $beneficiaries = $distributionBeneficiaryService->getBeneficiaries($distributionData);
        $uploadedFile = new UploadedFile(__DIR__.'/../Resources/beneficiariesInDistribution.csv', 'beneficiaryInDistribution.csv');

        $import = $distributionCSVService->parseCSV($countryIso3, $beneficiaries, $distributionData, $uploadedFile);

        // Check if the second step succeed
        $this->assertTrue(gettype($import) == "array");
        $this->assertArrayHasKey('added', $import);
        $this->assertArrayHasKey('created', $import);
        $this->assertArrayHasKey('deleted', $import);
        $this->assertArrayHasKey('updated', $import);

        $justifiedTypes = ['added', 'created', 'deleted'];
        foreach ($justifiedTypes as $justifiedType) {
            $justifiedBeneficiaries = [];
            foreach ($import[$justifiedType] as $beneficiary) {
                $beneficiary['justification'] = 'Justification ' . $justifiedType;
                array_push($justifiedBeneficiaries, $beneficiary);
            }
            $import[$justifiedType] = $justifiedBeneficiaries;
        }

        $save = $distributionCSVService->saveCSV($countryIso3, $distributionData, $import);

        $this->assertTrue(gettype($save) == "array");
        $this->assertArrayHasKey('result', $save);
        $this->assertEquals($save['result'], "Beneficiary list updated.");
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetBeneficiariesInProject($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'target' => 'Households'
        );
        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/beneficiaries/project/'. $distribution['project']['id'], $body);
        $beneficiaries = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($beneficiaries) == "array");
        $this->assertArrayHasKey('id', $beneficiaries[0]);
        $this->assertArrayHasKey('local_given_name', $beneficiaries[0]);
        $this->assertArrayHasKey('local_family_name', $beneficiaries[0]);
        $this->assertArrayHasKey('gender', $beneficiaries[0]);
        $this->assertArrayHasKey('status', $beneficiaries[0]);
        $this->assertArrayHasKey('residency_status', $beneficiaries[0]);
        $this->assertArrayHasKey('date_of_birth', $beneficiaries[0]);
        $this->assertArrayHasKey('updated_on', $beneficiaries[0]);
        $this->assertArrayHasKey('profile', $beneficiaries[0]);
        $this->assertArrayHasKey('vulnerability_criteria', $beneficiaries[0]);
        $this->assertArrayHasKey('phones', $beneficiaries[0]);
        $this->assertArrayHasKey('national_ids', $beneficiaries[0]);
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testPostTransaction($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'code' => '145891'
        );

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/transaction/distribution/'. $distribution['id'].'/send', $body);
        $sendMoney = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(true == true);
        // Check if the second step succeed
//        $this->assertTrue(gettype($sendMoney) == "array");
//        $this->assertArrayHasKey('sent', $sendMoney);
//        $this->assertArrayHasKey('failure', $sendMoney);
//        $this->assertArrayHasKey('no_mobile', $sendMoney);
//        $this->assertArrayHasKey('already_sent', $sendMoney);
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUpdateTransactionStatus($distribution)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/transaction/distribution/'. $distribution['id'].'/email');
        $update = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(true == true);
    }

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
        if ($commodity instanceof Commodity) {
            $this->em->remove($commodity);
        }

        $distribution = $this->em->getRepository(DistributionData::class)->find($distribution['id']);
        if ($distribution instanceof DistributionData) {
            $distributionBeneficiaries = $this->em
                ->getRepository(DistributionBeneficiary::class)->findByDistributionData($distribution);
            foreach ($distributionBeneficiaries as $distributionBeneficiary) {
                $transaction = $this->em->getRepository(Transaction::class)->findOneByDistributionBeneficiary($distributionBeneficiary);
                $this->em->remove($transaction);
                $this->em->remove($distributionBeneficiary);
            }

            $selectionCriteria = $this->em->getRepository(SelectionCriteria::class)->findByDistributionData($distribution);
            foreach ($selectionCriteria as $selectionCriterion) {
                $this->em->remove($selectionCriterion);
            }
            $this->em->remove($distribution);
        }

        $this->em->flush();
        $this->removeHousehold($this->namefullnameHousehold);
    }
}
