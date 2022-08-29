<?php

namespace VoucherBundle\Tests\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Location;
use DateTime;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Smartcard\Deposit\DepositFactory;
use NewApiBundle\Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Smartcard\PreliminaryInvoice;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use NewApiBundle\InputType\Smartcard\SmartcardRegisterInputType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\InputType\SmartcardPurchase;
use VoucherBundle\InputType\SmartcardInvoice;
use VoucherBundle\Utils\SmartcardService;

class SmartcardServiceTest extends KernelTestCase
{
    const VENDOR_USERNAME = 'one-purpose-vendor@example.org';

    /** @var ObjectManager|null */
    private $em;

    /** @var SmartcardService */
    private $smartcardService;

    /** @var Vendor */
    private $vendor;

    /** @var string */
    private $smartcardNumber = '';

    /** @var DepositFactory */
    private $depositFactory;

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        self::bootKernel();

        //Preparing the EntityManager
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->smartcardService = static::$kernel->getContainer()->get('smartcard_service');
        $this->depositFactory = static::$kernel->getContainer()->get(DepositFactory::class);

        $this->createTempVendor($this->em);
        $this->em->persist($this->vendor);
        $this->em->persist($this->user);

        $this->smartcardNumber = substr(md5((uniqid())), 0, 7);
        $this->em->flush();
    }

    public function validSmartcardCashflows(): array
    {
        $projectA = 3;
        $projectB = 10;
        $assistanceA1 = 51; // USD
        $assistanceA2 = 241; // SYP
        $assistanceB1 = 242; // USD
        $beneficiaryA1 = 2;
        $beneficiaryA2 = 4;
        $beneficiaryB1 = 250;
        $beneficiaryB2 = 252;

        return [
            'vendor has nothing' => [
                [],
                []
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
            ],
        ];
    }

    /**
     * @dataProvider validSmartcardCashflows
     *
     * @param array $actions
     * @param array $expectedResults
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSmartcardCashflows(array $actions, array $expectedResults): void
    {
        $admin = $this->user;
        $assistanceRepository = $this->em->getRepository(Assistance::class);
        $product = $this->em->getRepository(Product::class)->findOneBy(['countryISO3'=>'SYR'], ['id' => 'asc']);

        $date = DateTime::createFromFormat('Y-m-d', '2000-01-01');
        foreach ($actions as $actionData) {
            switch ($actionData[1]) {
                case 'register':
                    [$beneficiaryId, $action, $assistanceId] = $actionData;
                    /** @var Assistance $assistance */
                    $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                    $beneficiary = $assistance->getDistributionBeneficiaries()->get(0)->getBeneficiary();
                    $registerInputType = SmartcardRegisterInputType::create($this->smartcardNumber, $beneficiaryId, $date->format(\DateTimeInterface::ATOM));
                    try {
                        $this->smartcardService->register($registerInputType);
                    } catch (SmartcardDoubledRegistrationException $e) {
                    }
                    break;
                case 'purchase':
                    [$beneficiaryId, $action, $value, $currency, $assistanceId] = $actionData;
                    $purchase = new SmartcardPurchase();
                    $purchase->setVendorId($this->vendor->getId());
                    $purchase->setCreatedAt($date);
                    $purchase->setProducts([[
                        'id' => $product->getId(),
                        'quantity' => 2.5,
                        'value' => $value,
                        'currency' => $currency,
                    ]]);
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
                        'beneficiary'=> $beneficiary
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
        $preliminaryInvoices = $this->smartcardService->getRedemptionCandidates($this->vendor);
        $this->assertIsArray($preliminaryInvoices, "Redemption candidates must be array");
        $this->assertCount(count($expectedResults), $preliminaryInvoices, "Wrong count of redemption candidates");
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $this->assertContains([$preliminaryInvoice->getValue(), $preliminaryInvoice->getCurrency(), $preliminaryInvoice->getProject()->getId()], $expectedResults, "Result was unexpected");

            foreach ($preliminaryInvoice->getPurchaseIds() as $purchaseId) {
                /** @var SmartcardPurchase $purchase */
                $purchase = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->find($purchaseId);
                $this->assertNotNull($purchase, "Purchase must exists");
                $this->assertEquals(2000, $purchase->getCreatedAt()->format('Y'), "Wrong purchase year");
            }
        }
        // redeem test
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $batchRequest = new SmartcardInvoice();
            $batchRequest->setPurchases($preliminaryInvoice->getPurchaseIds());

            $batch = $this->smartcardService->redeem($this->vendor, $batchRequest, $admin);

            foreach ($batch->getPurchases() as $purchase) {
                $this->assertEquals(2000, $purchase->getCreatedAt()->format('Y'), "Wrong purchase year");
            }
            $this->assertEquals($preliminaryInvoice->getValue(), $batch->getValue(), "Redemption value of batch is different");
            $this->assertEquals($preliminaryInvoice->getCurrency(), $batch->getCurrency(), "Redemption currency of batch is different");
            $this->assertEquals($preliminaryInvoice->getProject()->getId(), $batch->getProject()->getId(), "Redemption project of batch is different");
            $this->assertEquals($preliminaryInvoice->getPurchaseCount(), $batch->getPurchases()->count(), "Redemption purchase count of batch is different");
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
                    $beneficiary1 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary2 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary3 => ['distributed'=>100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => null,
                ]
            ],
            'standard lazy flow' => [
                [
                    [$times[1], $beneficiary1, ['deposit', 'purchase'], $vendorA],
                    [$times[2], $beneficiary2, ['deposit', 'purchase'], $vendorA],
                    [$times[3], $beneficiary3, ['deposit', 'purchase'], $vendorA],
                ],
                [
                    $beneficiary1 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary2 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary3 => ['distributed'=>100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => null,
                ]
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
                    $beneficiary1 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary2 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary3 => ['distributed'=>100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => null,
                ]
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
                    $beneficiary1 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary2 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary3 => ['distributed'=>100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => null,
                ]
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
                    $beneficiary1 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary2 => ['distributed'=>100, 'purchased' => 10],
                    $beneficiary3 => ['distributed'=>100, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => null,
                ]
            ],
            'incomplete purchases' => [
                [
                    [$times[3], $beneficiary1, ['purchase'], $vendorA],
                    [$times[6], $beneficiary2, ['purchase'], $vendorA],
                    [$times[9], $beneficiary3, ['purchase'], $vendorA],
                ],
                [
                    $beneficiary1 => ['distributed'=>0, 'purchased' => 10],
                    $beneficiary2 => ['distributed'=>0, 'purchased' => 10],
                    $beneficiary3 => ['distributed'=>0, 'purchased' => 10],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => null,
                ]
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
                    $beneficiary1 => ['distributed'=>0, 'purchased' => 20],
                    $beneficiary2 => ['distributed'=>0, 'purchased' => 20],
                    $beneficiary3 => ['distributed'=>0, 'purchased' => 20],
                ],
                [
                    $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30],
                    $vendorB => ['purchases'=>3, 'records'=>6, 'value'=>30],
                ]
            ],
        ];
    }

    /**
     * @dataProvider validSmartcardReuseFlows
     *
     * @param array $actions
     * @param array $expectedBeneficiaryResults
     * @param array $expectedVendorResults
     *
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSmartcardReuseFlows(array $actions, array $expectedBeneficiaryResults, array $expectedVendorResults): void
    {
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
        $deposits = $this->em->getRepository(SmartcardDeposit::class)->findBy(['reliefPackage'=>$packages], ['id' => 'asc']);
        foreach ($packages as $package) {
            $this->em->remove($package);
        }
        foreach ($deposits as $deposit) {
            $this->em->remove($deposit);
        }
        $smartcards = $this->em->getRepository(Smartcard::class)->findBy(['beneficiary'=>$allTestingBeneficiaries], ['id' => 'asc']);
        $purchases = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->findBy(['smartcard'=>$smartcards], ['id' => 'asc']);
        foreach ($purchases as $purchase) {
            $this->em->remove($purchase);
        }
        $purchases = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->findBy(['vendor'=>$allTestingVendors], ['id' => 'asc']);
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
                        $registerInputType = SmartcardRegisterInputType::create($serialNumber, $beneficiaryId, $createdAt->format(\DateTimeInterface::ATOM));
                        try {
                            $this->smartcardService->register($registerInputType);
                        } catch (SmartcardDoubledRegistrationException $e) {
                        }
                        break;
                    case 'deposit':
                        /** @var Assistance $assistance */
                        $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                        /** @var Beneficiary $beneficiary */
                        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryId);

                        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy([
                            'assistance' => $assistance,
                            'beneficiary'=> $beneficiary
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
                                \DateTime::createFromFormat('Y-m-d', $dateOfEvent)
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
                            ]
                        ]);
                        $purchase = $this->smartcardService->purchase($serialNumber, $purchaseData);
                        $purchase->setAssistance($this->em->getRepository(Assistance::class)->find($assistanceId));
                        $this->em->persist($purchase);
                        $this->em->flush();
                        break;
                    default:
                        $this->fail('Wrong test data. Unknown action '.$action);
                }
            }
        }

        foreach ($expectedBeneficiaryResults as $beneficiaryId => $values) {
            $target = $this->em->getRepository(AssistanceBeneficiary::class)->findBy([
                'beneficiary' => $beneficiaryId,
                'assistance' => $assistanceId,
                ]);
            $package = $this->em->getRepository(ReliefPackage::class)->findBy(['assistanceBeneficiary'=>$target], ['id' => 'asc']);
            $deposits = $this->em->getRepository(SmartcardDeposit::class)->findBy(['reliefPackage'=>$package], ['id' => 'asc']);
            $distributed = 0;
            foreach ($deposits as $deposit) {
                $distributed += $deposit->getValue();
            }
            $this->assertEquals($values['distributed'], $distributed, "Wrong distributed amount");

            $smartcards = $this->em->getRepository(Smartcard::class)->findBy(['beneficiary'=>$beneficiaryId], ['id' => 'asc']);
            $purchases = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->findBy(['smartcard'=>$smartcards], ['id' => 'asc']);
            $purchased = 0;
            foreach ($purchases as $purchase) {
                $purchased += $purchase->getRecordsValue();
            }
            $this->assertEquals($values['purchased'], $purchased, "Wrong purchased amount");
        }

        foreach ($expectedVendorResults as $vendorId => $values) {
            $vendor = $this->em->getRepository(Vendor::class)->find($vendorId);
            $preliminaryInvoice = $this->smartcardService->getRedemptionCandidates($vendor);
            if (is_array($values)) {
                $this->assertCount(1, $preliminaryInvoice, "Wrong number of invoice candidates");
                /** @var PreliminaryInvoice $invoice */
                $invoice = $preliminaryInvoice[0];
                $this->assertEquals($values['purchases'], $invoice->getPurchaseCount(), "Wrong redeemable purchases count");
                $this->assertEquals($values['value'], $invoice->getValue(), "Wrong redeemable value");
                $this->assertEquals($projectA, $invoice->getProject()->getId(), "Wrong redeemable project");
            } elseif (null === $values) {
                $this->assertEmpty($preliminaryInvoice, "Wrong number of invoice candidates");
            } else {
                $this->fail("Wrong test data.");
            }
        }
    }

    private function createTempVendor(EntityManagerInterface $em): void
    {
        $id = substr(md5(uniqid()), 0, 5)."_";
        $adm2 = $this->em->getRepository(Location::class)->findOneBy(['countryISO3' => 'SYR', 'lvl' => 2], ['id' => 'asc']);

        $this->user = new User();
        $this->user->injectObjectManager($em);
        $this->user->setEnabled(1)
            ->setEmail($id.self::VENDOR_USERNAME)
            ->setEmailCanonical($id.self::VENDOR_USERNAME)
            ->setUsername($id.self::VENDOR_USERNAME)
            ->setUsernameCanonical($id.self::VENDOR_USERNAME)
            ->setSalt('')
            ->setRoles(['ROLE_ADMIN'])
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
        $this->vendor->setName("Test Vendor for ".__CLASS__);
    }
}
