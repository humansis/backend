<?php

namespace VoucherBundle\Tests\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UserBundle\Entity\User;
use VoucherBundle\DTO\PurchaseRedemptionBatch;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\InputType\SmartcardPurchase;
use VoucherBundle\InputType\SmartcardRedemtionBatch;
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

    public function setUp()
    {
        self::bootKernel();

        //Preparing the EntityManager
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->smartcardService = static::$kernel->getContainer()->get('smartcard_service');

        $this->createTempVendor($this->em);
        $this->em->persist($this->vendor);

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
            'purchase alone' => [
                [
                    [$beneficiaryA1, 'purchase', 300.1, 'USD'],
                ],
                [] // purchase without project cant be redeemed
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
                    [$beneficiaryA1, 'purchase', 500.8, 'USD'],
                ],
                [
                    [500.8, 'USD', $projectA],
                ]
            ],
            'multiproject' => [
                [
                    [$beneficiaryA1, 'register', $assistanceA1],
                    [$beneficiaryA1, 'deposit', 100, 'USD', $assistanceA1],
                    [$beneficiaryA1, 'purchase', 200, 'USD'],
                    [$beneficiaryB1, 'register', $assistanceB1],
                    [$beneficiaryB1, 'deposit', 20, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'purchase', 40, 'USD'],
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
                    [$beneficiaryA1, 'purchase', 200, 'USD'],
                    [$beneficiaryA1, 'deposit', 20, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 40, 'SYP'],
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
                    [$beneficiaryA1, 'purchase', 200, 'SYP'],
                    [$beneficiaryA1, 'register', $assistanceB1],
                    [$beneficiaryA1, 'deposit', 20, 'USD', $assistanceB1],
                    [$beneficiaryA1, 'purchase', 40, 'USD'],
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
                    [$beneficiaryA1, 'purchase', 10, 'USD'],
                    [$beneficiaryA1, 'purchase', 20, 'USD'],
                    [$beneficiaryA1, 'purchase', 30, 'USD'],
                    [$beneficiaryA1, 'purchase', 40, 'USD'],
                    [$beneficiaryA2, 'register', $assistanceA2],
                    [$beneficiaryA2, 'deposit', 20, 'SYP', $assistanceA2],
                    [$beneficiaryA2, 'purchase', 40, 'SYP'],
                    [$beneficiaryB1, 'register', $assistanceB1],
                    [$beneficiaryB1, 'deposit', 500, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'purchase', 40, 'USD'],
                    [$beneficiaryB1, 'deposit', 500, 'USD', $assistanceB1],
                    [$beneficiaryB1, 'purchase', 40, 'USD'],
                    [$beneficiaryA1, 'register', $assistanceA2],
                    [$beneficiaryA1, 'deposit', 1000, 'SYP', $assistanceA2],
                    [$beneficiaryA1, 'purchase', 100, 'SYP'],
                    [$beneficiaryA1, 'purchase', 100, 'SYP'],
                    [$beneficiaryA1, 'purchase', 100, 'SYP'],
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
     * @param $actions
     * @param $expectedResults
     */
    public function testSmartcardCashflows(array $actions, array $expectedResults): void
    {
        $admin = $this->em->getRepository(User::class)->find(1);
        $product = $this->em->getRepository(Product::class)->findOneBy(['countryISO3'=>'SYR'], ['id' => 'asc']);

        $date = \DateTime::createFromFormat('Y-m-d', '2000-01-01');
        foreach ($actions as $actionData) {
            switch ($actionData[1]) {
                case 'register':
                    [$beneficiaryId, $action, $assistanceId] = $actionData;
                    /** @var Assistance $assistance */
                    $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                    $beneficiary = $assistance->getDistributionBeneficiaries()->get(0)->getBeneficiary();
                    $this->smartcardService->register($this->smartcardNumber, $beneficiaryId, $date);
                    break;
                case 'purchase':
                    [$beneficiaryId, $action, $value, $currency] = $actionData;
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
                    $this->smartcardService->purchase($this->smartcardNumber, $purchase);
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

                    $this->smartcardService->deposit(
                        $this->smartcardNumber,
                        $reliefPackage->getId(),
                        $value,
                        $value, // balance is rewritten by new value
                        $date,
                        $admin
                    );
                    break;
            }
            $date = clone $date;
            $date->modify('+1 day');
        }
        /** @var PurchaseRedemptionBatch[] $batchCandidates */
        $batchCandidates = $this->smartcardService->getRedemptionCandidates($this->vendor);
        $this->assertIsArray($batchCandidates, "Redemption candidates must be array");
        $this->assertCount(count($expectedResults), $batchCandidates, "Wrong count of redemption candidates");
        foreach ($batchCandidates as $candidate) {
            $this->assertContains([$candidate->getValue(), $candidate->getCurrency(), $candidate->getProjectId()], $expectedResults, "Result was unexpected");

            foreach ($candidate->getPurchasesIds() as $purchaseId) {
                /** @var SmartcardPurchase $purchase */
                $purchase = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->find($purchaseId);
                $this->assertNotNull($purchase, "Purchase must exists");
                $this->assertEquals(2000, $purchase->getCreatedAt()->format('Y'), "Wrong purchase year");
            }
        }
        // redeem test
        foreach ($batchCandidates as $candidateToSave) {
            $batchRequest = new SmartcardRedemtionBatch();
            $batchRequest->setPurchases($candidateToSave->getPurchasesIds());

            $batch = $this->smartcardService->redeem($this->vendor, $batchRequest, $admin);

            foreach ($batch->getPurchases() as $purchase) {
                $this->assertEquals(2000, $purchase->getCreatedAt()->format('Y'), "Wrong purchase year");
            }
            $this->assertEquals($candidateToSave->getValue(), $batch->getValue(), "Redemption value of batch is different");
            $this->assertEquals($candidateToSave->getCurrency(), $batch->getCurrency(), "Redemption currency of batch is different");
            $this->assertEquals($candidateToSave->getProjectId(), $batch->getProject()->getId(), "Redemption project of batch is different");
            $this->assertEquals($candidateToSave->getPurchasesCount(), $batch->getPurchases()->count(), "Redemption purchase count of batch is different");
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
                    // $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30], I'm not sure if it is correct
                    $vendorA => null,
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
                    // $vendorA => ['purchases'=>3, 'records'=>6, 'value'=>30], I'm not sure if it is correct
                    // $vendorB => ['purchases'=>3, 'records'=>6, 'value'=>30], I'm not sure if it is correct
                    $vendorA => null,
                    $vendorB => null,
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
     */
    public function testSmartcardReuseFlows(array $actions, array $expectedBeneficiaryResults, array $expectedVendorResults): void
    {
        $admin = $this->em->getRepository(User::class)->find(1);
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
                        $this->smartcardService->register($serialNumber, $beneficiaryId, \DateTime::createFromFormat('Y-m-d', $dateOfEvent));
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

                        $this->smartcardService->deposit($serialNumber, $reliefPackage->getId(), 100, null, \DateTime::createFromFormat('Y-m-d', $dateOfEvent), $admin);
                        break;
                    case 'purchase':
                        $vendorId = $preparedAction[3];
                        $purchaseData = new SmartcardPurchase();
                        $purchaseData->setBeneficiaryId($beneficiaryId);
                        $purchaseData->setCreatedAt(\DateTime::createFromFormat('Y-m-d', $dateOfEvent));
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
                        $this->smartcardService->purchase($serialNumber, $purchaseData);
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
            $redemptionCandidates = $this->smartcardService->getRedemptionCandidates($vendor);
            if (is_array($values)) {
                $this->assertCount(1, $redemptionCandidates, "Wrong number of invoice candidates");
                /** @var PurchaseRedemptionBatch $invoice */
                $invoice = $redemptionCandidates[0];
                $this->assertEquals($values['purchases'], $invoice->getPurchasesCount(), "Wrong redeemable purchases count");
                $this->assertEquals($values['value'], $invoice->getValue(), "Wrong redeemable value");
                $this->assertEquals($projectA, $invoice->getProjectId(), "Wrong redeemable project");
            } elseif (null === $values) {
                $this->assertEmpty($redemptionCandidates, "Wrong number of invoice candidates");
            } else {
                $this->fail("Wrong test data.");
            }
        }
    }

    private function createTempVendor(\Doctrine\ORM\EntityManagerInterface $em): void
    {
        $id = substr(md5(uniqid()), 0, 5)."_";
        $adm1 = $this->em->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'SYR'], ['id' => 'asc']);
        $adm2 = $this->em->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1], ['id' => 'asc']);

        $user = new User();
        $user->injectObjectManager($em);
        $user->setEnabled(1)
            ->setEmail($id.self::VENDOR_USERNAME)
            ->setEmailCanonical($id.self::VENDOR_USERNAME)
            ->setUsername($id.self::VENDOR_USERNAME)
            ->setUsernameCanonical($id.self::VENDOR_USERNAME)
            ->setSalt('')
            ->setRoles(['ROLE_ADMIN'])
            ->setChangePassword(0);
        $user->setPassword('');

        $this->vendor = new Vendor();
        $this->vendor
            ->setShop('single-purpose')
            ->setAddressNumber('13')
            ->setAddressStreet('Main street')
            ->setAddressPostcode('12345')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2->getLocation());
        $this->vendor->setName("Test Vendor for ".__CLASS__);
    }
}
