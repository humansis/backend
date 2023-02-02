<?php

declare(strict_types=1);

namespace Tests\Controller\SupportApp\Smartcard;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\SmartcardDeposit;
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

    protected $em;

    private $container;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();
        $this->container = self::getContainer();
        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');

        $this->assistanceBeneficiaryRepository = $this->container->get(AssistanceBeneficiaryRepository::class);
        $this->assistanceRepository = $this->container->get(AssistanceRepository::class);
        $this->beneficiaryRepository = $this->container->get(BeneficiaryRepository::class);
        $this->userRepository = $this->container->get(UserRepository::class);
        $this->smartcardRepository = $this->container->get(SmartcardRepository::class);
        $this->em = $this->container->get(EntityManagerInterface::class);
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


        $smartcard = $this->smartcardRepository->findBy(['beneficiary' => $beneficiary->getId()], ['id' => 'desc'])[0];

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

        $smartcard = $this->smartcardRepository->findBy(['beneficiary' => $beneficiary->getId()], ['id' => 'desc'])[0];

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


        $smartcard = $this->smartcardRepository->findBy(['beneficiary' => $beneficiary->getId()], ['id' => 'desc'])[0];

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
