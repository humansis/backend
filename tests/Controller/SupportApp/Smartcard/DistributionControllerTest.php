<?php

declare(strict_types=1);

namespace Tests\Controller\SupportApp\Smartcard;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\Beneficiary;
use Entity\SmartcardBeneficiary;
use Entity\SmartcardDeposit;
use Entity\User;
use Enum\ModalityType;
use Enum\ReliefPackageState;
use Exception;
use Repository\Assistance\ReliefPackageRepository;
use Repository\AssistanceBeneficiaryRepository;
use Repository\AssistanceRepository;
use Repository\BeneficiaryRepository;
use Repository\SmartcardBeneficiaryRepository;
use Repository\UserRepository;
use Tests\BMSServiceTestCase;

class DistributionControllerTest extends BMSServiceTestCase
{
    private AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository;

    private AssistanceRepository $assistanceRepository;

    private BeneficiaryRepository $beneficiaryRepository;

    private UserRepository $userRepository;

    private SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository;

    private ReliefPackageRepository $reliefPackageRepository;

    /** @var EntityManagerInterface */
    protected $em;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');

        $this->assistanceBeneficiaryRepository = self::getContainer()->get('doctrine')->getRepository(AssistanceBeneficiary::class);
        $this->assistanceRepository = self::getContainer()->get('doctrine')->getRepository(Assistance::class);
        $this->beneficiaryRepository = self::getContainer()->get('doctrine')->getRepository(Beneficiary::class);
        $this->userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $this->smartcardBeneficiaryRepository = self::getContainer()->get('doctrine')->getRepository(SmartcardBeneficiary::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->reliefPackageRepository = self::getContainer()->get(ReliefPackageRepository::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    //this test for incorrect scenario
    public function testIncorrectResetingReliefPackage1()
    {
        $assistance = $this->assistanceRepository->findOneBy([], ['id' => 'ASC']);
        $beneficiary = $this->beneficiaryRepository->findOneBy([], ['id' => 'ASC']);
        $user = $this->userRepository->find($this->getTestUser()->getId());

        $oldAssistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary($assistance->getId(), $beneficiary->getId());
        if ($oldAssistanceBeneficiary) {
            $oldReliefPackages = $oldAssistanceBeneficiary->getReliefPackages();
            if (!$oldReliefPackages->isEmpty()) {
                foreach ($oldReliefPackages as $oldReliefPackage) {
                    $oldSmartcardDeposits = $oldReliefPackage->getSmartcardDeposits();
                    if (!$oldSmartcardDeposits->isEmpty()) {
                        foreach ($oldSmartcardDeposits as $oldSmartcardDeposit) {
                            $this->em->remove($oldSmartcardDeposit);
                            $this->em->flush();
                        }
                    }
                    $this->em->remove($oldReliefPackage);
                    $this->em->flush();
                }
            }
            $this->em->remove($oldAssistanceBeneficiary);
            $this->em->flush();
        }


        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setAssistance($assistance);
        $assistanceBeneficiary->setBeneficiary($beneficiary);
        $this->em->persist($assistanceBeneficiary);


        $reliefPackage = new Assistance\ReliefPackage(
            $assistanceBeneficiary,
            'Smartcard',
            45,
            'KHR',
            ReliefPackageState::DISTRIBUTED,
            45
        );
        $reliefPackage->setDistributedBy($user);
        $this->em->persist($reliefPackage);


        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findBy(['beneficiary' => $beneficiary->getId()], ['id' => 'desc'])[0];
        $smartcardDeposit = new SmartcardDeposit(
            $smartcardBeneficiary,
            $user,
            $reliefPackage,
            45,
            0,
            new DateTime('@' . strtotime('now')),
        );
        $this->em->persist($smartcardDeposit);
        $this->em->flush();
        $this->em->refresh($assistanceBeneficiary);
        $this->em->refresh($reliefPackage);
        $this->em->refresh($smartcardDeposit);

        $beneficiaryID = $beneficiary->getId();
        $smartcardCode = $smartcardBeneficiary->getSerialNumber();

        $this->request(
            'DELETE',
            '/api/basic/support-app/v1/smartcard/distribution',
            [
                "assistanceId" => 9999999,
                "beneficiaryId" => $beneficiaryID,
                "smartcardCode" => $smartcardCode
            ]
        );
        $this->assertEquals(
            400,
            $this->client->getResponse()->getStatusCode(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    //this test for incorrect scenario
    public function testIncorrectResetingReliefPackage2()
    {
        $assistance = $this->assistanceRepository->findOneBy([], ['id' => 'ASC']);
        $beneficiary = $this->beneficiaryRepository->findOneBy([], ['id' => 'ASC']);
        $user = $this->userRepository->find($this->getTestUser()->getId());

        $oldAssistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary($assistance->getId(), $beneficiary->getId());
        if ($oldAssistanceBeneficiary) {
            $oldReliefPackages = $oldAssistanceBeneficiary->getReliefPackages();
            if (!$oldReliefPackages->isEmpty()) {
                foreach ($oldReliefPackages as $oldReliefPackage) {
                    $oldSmartcardDeposits = $oldReliefPackage->getSmartcardDeposits();
                    if (!$oldSmartcardDeposits->isEmpty()) {
                        foreach ($oldSmartcardDeposits as $oldSmartcardDeposit) {
                            $this->em->remove($oldSmartcardDeposit);
                            $this->em->flush();
                        }
                    }
                    $this->em->remove($oldReliefPackage);
                    $this->em->flush();
                }
            }
            $this->em->remove($oldAssistanceBeneficiary);
            $this->em->flush();
        }

        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setAssistance($assistance);
        $assistanceBeneficiary->setBeneficiary($beneficiary);
        $this->em->persist($assistanceBeneficiary);


        $reliefPackage = new Assistance\ReliefPackage(
            $assistanceBeneficiary,
            'Smartcard',
            45,
            'KHR',
            ReliefPackageState::DISTRIBUTED,
            45
        );
        $reliefPackage->setDistributedBy($user);
        $this->em->persist($reliefPackage);

        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findBy(['beneficiary' => $beneficiary->getId()], ['id' => 'desc'])[0];

        $smartcardDeposit = new SmartcardDeposit(
            $smartcardBeneficiary,
            $user,
            $reliefPackage,
            45,
            0,
            new DateTime('@' . strtotime('now')),
        );
        $this->em->persist($smartcardDeposit);
        $this->em->flush();
        $this->em->refresh($assistanceBeneficiary);
        $this->em->refresh($reliefPackage);
        $this->em->refresh($smartcardDeposit);

        $assistanceID = $assistance->getId();
        $beneficiaryID = $beneficiary->getId();

        $this->request(
            'DELETE',
            '/api/basic/support-app/v1/smartcard/distribution',
            [
                "assistanceId" => $assistanceID,
                "beneficiaryId" => $beneficiaryID,
                "smartcardCode" => 99999999
            ]
        );
        $this->assertEquals(
            400,
            $this->client->getResponse()->getStatusCode(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    //this test for correct  scenario
    public function testResetReliefPackage()
    {
        $assistance = $this->assistanceRepository->findOneBy([], ['id' => 'ASC']);
        $beneficiary = $this->beneficiaryRepository->findOneBy([], ['id' => 'ASC']);
        $user = $this->userRepository->find($this->getTestUser()->getId());

        $oldAssistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary($assistance->getId(), $beneficiary->getId());
        if ($oldAssistanceBeneficiary) {
            $oldReliefPackages = $oldAssistanceBeneficiary->getReliefPackages();
            if (!$oldReliefPackages->isEmpty()) {
                foreach ($oldReliefPackages as $oldReliefPackage) {
                    $oldSmartcardDeposits = $oldReliefPackage->getSmartcardDeposits();
                    if (!$oldSmartcardDeposits->isEmpty()) {
                        foreach ($oldSmartcardDeposits as $oldSmartcardDeposit) {
                            $this->em->remove($oldSmartcardDeposit);
                            $this->em->flush();
                        }
                    }
                    $this->em->remove($oldReliefPackage);
                    $this->em->flush();
                }
            }
            $this->em->remove($oldAssistanceBeneficiary);
            $this->em->flush();
        }

        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setAssistance($assistance);
        $assistanceBeneficiary->setBeneficiary($beneficiary);
        $this->em->persist($assistanceBeneficiary);


        $reliefPackage = new Assistance\ReliefPackage(
            $assistanceBeneficiary,
            'Smartcard',
            45,
            'KHR',
            ReliefPackageState::DISTRIBUTED,
            45
        );
        $reliefPackage->setDistributedBy($user);
        $this->em->persist($reliefPackage);


        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findBy(['beneficiary' => $beneficiary->getId()], ['id' => 'desc'])[0];
        $smartcardDeposit = new SmartcardDeposit(
            $smartcardBeneficiary,
            $user,
            $reliefPackage,
            45,
            0,
            new DateTime('@' . strtotime('now')),
        );
        $this->em->persist($smartcardDeposit);
        $this->em->flush();
        $this->em->refresh($assistanceBeneficiary);
        $this->em->refresh($reliefPackage);
        $this->em->refresh($smartcardDeposit);
        $assistanceID = $assistance->getId();
        $beneficiaryID = $beneficiary->getId();
        $smartcardCode = $smartcardBeneficiary->getSerialNumber();
        $this->request(
            'DELETE',
            '/api/basic/support-app/v1/smartcard/distribution',
            [
                "assistanceId" => $assistanceID,
                "beneficiaryId" => $beneficiaryID,
                "smartcardCode" => $smartcardCode
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testManualDistribution(): void
    {
        $this->em->beginTransaction();
        $reliefPackage = $this->reliefPackageRepository->findOneBy(
            ['state' => ReliefPackageState::TO_DISTRIBUTE, 'modalityType' => ModalityType::SMART_CARD]
        );
        if (!$reliefPackage) {
            $this->markTestSkipped('There is no usable Relief Package');
        }

        $this->request(
            'POST',
            '/api/basic/support-app/v1/smartcard/distribution',
            [
                'reliefPackageId' => $reliefPackage->getId(),
                'value' => 20,
                'createdAt' => '2023-01-01T00:01:01Z',
                'createdBy' => $this->getTestUser()->getId(),
                'smartcardCode' => 'AABBBCCC',
                'note' => 'Test note',
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->em->refresh($reliefPackage);
        $this->assertEquals($this->getTestUser()->getId(), $reliefPackage->getDistributedBy()->getId());
        $this->assertEquals(20, $reliefPackage->getAmountDistributed());
        $this->assertEquals('Test note', $reliefPackage->getNotes());

        $this->em->rollback();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testManualDistributionWithoutCheckingWorkflow(): void
    {
        $this->em->beginTransaction();
        $reliefPackage = $this->reliefPackageRepository->findOneBy(
            ['modalityType' => ModalityType::SMART_CARD]
        );
        if (!$reliefPackage) {
            $this->markTestSkipped('There is no usable Relief Package');
        }
        $valueToDistribute = $reliefPackage->getAmountToDistribute();
        $spent = $reliefPackage->getAmountSpent();
        $reliefPackage->setState(ReliefPackageState::DISTRIBUTED);
        $reliefPackage->setAmountDistributed('0');
        $this->em->persist($reliefPackage);
        $this->em->flush();

        $this->request(
            'POST',
            '/api/basic/support-app/v1/smartcard/distribution',
            [
                'reliefPackageId' => $reliefPackage->getId(),
                'value' => null,
                'checkState' => false,
                'createdAt' => '2023-01-01T00:01:01Z',
                'createdBy' => $this->getTestUser()->getId(),
                'smartcardCode' => 'AABBBCCC',
                'note' => 'Test note',
                'spent' => "100.00",
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->em->refresh($reliefPackage);
        $this->assertEquals(100 + $spent, $reliefPackage->getAmountSpent());
        $this->assertEquals($valueToDistribute, $reliefPackage->getAmountDistributed());
        $this->assertEquals(false, $reliefPackage->getSmartcardDeposits()[0]->isSuspicious());

        $this->em->rollback();
    }
}
