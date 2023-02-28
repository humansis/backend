<?php

namespace Tests\Utils;

use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use Component\Smartcard\Invoice\Exception\AlreadyRedeemedInvoiceException;
use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use Component\Smartcard\Invoice\InvoiceFactory;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Location;
use Entity\Beneficiary;
use DateTime;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Doctrine\Persistence\ObjectManager;
use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use Entity\Assistance\ReliefPackage;
use Entity\Smartcard\PreliminaryInvoice;
use Enum\ModalityType;
use Enum\ReliefPackageState;
use Enum\SmartcardStates;
use InputType\Smartcard\DepositInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
use InputType\SmartcardInvoiceCreateInputType;
use Psr\Cache\InvalidArgumentException;
use Repository\Assistance\ReliefPackageRepository;
use Repository\RoleRepository;
use Repository\BeneficiaryRepository;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Entity\User;
use Entity\Product;
use Entity\Smartcard;
use Entity\SmartcardDeposit;
use Entity\Vendor;
use InputType\SmartcardPurchase;
use Tests\ComponentHelper\DepositHelper;
use Tests\ComponentHelper\SmartcardHelper;
use Utils\SmartcardService;

class SmartcardServiceTest extends KernelTestCase
{
    use SmartcardHelper;
    use DepositHelper;

    final public const VENDOR_USERNAME = 'one-purpose-vendor@example.org';

    /** @var ObjectManager|null */
    private ?ObjectManager $em;

    private SmartcardService $smartcardService;

    private ?Vendor $vendor = null;

    private string $smartcardNumber = '';

    private DepositFactory $depositFactory;

    private InvoiceFactory $invoiceFactory;

    private ?User $user = null;

    private PreliminaryInvoiceRepository $preliminaryInvoiceRepository;

    private RoleRepository $roleRepository;

    private ReliefPackageRepository $reliefPackageRepository;

    private BeneficiaryRepository $beneficiaryRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        //Preparing the EntityManager
        $this->em = self::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->smartcardService = self::getContainer()->get('smartcard_service');
        $this->depositFactory = self::getContainer()->get(DepositFactory::class);
        $this->invoiceFactory = self::getContainer()->get(InvoiceFactory::class);
        $this->roleRepository = self::getContainer()->get(RoleRepository::class);
        $this->preliminaryInvoiceRepository = self::getContainer()->get(PreliminaryInvoiceRepository::class);
        $this->reliefPackageRepository = self::getContainer()->get(ReliefPackageRepository::class);
        $this->beneficiaryRepository = self::getContainer()->get(BeneficiaryRepository::class);

        $this->createTempVendor();
        $this->em->persist($this->vendor);
        $this->em->persist($this->user);

