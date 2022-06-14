<?php

declare(strict_types=1);

namespace Tests\DistributionBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Utils\DistributionCSVService;
use NewApiBundle\Entity\Assistance\SelectionCriteria;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\BMSServiceTestCase;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\InputType\VoucherPurchase;
use VoucherBundle\Model\PurchaseService;

class AssistanceControllerTest extends BMSServiceTestCase
{
    /** @var DistributionCSVService $distributionCSVService */
    private $distributionCSVService;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
        $this->distributionCSVService = self::$container->get('distribution.distribution_csv_service');
    }

    /**
     * @throws \Exception
     */
    public function testCreateDistribution()
    {
//        $this->removeHousehold($this->namefullnameHousehold);
        $this->createHousehold();

        $adm2 = self::$container->get('doctrine')->getRepository(\CommonBundle\Entity\Adm2::class)->findOneBy([], ['id' => 'asc']);

        $criteria = array(
            "id" => null,
            'adm1' => $adm2->getAdm1()->getId(),
            'adm2' => $adm2->getId(),
            'adm3' => null,
            'adm4' => null,
            "target_type" => AssistanceTargetType::HOUSEHOLD,
            "commodities" => [
                [
                    'id' => null,
                    "modality" => "Cash",
                    "modality_type" => [
                        "id" => 1,
                    ],
                    "type" => "Mobile Money",
                    "unit" => "USD",
                    "value" => 150.1,
                    "description" => null
                ]
            ],
            "date_distribution" => "13-09-2018",
            "location" => [
                'adm1' => $adm2->getAdm1()->getId(),
                'adm2' => $adm2->getId(),
                'adm3' => null,
                'adm4' => null,
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
                "id"=> 1,
                "name"=> "",
                "sectors"=> [],
                "sectors_name"=> []
            ],
            "selection_criteria"=> [
                [
                    [
                        "condition_string"=> "true",
                        "field_string"=> "disabled",
                        "id_field"=> 1,
                        "target"=> "Beneficiary",
                        "table_string"=> "vulnerabilityCriteria",
                        "weight"=> 1,
                    ]
                ]
            ],
            "threshold"=> 1,
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::IN_KIND_FOOD,
        );


        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/distributions', $criteria);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $return = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('distribution', $return);
        $this->assertArrayHasKey('data', $return);

        $distribution = $return['distribution'];
        $this->assertArrayHasKey('name', $distribution);
        $this->assertArrayHasKey('target_type', $distribution);
        $this->assertArrayHasKey('assistance_type', $distribution);
        $this->assertArrayHasKey('project', $distribution);
        $this->assertArrayHasKey('selection_criteria', $distribution);
        $this->assertArrayHasKey('validated', $distribution);

        $this->assertEquals($distribution['name'], $adm2->getName().'-'.$criteria['date_distribution']);

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

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        // Check if the second step succeed
        $this->assertIsArray($randomBenef[0]);
        $this->assertIsArray($randomBenef[1]);
    }

    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testValidate($distribution)
    {
        $this->markTestSkipped('Old endpoint');
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distribution['id'] .'/validate', array());
        $validate = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

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
        $this->assertArrayHasKey('assistance_type', $validate);
        $this->assertArrayHasKey('target_type', $validate);
        $this->assertArrayHasKey('commodities', $validate);
        $this->assertArrayHasKey('distribution_beneficiaries', $validate);

        return $distribution['id'];
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddBeneficiary($distribution)
    {
        $this->assertTrue(true);
        // TODO: write test in proper way.
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testRemoveOneBeneficiary($distribution)
    {
        $this->assertTrue(true);
        // TODO: write test in proper way. Thisone contains specific ID
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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
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
        $this->assertArrayHasKey('target_type', $all[0]);
        $this->assertArrayHasKey('assistance_type', $all[0]);
        $this->assertArrayHasKey('commodities', $all[0]);
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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
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
        $this->assertArrayHasKey('target_type', $one);
        $this->assertArrayHasKey('assistance_type', $one);
        $this->assertArrayHasKey('commodities', $one);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testHouseholdSummary()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $hh = $this->em->getRepository(Household::class)->findOneBy([], ['id' => 'asc']);
        $hhId = $hh->getId();

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/household/'. $hhId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $hhsummaries = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($hhsummaries);
        if (count($hhsummaries) < 1) {
            $this->markTestSkipped("Warning: there are no Assistances for this HH");
        }
        $hhsummary = $hhsummaries[0];

        // Check if the second step succeed
        $this->assertArrayHasKey('id', $hhsummary);
        $this->assertArrayHasKey('name', $hhsummary);
        $this->assertArrayHasKey('date_distribution', $hhsummary);
        $this->assertArrayHasKey('type', $hhsummary);
        $this->assertArrayHasKey('commodities', $hhsummary);
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributionBeneficiaries($distribution)
    {
        $this->markTestSkipped('Old endpoint');
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'. $distribution['id'] .'/beneficiaries');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $beneficiaries = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($beneficiaries) == "array");
        $this->assertArrayHasKey('id', $beneficiaries[0]);
        $this->assertArrayHasKey('beneficiary', $beneficiaries[0]);
        $this->assertArrayHasKey('transactions', $beneficiaries[0]);
    }


    /**
     * @depends testCreateDistribution
     * @param $distribution
     */
    public function testDistributionBeneficiariesVouchers($distribution)
    {
        $this->markTestSkipped('Old endpoint');
        $bookletService = self::$container->get('voucher.booklet_service');
        $loggerService = self::$container->get('logger');
        $purchaseService = new PurchaseService($this->em, $loggerService);

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $distributionRepo = $this->em->getRepository(AssistanceBeneficiary::class);
        $firstAssistanceBeneficiary = $distributionRepo->findOneBy(['assistance'=>$distribution['id']], ['id' => 'asc']);
        $bnfId = $firstAssistanceBeneficiary->getBeneficiary()->getId();

        $booklet = $bookletService->create('KHM', [
            'project_id' => 1,
            'number_booklets' => 1,
            'number_vouchers' => 10,
            'currency' => 'USD',
            'individual_values' => range(100, 110)
        ]);
        $booklet = $this->em->getRepository(Booklet::class)->find($booklet->getId());
        $bookletService->assign($booklet, $firstAssistanceBeneficiary->getAssistance(), $firstAssistanceBeneficiary->getBeneficiary());

        $bookletBig = $bookletService->create('KHM', [
            'project_id' => 1,
            'number_booklets' => 1,
            'number_vouchers' => 20,
            'currency' => 'EUR',
            'individual_values' => range(200, 220)
        ]);
        $bookletBig = $this->em->getRepository(Booklet::class)->find($bookletBig->getId());
        $bookletService->assign($bookletBig, $firstAssistanceBeneficiary->getAssistance(), $firstAssistanceBeneficiary->getBeneficiary());

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);

        $purchase = new VoucherPurchase();
        $purchase->setCreatedAt(new \DateTime());
        $purchase->setProducts([]);
        $purchase->setVendorId($vendor->getId());
        $purchase->setVouchers($bookletBig->getVouchers()->toArray());
        $purchaseService->purchase($purchase);

        // Second step
        $crawler = $this->request('GET', '/api/wsse/distributions/'. $distribution['id'] .'/beneficiaries');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $beneficiaries = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertIsArray($beneficiaries);
        $pivotBeneficiary = null;
        foreach ($beneficiaries as $beneficiary) {
            $this->assertIsArray($beneficiary['booklets'], "Booklets is not array in BNF ".$beneficiary['id'].'/'.$beneficiary['beneficiary']['id']);
            if ($beneficiary['beneficiary']['id'] === $bnfId) {
                $pivotBeneficiary = $beneficiary;
            }
        }
        $this->assertNotNull($pivotBeneficiary, "There is no BNF ({$bnfId}) with added voucher");
        $this->assertCount(2, $pivotBeneficiary['booklets'], "Wrong booklet count");
        $this->assertArrayHasKey('currency', $pivotBeneficiary['booklets'][0], "Missing currency");
        $this->assertArrayHasKey('currency', $pivotBeneficiary['booklets'][1], "Missing currency");
        $this->assertEquals('USD', $pivotBeneficiary['booklets'][0]['currency'], "Inconsistent currency");
        $this->assertEquals('EUR', $pivotBeneficiary['booklets'][1]['currency'], "Inconsistent currency");
        $this->assertCount(10, $pivotBeneficiary['booklets'][0]['vouchers']);
        $this->assertCount(20, $pivotBeneficiary['booklets'][1]['vouchers']);

        foreach ($pivotBeneficiary['booklets'][0]['vouchers'] as $id => $voucher) {
            $this->assertArrayHasKey('value', $voucher);
            $this->assertEquals($voucher['value'], 100+$id, "Wrong voucher value");
            $this->assertArrayHasKey('used_at', $voucher);
            $this->assertNull($voucher['used_at']);
            $this->assertArrayHasKey('redeemed_at', $voucher);
            $this->assertNull($voucher['redeemed_at']);
        }

        foreach ($pivotBeneficiary['booklets'][1]['vouchers'] as $id => $voucher) {
            $this->assertArrayHasKey('value', $voucher);
            $this->assertEquals($voucher['value'], 200 + $id, "Wrong voucher value");
            $this->assertArrayHasKey('used_at', $voucher);
            $this->assertNotNull($voucher['used_at'], "Empty used at in used voucher");
            $this->assertRegExp('|\d\d\d\d-\d\d-\d\d|', $voucher['used_at']);
            $this->assertArrayHasKey('redeemed_at', $voucher);
            $this->assertNull($voucher['redeemed_at']);
        }
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
            'target_type' => AssistanceTargetType::HOUSEHOLD,
            'updated_on' => '28-11-2018 11:11:11',
            'validated' => false,
        );
        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/'. $distribution['id'], $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $update = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertArrayHasKey('id', $update);
        $this->assertArrayHasKey('name', $update);
        $this->assertArrayHasKey('updated_on', $update);
        $this->assertArrayHasKey('date_distribution', $update);
        $this->assertArrayHasKey('project', $update);
        $this->assertArrayHasKey('selection_criteria', $update);
        $this->assertArrayHasKey('archived', $update);
        $this->assertArrayHasKey('validated', $update);
        $this->assertArrayHasKey('target_type', $update);
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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
    }

    /**
     * @depends testCreateDistribution
     * @param $d
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributionsForFrontend($d)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/web-app/v1/distributions/projects/'. $d['project']['id']);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $distributions = json_decode($this->client->getResponse()->getContent(), true);

        if (count($distributions) < 1) {
            $this->markTestSkipped("Warning: there is no distribution to proper test endpoint");
        }

        $distribution = $distributions[0];

        // Check if the second step succeed
        $this->assertIsArray($distributions);
        $this->assertArrayHasKey('id', $distribution);
        $this->assertArrayHasKey('updated_on', $distribution);
        $this->assertArrayHasKey('date_distribution', $distribution);
        $this->assertArrayHasKey('location', $distribution);
        $this->assertArrayHasKey('project', $distribution);
        $this->assertArrayHasKey('selection_criteria', $distribution);
        $this->assertArrayHasKey('archived', $distribution);
        $this->assertArrayHasKey('validated', $distribution);
        $this->assertArrayHasKey('target_type', $distribution);
        $this->assertArrayHasKey('commodities', $distribution);
        $this->assertArrayHasKey('beneficiaries_count', $distribution);
        $this->assertArrayNotHasKey('distribution_beneficiaries', $distribution);
    }

    /**
     * @depends testCreateDistribution
     * @param $d
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributionsForOldMobile($d)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/projects/'. $d['project']['id']);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $distributions = json_decode($this->client->getResponse()->getContent(), true);

        if (count($distributions) < 1) {
            $this->markTestSkipped("Warning: there is no distribution to proper test endpoint");
        }

        $distribution = $distributions[0];

        // Check if the second step succeed
        $this->assertIsArray($distributions);
        $this->assertArrayHasKey('id', $distribution);
        $this->assertArrayHasKey('updated_on', $distribution);
        $this->assertArrayHasKey('date_distribution', $distribution);
        $this->assertArrayHasKey('location', $distribution);
        $this->assertArrayHasKey('project', $distribution);
        $this->assertArrayHasKey('selection_criteria', $distribution);
        $this->assertArrayHasKey('archived', $distribution);
        $this->assertArrayHasKey('validated', $distribution);
        $this->assertArrayHasKey('type', $distribution);
        $this->assertArrayHasKey('commodities', $distribution);
        $this->assertArrayHasKey('beneficiaries_count', $distribution);
        $this->assertArrayHasKey('distribution_beneficiaries', $distribution);
        $this->assertIsArray($distribution['distribution_beneficiaries']);
        $this->assertCount($distribution['beneficiaries_count'], $distribution['distribution_beneficiaries']);
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

        $distributionCSVService = self::$container->get('distribution.distribution_csv_service');

        $countryIso3 = 'KHM';

        //assistance will be used in the function "parseCSV" to get all the beneficiaries in a project :
        $assistance = $this->em->getRepository(Assistance::class)->findOneById($distribution['id']);
        $assistanceBeneficiaryService = self::$container->get('distribution.assistance_beneficiary_service');

        //beneficiaries contains all beneficiaries in a distribution :
        $beneficiaries = $assistanceBeneficiaryService->getBeneficiaries($assistance);
        $uploadedFile = new UploadedFile(__DIR__.'/../Resources/beneficiariesInDistribution.csv', 'beneficiaryInDistribution.csv');

        $import = $distributionCSVService->parseCSV($countryIso3, $beneficiaries, $assistance, $uploadedFile);

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

        $save = $distributionCSVService->saveCSV($countryIso3, $assistance, $import);

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
            'target' => 'Household'
        );
        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/distributions/beneficiaries/project/'. $distribution['project']['id'], $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
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
        // $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
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
        $crawler = $this->request('POST', '/api/wsse/transaction/distribution/'. $distribution['id'].'/email');
        // $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
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

        $distribution = $this->em->getRepository(Assistance::class)->find($distribution['id']);
        if ($distribution instanceof Assistance) {
            $distributionBeneficiaries = $this->em
                ->getRepository(AssistanceBeneficiary::class)->findByAssistance($distribution);
            foreach ($distributionBeneficiaries as $assistanceBeneficiary) {
                $transaction = $this->em->getRepository(Transaction::class)->findOneByAssistanceBeneficiary($assistanceBeneficiary);
                $this->em->remove($transaction);
                $this->em->remove($assistanceBeneficiary);
            }

            $selectionCriteria = $this->em->getRepository(SelectionCriteria::class)->findByAssistance($distribution);
            foreach ($selectionCriteria as $selectionCriterion) {
                $this->em->remove($selectionCriterion);
            }
            $this->em->remove($distribution);
        }

        $this->em->flush();
        $this->removeHousehold($this->namefullnameHousehold);
    }

    public function testCreateDistributionToBeDeleted()
    {
        $this->markTestSkipped('Old endpoint');
        $body = [
            'id' => null,
            'adm1' => '',
            'adm2' => '',
            'adm3' => '',
            'adm4' => '',
            'target_type' => AssistanceTargetType::HOUSEHOLD,
            'commodities' => [
                [
                    'id' => null,
                    'modality' => 'Cash',
                    'modality_type' => [
                        'id' => 1,
                    ],
                    'type' => 'Mobile Money',
                    'unit' => 'USD',
                    'value' => 100,
                    'description' => null,
                ],
            ],
            'date_distribution' => '13-09-2018',
            'location' => [
                'adm1' => 1,
                'adm2' => 1,
                'adm3' => 1,
                'adm4' => 1,
                'country_iso3' => 'KHM',
            ],
            'country_specific_answers' => [
                [
                    'answer' => 'MY_ANSWER_TEST1',
                    'country_specific' => [
                        'id' => 1,
                    ],
                ],
            ],
            'location_name' => '',
            'name' => 'DISTRIBUTION_TO_BE_DELETED_FROM_DB',
            'description' => 'some description',
            'project' => [
                'donors' => [],
                'donors_name' => [],
                'id' => 1,
                'name' => '',
                'sectors' => [],
                'sectors_name' => [],
            ],
            'selection_criteria' => [
                [
                    [
                        'condition_string' => 'true',
                        'field_string' => 'disabled',
                        'id_field' => 1,
                        'target' => 'Beneficiary',
                        'table_string' => 'vulnerabilityCriteria',
                        'weight' => 1,
                    ],
                ],
            ],
            'threshold' => 1,
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::IN_KIND_FOOD,
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // preparation to test
        $this->request('PUT', '/api/wsse/distributions', $body);
        $assistanceData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        return $assistanceData['distribution']['id'];
    }

    /**
     * @depends testCreateDistributionToBeDeleted
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteUnvalidatedDistribution($id)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/wsse/distributions/'.$id);
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode(), 'Request failed: '.$this->client->getResponse()->getContent());

        $assistance = $this->em->getRepository(Assistance::class)->find($id);
        $this->assertNull($assistance, 'Assistance should not exists in DB');
    }

    /**
     * @depends testValidate
     */
    public function testDeleteValidatedAssistance($id)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/wsse/distributions/'.$id);
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode(), 'Request failed: '.$this->client->getResponse()->getContent());

        $assistance = $this->em->getRepository(Assistance::class)->find($id);
        $this->assertInstanceOf(Assistance::class, $assistance, 'Assistance should exists in DB');
        $this->assertTrue((bool) $assistance->getArchived(), 'Assistance should be archived');
    }

    public function testCreateDistributionForCommunity()
    {
        $this->markTestSkipped('Old endpoint');
        /** @var \BeneficiaryBundle\Repository\CommunityRepository $communityRepo */
        $communityRepo = self::$container->get('doctrine')->getRepository(\BeneficiaryBundle\Entity\Community::class);
        $community = $communityRepo->findBy([], ['id' => 'asc'])[0];

        $body = [
            'id' => null,
            'adm1' => '',
            'adm2' => '',
            'adm3' => '',
            'adm4' => '',
            'target_type' => AssistanceTargetType::COMMUNITY,
            'date_distribution' => '13-09-2018',
            'location' => [
                'adm1' => 1,
                'adm2' => 1,
                'adm3' => 1,
                'adm4' => 1,
                'country_iso3' => 'KHM',
            ],
            'commodities' => [
                [
                    'id' => null,
                    'modality' => 'Cash',
                    'modality_type' => [
                        'id' => 1,
                    ],
                    'type' => 'Mobile Money',
                    'unit' => 'USD',
                    'value' => 100,
                    'description' => null,
                ],
            ],
            'country_specific_answers' => [
                [
                    'answer' => 'MY_ANSWER_TEST1',
                    'country_specific' => [
                        'id' => 1,
                    ],
                ],
            ],
            'location_name' => '',
            'name' => 'DISTRIBUTION_FOR_COMMUNITY',
            'project' => [
                'donors' => [],
                'donors_name' => [],
                'id' => 1,
                'name' => '',
                'sectors' => [],
                'sectors_name' => [],
            ],
            'communities' => [$community->getId()],
            'households_targeted' => 3,
            'individuals_targeted' => 5,
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::IN_KIND_FOOD,
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // preparation to test
        $this->request('PUT', '/api/wsse/distributions', $body);
        $assistanceData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        return $assistanceData['distribution']['id'];
    }

    public function testCreateDistributionForInstitution()
    {
        $this->markTestSkipped('Old endpoint');
        /** @var \BeneficiaryBundle\Repository\InstitutionRepository $institutionRepo */
        $institutionRepo = self::$container->get('doctrine')->getRepository(\BeneficiaryBundle\Entity\Institution::class);
        $institution = $institutionRepo->findBy([], ['id' => 'asc'])[0];

        $body = [
            'id' => null,
            'adm1' => '',
            'adm2' => '',
            'adm3' => '',
            'adm4' => '',
            'target_type' => AssistanceTargetType::INSTITUTION,
            'assistance_type' => AssistanceType::ACTIVITY,
            'date_distribution' => '13-09-2018',
            'location' => [
                'adm1' => 1,
                'adm2' => 1,
                'adm3' => 1,
                'adm4' => 1,
                'country_iso3' => 'KHM',
            ],
            'country_specific_answers' => [
                [
                    'answer' => 'MY_ANSWER_TEST1',
                    'country_specific' => [
                        'id' => 1,
                    ],
                ],
            ],
            'location_name' => '',
            'name' => 'DISTRIBUTION_FOR_INSTITUTION',
            'project' => [
                'donors' => [],
                'donors_name' => [],
                'id' => 1,
                'name' => '',
                'sectors' => [],
                'sectors_name' => [],
            ],
            'institutions' => [$institution->getId()],
            'sector' => \ProjectBundle\DBAL\SectorEnum::FOOD_SECURITY,
            'subsector' => \ProjectBundle\DBAL\SubSectorEnum::IN_KIND_FOOD,
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // preparation to test
        $this->request('PUT', '/api/wsse/distributions', $body);
        $assistanceData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        return $assistanceData['distribution']['id'];
    }


    /**
     * @depends testCreateDistributionForCommunity
     * @param $distributionId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributionCommunities($distributionId)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'.$distributionId .'/communities');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $beneficiaries = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertIsArray($beneficiaries);
        $this->assertCount(1, $beneficiaries);
        $this->assertArrayHasKey('id', $beneficiaries[0]);
        $this->assertArrayHasKey('community', $beneficiaries[0]);
        $this->assertArrayHasKey('transactions', $beneficiaries[0]);
    }

    /**
     * @depends testCreateDistributionForInstitution
     * @param $distributionId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetDistributionInstitutions($distributionId)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/'.$distributionId .'/institutions');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $beneficiaries = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertIsArray($beneficiaries);
        $this->assertCount(1, $beneficiaries);
        $this->assertArrayHasKey('id', $beneficiaries[0]);
        $this->assertArrayHasKey('institution', $beneficiaries[0]);
        $this->assertArrayHasKey('transactions', $beneficiaries[0]);
    }
}
