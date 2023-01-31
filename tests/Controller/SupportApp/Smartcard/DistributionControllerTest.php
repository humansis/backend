<?php

declare(strict_types=1);

namespace Tests\Controller\SupportApp\Smartcard;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\Beneficiary;
use Entity\Smartcard;
use Entity\SmartcardDeposit;
use Entity\User;
use Enum\ReliefPackageState;
use Repository\AssistanceBeneficiaryRepository;
use Repository\AssistanceRepository;
use Repository\BeneficiaryRepository;
use Repository\SmartcardRepository;
use Repository\UserRepository;
use Tests\BMSServiceTestCase;

class DistributionControllerTest extends BMSServiceTestCase
{
    private AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository;


    private AssistanceRepository $assistanceRepository;


    private BeneficiaryRepository $beneficiaryRepository;


    private UserRepository $userRepository;


    private SmartcardRepository $smartcardRepository;

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
        $this->smartcardRepository = self::getContainer()->get('doctrine')->getRepository(Smartcard::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
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
                    if ($oldReliefPackage) {
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


        $smartcard = $this->smartcardRepository->findBySerialNumberAndBeneficiary($beneficiary->getSmartcardSerialNumber(), $beneficiary);

        $smartcardDeposit = new SmartcardDeposit();
        $smartcardDeposit = $smartcardDeposit::create($smartcard, $user, $reliefPackage, 45, 0, new \DateTime('@' . strtotime('now')), '2b69fe65aab80651d0075cf8e9ff4f12');
        $this->em->persist($smartcardDeposit);
        $this->em->flush();
        $this->em->refresh($assistanceBeneficiary);
        $this->em->refresh($reliefPackage);
        $this->em->refresh($smartcardDeposit);

        $assistanceID = $assistance->getId();
        $beneficiaryID = $beneficiary->getId();
        $smartcardCode = $smartcard->getSerialNumber();

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
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
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
                    if ($oldReliefPackage) {
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


        $smartcard = $this->smartcardRepository->findBySerialNumberAndBeneficiary($beneficiary->getSmartcardSerialNumber(), $beneficiary);

        $smartcardDeposit = new SmartcardDeposit();
        $smartcardDeposit = $smartcardDeposit::create($smartcard, $user, $reliefPackage, 45, 0, new \DateTime('@' . strtotime('now')), '2b69fe65aab80651d0075cf8e9ff4f12');
        $this->em->persist($smartcardDeposit);
        $this->em->flush();
        $this->em->refresh($assistanceBeneficiary);
        $this->em->refresh($reliefPackage);
        $this->em->refresh($smartcardDeposit);

        $assistanceID = $assistance->getId();
        $beneficiaryID = $beneficiary->getId();
        $smartcardCode = $smartcard->getSerialNumber();

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
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    //this test for correct  scenario
    public function testResetingReliefPackage()
    {
        $assistance = $this->assistanceRepository->findOneBy([], ['id' => 'ASC']);
        $beneficiary = $this->beneficiaryRepository->findOneBy([], ['id' => 'ASC']);
        $user = $this->userRepository->find($this->getTestUser()->getId());

        $oldAssistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary($assistance->getId(), $beneficiary->getId());
        if ($oldAssistanceBeneficiary) {
            $oldReliefPackages = $oldAssistanceBeneficiary->getReliefPackages();
            if (!$oldReliefPackages->isEmpty()) {
                foreach ($oldReliefPackages as $oldReliefPackage) {
                    if ($oldReliefPackage) {
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


        $smartcard = $this->smartcardRepository->findBySerialNumberAndBeneficiary($beneficiary->getSmartcardSerialNumber(), $beneficiary);

        $smartcardDeposit = new SmartcardDeposit();
        $smartcardDeposit = $smartcardDeposit::create($smartcard, $user, $reliefPackage, 45, 0, new \DateTime('@' . strtotime('now')), '2b69fe65aab80651d0075cf8e9ff4f12');
        $this->em->persist($smartcardDeposit);
        $this->em->flush();
        $this->em->refresh($assistanceBeneficiary);
        $this->em->refresh($reliefPackage);
        $this->em->refresh($smartcardDeposit);
        $assistanceID = $assistance->getId();
        $beneficiaryID = $beneficiary->getId();
        $smartcardCode = $smartcard->getSerialNumber();
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
}