        $this->smartcardNumber = substr(md5((uniqid())), 0, 7);
        $this->em->flush();
    }

    public function validSmartcardCashflows(): array
    {
        //TODO rely on IDs is REALLY BAD PRACTICE
        $projectA = 3;
        $projectB = 10;
        $assistanceA1 = 51; // USD
        $assistanceA2 = 240; // SYP
        $assistanceB1 = 170; // USD
        $beneficiaryA1 = 2;
        $beneficiaryA2 = 4;
        $beneficiaryB1 = 250;

        return [
            'vendor has nothing' => [
                [],
                [],
            ],
            'deposit alone' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 20.112, 'USD', $assistanceA1],
                ],
                [], // there is nothing to redeem
            ],
            'trivial' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 300.1, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 500.8, 'USD', $assistanceA1],
                ],
                [
                    [500.8, 'USD', $projectA],
                ],
            ],
            'multiproject' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 100, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 200, 'USD', $assistanceA1],
                    [$beneficiaryB1, 'register', $assistanceB1],
                    [$beneficiaryB1, 'deposit', 20, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'purchase', 40, 'USD', $assistanceB1],
                ],
                [
                    [200, 'USD', $projectA],
                    [40, 'USD', $projectB],
                ],
            ],
            'multicurrency' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 100, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 200, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 20, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 40, 'SYP', $assistanceA1],
                ],
                [
                    [200, 'USD', $projectA],
                    [40, 'SYP', $projectA],
                ],
            ],
            'multiproject and multicurrency' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA2],
                    [$beneficiaryA1, 'deposit', 100, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 200, 'SYP', $assistanceA1],
                    [$beneficiaryA1, 'register', $assistanceB1],
                    [$beneficiaryA1, 'deposit', 20, 'USD', $assistanceB1],
                    [$beneficiaryA1, 'purchase', 40, 'USD', $assistanceB1],
                ],
                [
                    [200, 'SYP', $projectA],
                    [40, 'USD', $projectB],
                ],
            ],
            'chaos' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 100, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 10, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 20, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 30, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 40, 'USD', $assistanceA1],
                    [$beneficiaryA2, 'register', $assistanceA2],
                    [$beneficiaryA2, 'deposit', 20, 'SYP', $assistanceA2],
                    [$beneficiaryA2, 'purchase', 40, 'SYP', $assistanceA2],
                    [$beneficiaryB1, 'register', $assistanceB1],
                    [$beneficiaryB1, 'deposit', 500, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'purchase', 40, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'deposit', 500, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'purchase', 40, 'USD', $assistanceB1],
                    [$beneficiaryA1, 'register', $assistanceA2],
                    [$beneficiaryA1, 'deposit', 1000, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 100, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 100, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 100, 'SYP', $assistanceA2],
                ],
                [
                    [100, 'USD', $projectA],
                    [340, 'SYP', $projectA],
                    [80, 'USD', $projectB],
                ],
            ],
        ];
    }

    /**
     * @dataProvider validSmartcardCashflows
     *
     *
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws DoubledDepositException
     * @throws AlreadyRedeemedInvoiceException
     * @throws NotRedeemableInvoiceException
     */
    public function testSmartcardCashflows(array $actions, array $expectedResults): void
    {
        $admin = $this->user;
        $assistanceRepository = $this->em->getRepository(Assistance::class);
        $product = $this->em->getRepository(Product::class)->findOneBy(['countryIso3' => 'SYR'], ['id' => 'asc']);

        $date = DateTime::createFromFormat('Y-m-d', '2000-01-01');
        foreach ($actions as $actionData) {
            switch ($actionData[1]) {
                case 'register':
                    [$beneficiaryId, $action, $assistanceId] = $actionData;
                    /** @var Assistance $assistance */
                    $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                    $beneficiary = $assistance->getDistributionBeneficiaries()->get(0)->getBeneficiary();
                    $registerInputType = SmartcardRegisterInputType::create(
                        $this->smartcardNumber,
                        $beneficiaryId,
                        $date,
                    );
                    try {
                        $this->smartcardService->register($registerInputType);
                    } catch (SmartcardDoubledRegistrationException) {
                    }
                    break;
                case 'purchase':
                    [$beneficiaryId, $action, $value, $currency, $assistanceId] = $actionData;
                    $purchase = new SmartcardPurchase();
                    $purchase->setVendorId($this->vendor->getId());
                    $purchase->setCreatedAt($date);
                    $purchase->setProducts([
                        [
                            'id' => $product->getId(),
                            'quantity' => 2.5,
                            'value' => $value,
                            'currency' => $currency,
                        ],
                    ]);
                    $purchase->setBeneficiaryId($beneficiaryId);
                    $purchase = $this->smartcardService->purchase($this->smartcardNumber, $purchase);
                    $purchase->setAssistance($assistanceRepository->find($assistanceId));
                    $this->em->persist($purchase);
                    $this->em->flush();
                    break;
                case 'deposit':
                    [$beneficiaryId, $action, $value, $currency, $assistanceId] = $actionData;

                    /** @var Assistance $assistance */
                    $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                    /** @var Beneficiary $beneficiary */
                    $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryId);

                    $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy([
                        'assistance' => $assistance,
                        'beneficiary' => $beneficiary,
                    ], ['id' => 'asc']);

                    if (is_null($assistanceBeneficiary)) {
                        var_dump($assistance->getId());
                        var_dump($beneficiary->getId());
                    }

                    $reliefPackage = new ReliefPackage(
                        $assistanceBeneficiary,
                        ModalityType::SMART_CARD,
                        $assistanceBeneficiary->getAssistance()->getCommodities()[0]->getValue(),
                        $assistanceBeneficiary->getAssistance()->getCommodities()[0]->getUnit(),
                    );

                    $this->em->persist($reliefPackage);
                    $this->em->flush();

                    $this->depositFactory->create(
                        $this->smartcardNumber,
                        DepositInputType::create(
                            $reliefPackage->getId(),
                            $value,
                            $value, // balance is rewritten by new value
                            $date
                        ),
                        $admin
                    );
                    break;
            }
            $date = clone $date;
            $date->modify('+1 day');
        }
        /** @var PreliminaryInvoice[] $preliminaryInvoices */
        $preliminaryInvoices = $this->preliminaryInvoiceRepository->findBy(['vendor' => $this->vendor]);
        $this->assertIsArray($preliminaryInvoices, "Redemption candidates must be array");
        $this->assertCount(count($expectedResults), $preliminaryInvoices, "Wrong count of redemption candidates");
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $this->assertContainsEquals(
                [
                    $preliminaryInvoice->getValue(),
                    $preliminaryInvoice->getCurrency(),
                    $preliminaryInvoice->getProject()->getId(),
                ],
                $expectedResults,
                "Result was unexpected"
            );

            foreach ($preliminaryInvoice->getPurchaseIds() as $purchaseId) {
                /** @var SmartcardPurchase $purchase */
                $purchase = $this->em->getRepository(\Entity\SmartcardPurchase::class)->find($purchaseId);
                $this->assertNotNull($purchase, "Purchase must exists");
                $this->assertEquals(2000, $purchase->getCreatedAt()->format('Y'), "Wrong purchase year");
            }
        }
        // redeem test
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $batchRequest = new SmartcardInvoiceCreateInputType();
            $batchRequest->setPurchaseIds($preliminaryInvoice->getPurchaseIds());

            $batch = $this->invoiceFactory->create($this->vendor, $batchRequest, $admin);

            foreach ($batch->getPurchases() as $purchase) {
                $this->assertEquals(2000, $purchase->getCreatedAt()->format('Y'), "Wrong purchase year");
            }
            $this->assertEquals(
                $preliminaryInvoice->getValue(),
                $batch->getValue(),
                "Redemption value of batch is different"
            );
            $this->assertEquals(
                $preliminaryInvoice->getCurrency(),
                $batch->getCurrency(),
                "Redemption currency of batch is different"
            );
            $this->assertEquals(
                $preliminaryInvoice->getProject()->getId(),
                $batch->getProject()->getId(),
                "Redemption project of batch is different"
            );
            $this->assertEquals(
                $preliminaryInvoice->getPurchaseCount(),
                $batch->getPurchases()->count(),
                "Redemption purchase count of batch is different"
            );
        }
    }

    public function validSmartcardReuseFlows(): array
    {
        $projectA = 3;
        $projectB = 10;
        $assistanceA1 = 51; // USD
        $beneficiary1 = 70;
        $beneficiary2 = 71;
        $beneficiary3 = 72;

        $vendorA = 3;
        $vendorB = 5;

        $times = [];
        $times[1] = '2000-01-01';
        $times[2] = '2000-02-01';
        $times[3] = '2000-03-01';
        $times[4] = '2000-04-01';
        $times[5] = '2000-05-01';
        $times[6] = '2000-06-01';
        $times[7] = '2000-07-01';
        $times[8] = '2000-08-01';
        $times[9] = '2000-09-01';

        /**
         * deposit = 100 USD
         * purchase = 10 USD in 2 products
         */
        return [
            'standard full flow' => [
                [
                    [$times[1], $beneficiary1, ['register', 'deposit', 'purchase'], $vendorA],
                    [$times[2], $beneficiary2, ['register', 'deposit', 'purchase'], $vendorA],
                    [$times[3], $beneficiary3, ['register', 'deposit', 'purchase'], $vendorA],
                ],
                [
                    $beneficiary1 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary2 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary3 => ['distributed' => 100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => null,
                ],
            ],
            'standard lazy flow' => [
                [
                    [$times[1], $beneficiary1, ['deposit', 'purchase'], $vendorA],
                    [$times[2], $beneficiary2, ['deposit', 'purchase'], $vendorA],
                    [$times[3], $beneficiary3, ['deposit', 'purchase'], $vendorA],
                ],
                [
                    $beneficiary1 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary2 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary3 => ['distributed' => 100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => null,
                ],
            ],
            'standard lazy vendor flow' => [
                [
                    [$times[1], $beneficiary1, ['register', 'deposit']],
                    [$times[3], $beneficiary2, ['register', 'deposit']],
                    [$times[5], $beneficiary3, ['register', 'deposit']],
                    [$times[2], $beneficiary1, ['purchase'], $vendorA],
                    [$times[4], $beneficiary2, ['purchase'], $vendorA],
                    [$times[6], $beneficiary3, ['purchase'], $vendorA],
                ],
                [
                    $beneficiary1 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary2 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary3 => ['distributed' => 100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => null,
                ],
            ],
            'standard lazy distributor flow' => [
                [
                    [$times[2], $beneficiary1, ['purchase'], $vendorA],
                    [$times[4], $beneficiary2, ['purchase'], $vendorA],
                    [$times[6], $beneficiary3, ['purchase'], $vendorA],
                    [$times[1], $beneficiary1, ['register', 'deposit']],
                    [$times[3], $beneficiary2, ['register', 'deposit']],
                    [$times[5], $beneficiary3, ['register', 'deposit']],

                ],
                [
                    $beneficiary1 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary2 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary3 => ['distributed' => 100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => null,
                ],
            ],
            'two lazy distributors in reverse order' => [
                [
                    [$times[3], $beneficiary1, ['purchase'], $vendorA],
                    [$times[6], $beneficiary2, ['purchase'], $vendorA],
                    [$times[9], $beneficiary3, ['purchase'], $vendorA],
                    [$times[2], $beneficiary1, ['deposit']],
                    [$times[5], $beneficiary2, ['deposit']],
                    [$times[8], $beneficiary3, ['deposit']],
                    [$times[1], $beneficiary1, ['register']],
                    [$times[4], $beneficiary2, ['register']],
                    [$times[7], $beneficiary3, ['register']],
                ],
                [
                    $beneficiary1 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary2 => ['distributed' => 100, 'purchased' => 10],
                    $beneficiary3 => ['distributed' => 100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => null,
                ],
            ],
            'incomplete purchases' => [
                [
                    [$times[3], $beneficiary1, ['purchase'], $vendorA],
                    [$times[6], $beneficiary2, ['purchase'], $vendorA],
                    [$times[9], $beneficiary3, ['purchase'], $vendorA],
                ],
                [
                    $beneficiary1 => ['distributed' => 0, 'purchased' => 10],
                    $beneficiary2 => ['distributed' => 0, 'purchased' => 10],
                    $beneficiary3 => ['distributed' => 0, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => null,
                ],
            ],
            'incomplete purchases in wrong order' => [
                [
                    [$times[1], $beneficiary1, ['purchase'], $vendorA],
                    [$times[2], $beneficiary2, ['purchase'], $vendorA],
                    [$times[5], $beneficiary3, ['purchase'], $vendorA],
                    [$times[3], $beneficiary1, ['purchase'], $vendorB],
                    [$times[6], $beneficiary2, ['purchase'], $vendorB],
                    [$times[9], $beneficiary3, ['purchase'], $vendorB],
                ],
                [
                    $beneficiary1 => ['distributed' => 0, 'purchased' => 20],
                    $beneficiary2 => ['distributed' => 0, 'purchased' => 20],
                    $beneficiary3 => ['distributed' => 0, 'purchased' => 20],
                ],
                [
                    $vendorA => ['purchases' => 3, 'records' => 6, 'value' => 30],
                    $vendorB => ['purchases' => 3, 'records' => 6, 'value' => 30],
                ],
            ],
        ];
    }

    /**
     * @dataProvider validSmartcardReuseFlows
     *
     *
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     */
    public function testSmartcardReuseFlows(
        array $actions,
        array $expectedBeneficiaryResults,
        array $expectedVendorResults
    ): void {
        $admin = $this->user;
        $projectA = 3;
        $assistanceId = 51; // USD
        $serialNumber = '111222333';
        $allTestingBeneficiaries = [70, 71, 72];
        $allTestingVendors = [2, 3];

        $targets = $this->em->getRepository(AssistanceBeneficiary::class)->findBy([
            'beneficiary' => $allTestingBeneficiaries,
            'assistance' => $assistanceId,
        ]);
        $this->assertCount(3, $targets, "All testing Beneficiaries must be in testing Assistance#$assistanceId");
        $packages = $this->em->getRepository(ReliefPackage::class)->findBy([
            'assistanceBeneficiary' => $targets,
        ]);
        $deposits = $this->em->getRepository(SmartcardDeposit::class)->findBy(
            ['reliefPackage' => $packages],
            ['id' => 'asc']
        );
        foreach ($packages as $package) {
            $this->em->remove($package);
        }
        foreach ($deposits as $deposit) {
            $this->em->remove($deposit);
        }
        $smartcards = $this->em->getRepository(Smartcard::class)->findBy(
            ['beneficiary' => $allTestingBeneficiaries],
            ['id' => 'asc']
        );
        $purchases = $this->em->getRepository(\Entity\SmartcardPurchase::class)->findBy(
            ['smartcard' => $smartcards],
            ['id' => 'asc']
        );
        foreach ($purchases as $purchase) {
            $this->em->remove($purchase);
        }
        $purchases = $this->em->getRepository(\Entity\SmartcardPurchase::class)->findBy(
            ['vendor' => $allTestingVendors],
            ['id' => 'asc']
        );
        foreach ($purchases as $purchase) {
            $this->em->remove($purchase);
        }
        $this->em->flush();

        foreach ($actions as $preparedAction) {
            [$dateOfEvent, $beneficiaryId, $subActions] = $preparedAction;
            foreach ($subActions as $action) {
                switch ($action) {
                    case 'register':
                        $createdAt = DateTime::createFromFormat('Y-m-d', $dateOfEvent);
                        $registerInputType = SmartcardRegisterInputType::create(
                            $serialNumber,
                            $beneficiaryId,
                            $createdAt
                        );
                        try {
                            $this->smartcardService->register($registerInputType);
                        } catch (SmartcardDoubledRegistrationException) {
                        }
                        break;
                    case 'deposit':
                        /** @var Assistance $assistance */
                        $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                        /** @var Beneficiary $beneficiary */
                        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryId);

                        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy([
                            'assistance' => $assistance,
                            'beneficiary' => $beneficiary,
                        ], ['id' => 'asc']);

                        $reliefPackage = new ReliefPackage(
                            $assistanceBeneficiary,
                            ModalityType::SMART_CARD,
                            $assistanceBeneficiary->getAssistance()->getCommodities()[0]->getValue(),
                            $assistanceBeneficiary->getAssistance()->getCommodities()[0]->getUnit(),
                        );

                        $this->em->persist($reliefPackage);
                        $this->em->flush();

                        $this->depositFactory->create(
                            $serialNumber,
                            DepositInputType::create(
                                $reliefPackage->getId(),
                                100,
                                null,
                                DateTime::createFromFormat('Y-m-d', $dateOfEvent)
                            ),
                            $admin
                        );
                        break;
                    case 'purchase':
                        $vendorId = $preparedAction[3];
                        $purchaseData = new SmartcardPurchase();
                        $purchaseData->setBeneficiaryId($beneficiaryId);
                        $purchaseData->setCreatedAt(DateTime::createFromFormat('Y-m-d', $dateOfEvent));
                        $purchaseData->setVendorId($vendorId);
                        $purchaseData->setProducts([
                            [
                                'id' => 1,
                                'quantity' => 1,
                                'value' => 2,
                                'currency' => 'USD',
                            ],
                            [
                                'id' => 2,
                                'quantity' => 1,
                                'value' => 8,
                                'currency' => 'USD',
                            ],
                        ]);
                        $purchase = $this->smartcardService->purchase($serialNumber, $purchaseData);
                        $purchase->setAssistance($this->em->getRepository(Assistance::class)->find($assistanceId));
                        $this->em->persist($purchase);
                        $this->em->flush();
                        break;
                    default:
                        $this->fail('Wrong test data. Unknown action ' . $action);
                }
            }
        }

        foreach ($expectedBeneficiaryResults as $beneficiaryId => $values) {
            $target = $this->em->getRepository(AssistanceBeneficiary::class)->findBy([
                'beneficiary' => $beneficiaryId,
                'assistance' => $assistanceId,
            ]);
            $package = $this->em->getRepository(ReliefPackage::class)->findBy(
                ['assistanceBeneficiary' => $target],
                ['id' => 'asc']
            );
            $deposits = $this->em->getRepository(SmartcardDeposit::class)->findBy(
                ['reliefPackage' => $package],
                ['id' => 'asc']
            );
            $distributed = 0;
            foreach ($deposits as $deposit) {
                $distributed += $deposit->getValue();
            }
            $this->assertEquals($values['distributed'], $distributed, "Wrong distributed amount");

            $smartcards = $this->em->getRepository(Smartcard::class)->findBy(
                ['beneficiary' => $beneficiaryId],
                ['id' => 'asc']
            );
            $purchases = $this->em->getRepository(\Entity\SmartcardPurchase::class)->findBy(
                ['smartcard' => $smartcards],
                ['id' => 'asc']
            );
            $purchased = 0;
            foreach ($purchases as $purchase) {
                $purchased += $purchase->getRecordsValue();
            }
            $this->assertEquals($values['purchased'], $purchased, "Wrong purchased amount");
        }

        foreach ($expectedVendorResults as $vendorId => $values) {
            $vendor = $this->em->getRepository(Vendor::class)->find($vendorId);
            $preliminaryInvoice = $this->preliminaryInvoiceRepository->findBy(['vendor' => $vendor]);
            if (is_array($values)) {
                $this->assertCount(1, $preliminaryInvoice, "Wrong number of invoice candidates");
                /** @var PreliminaryInvoice $invoice */
                $invoice = $preliminaryInvoice[0];
                $this->assertEquals(
                    $values['purchases'],
                    $invoice->getPurchaseCount(),
                    "Wrong redeemable purchases count"
                );
                $this->assertEquals($values['value'], $invoice->getValue(), "Wrong redeemable value");
                $this->assertEquals($projectA, $invoice->getProject()->getId(), "Wrong redeemable project");
            } elseif (null === $values) {
                $this->assertEmpty($preliminaryInvoice, "Wrong number of invoice candidates");
            } else {
                $this->fail("Wrong test data.");
            }
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws InvalidArgumentException
     * @throws DoubledDepositException
     */
    public function testGetOrCreateActiveSmartcardForBeneficiary(): void
    {
        $this->em->beginTransaction();

        $beneficiary = $this->beneficiaryRepository->findOneBy([]);
        $smartcard1 = $this->getSmartcardForBeneficiary('AAA123AAA', $beneficiary);
        $smartcard2 = $this->getSmartcardForBeneficiary('BBB123BBB', $beneficiary);
        $this->em->refresh($smartcard1);

        $this->assertEquals(SmartcardStates::INACTIVE, $smartcard1->getState());
        $this->assertEquals(SmartcardStates::ACTIVE, $smartcard2->getState());

        $this->em->rollback();
    }

    public function testDepositToOldSmartcard(): void
    {
        $this->em->beginTransaction();

        /**
         * @var ?ReliefPackage $reliefPackage
         */
        $reliefPackage = $this->reliefPackageRepository->findOneBy(
            ['state' => ReliefPackageState::TO_DISTRIBUTE, 'modalityType' => ModalityType::SMART_CARD]
        );
        if (!$reliefPackage) {
            $this->markTestSkipped('No relief package to distribute.');
        }

        $ab = $reliefPackage->getAssistanceBeneficiary();
        $oldSmartcard = $ab->getBeneficiary()->getActiveSmartcard();
        if (!$oldSmartcard) {
            $oldSmartcard = $this->getSmartcardForBeneficiary('AAA123AAA', $ab->getBeneficiary());
        }
        $newSmartcard = $this->getSmartcardForBeneficiary('BBB123BBB', $ab->getBeneficiary());
        $this->em->refresh($oldSmartcard);
        $this->em->refresh($newSmartcard);

        $this->assertEquals(SmartcardStates::INACTIVE, $oldSmartcard->getState());
        $this->assertEquals(SmartcardStates::ACTIVE, $newSmartcard->getState());

        $depositInputType = self::buildDepositInputType(
            $reliefPackage->getId(),
            $reliefPackage->getAmountToDistribute()
        );
        $this->createDeposit($oldSmartcard->getSerialNumber(), $depositInputType, $this->user, $this->depositFactory);

        $this->em->flush();
        $this->em->refresh($oldSmartcard);
        $this->em->refresh($newSmartcard);

        $this->assertEquals(SmartcardStates::ACTIVE, $oldSmartcard->getState());
        $this->assertEquals(SmartcardStates::INACTIVE, $newSmartcard->getState());

        $this->em->rollback();
    }

    private function createTempVendor(): void
    {
        $id = substr(md5(uniqid()), 0, 5) . "_";
        $adm2 = $this->em->getRepository(Location::class)->findOneBy(
            ['countryIso3' => 'SYR', 'lvl' => 2],
            ['id' => 'asc']
        );

        $roles = $this->roleRepository->findByCodes(['ROLE_ADMIN']);

        $this->user = new User();
        $this->user->setEnabled(1)
            ->setEmail($id . self::VENDOR_USERNAME)
            ->setUsername($id . self::VENDOR_USERNAME)
            ->setSalt('')
            ->setRoles($roles)
            ->setChangePassword(0)
            ->setPassword('');

        $this->vendor = new Vendor();
        $this->vendor
            ->setShop('single-purpose')
            ->setAddressNumber('13')
            ->setAddressStreet('Main street')
            ->setAddressPostcode('12345')
            ->setArchived(false)
            ->setUser($this->user)
            ->setLocation($adm2);
        $this->vendor->setName("Test Vendor for " . self::class);
    }
}
