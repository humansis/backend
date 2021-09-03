<?php

namespace VoucherBundle\Tests\Utils;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use DistributionBundle\Entity\Assistance;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UserBundle\Entity\User;
use VoucherBundle\DTO\PurchaseRedemptionBatch;
use VoucherBundle\Entity\Product;
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

        return [
            'vendor has nothing' => [
                [],
                []
            ],
            'purchase alone' => [
                [
                    ['purchase', 300.1, 'USD'],
                ],
                [] // purchase without project cant be redeemed
            ],
            'deposit alone' => [
                [
                    ['register', $assistanceA1],
                    ['deposit', 20.112, 'USD', $assistanceA1],
                ],
                [], // there is nothing to redeem
            ],
            'trivial' => [
                [
                    ['register', $assistanceA1],
                    ['deposit', 300.1, 'USD', $assistanceA1],
                    ['purchase', 500.8, 'USD'],
                ],
                [
                    [500.8, 'USD', $projectA],
                ]
            ],
            'multiproject' => [
                [
                    ['register', $assistanceA1],
                    ['deposit', 100, 'USD', $assistanceA1],
                    ['purchase', 200, 'USD'],
                    ['register', $assistanceB1],
                    ['deposit', 20, 'USD', $assistanceB1],
                    ['purchase', 40, 'USD'],
                ],
                [
                    [200, 'USD', $projectA],
                    [40, 'USD', $projectB],
                ]
            ],
            'multicurrency' => [
                [
                    ['register', $assistanceA1],
                    ['deposit', 100, 'USD', $assistanceA1],
                    ['purchase', 200, 'USD'],
                    ['deposit', 20, 'SYP', $assistanceA2],
                    ['purchase', 40, 'SYP'],
                ],
                [
                    [200, 'USD', $projectA],
                    [40, 'SYP', $projectA],
                ]
            ],
            'multiproject and multicurrency' => [
                [
                    ['register', $assistanceA2],
                    ['deposit', 100, 'SYP', $assistanceA2],
                    ['purchase', 200, 'SYP'],
                    ['register', $assistanceB1],
                    ['deposit', 20, 'USD', $assistanceB1],
                    ['purchase', 40, 'USD'],
                ],
                [
                    [200, 'SYP', $projectA],
                    [40, 'USD', $projectB],
                ]
            ],
            'chaos' => [
                [
                    ['register', $assistanceA1],
                    ['deposit', 100, 'USD', $assistanceA1],
                    ['purchase', 10, 'USD'],
                    ['purchase', 20, 'USD'],
                    ['purchase', 30, 'USD'],
                    ['purchase', 40, 'USD'],
                    ['register', $assistanceA2],
                    ['deposit', 20, 'SYP', $assistanceA2],
                    ['purchase', 40, 'SYP'],
                    ['register', $assistanceB1],
                    ['deposit', 500, 'USD', $assistanceB1],
                    ['purchase', 40, 'USD'],
                    ['deposit', 500, 'USD', $assistanceB1],
                    ['purchase', 40, 'USD'],
                    ['register', $assistanceA2],
                    ['deposit', 1000, 'SYP', $assistanceA2],
                    ['purchase', 100, 'SYP'],
                    ['purchase', 100, 'SYP'],
                    ['purchase', 100, 'SYP'],
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
        $product = $this->em->getRepository(Product::class)->findOneBy(['countryISO3'=>'SYR']);

        $date = \DateTime::createFromFormat('Y-m-d', '2000-01-01');
        foreach ($actions as $action) {
            switch ($action[0]) {
                case 'register':
                    $assistanceId = $action[1];
                    /** @var Assistance $assistance */
                    $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
                    $beneficiary = $assistance->getDistributionBeneficiaries()->get(0)->getBeneficiary();
                    $this->smartcardService->register($this->smartcardNumber, $beneficiary->getId(), $date);
                    break;
                case 'purchase':
                    $purchase = new SmartcardPurchase();
                    $purchase->setVendorId($this->vendor->getId());
                    $purchase->setCreatedAt($date);
                    $purchase->setProducts([[
                        'id' => $product->getId(),
                        'quantity' => 2.5,
                        'value' => $action[1],
                        'currency' => $action[2],
                    ]]);
                    $this->smartcardService->purchaseWithoutReusingSC($this->smartcardNumber, $purchase);
                    break;
                case 'deposit':
                    $this->smartcardService->deposit(
                        $this->smartcardNumber,
                        $action[3], // assistanceId
                        $action[1],
                        $action[1], // balance is rewritten by new value
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
        }
        // redeem test
        foreach ($batchCandidates as $candidateToSave) {
            $batchRequest = new SmartcardRedemtionBatch();
            $batchRequest->setPurchases($candidateToSave->getPurchasesIds());

            $batch = $this->smartcardService->redeem($this->vendor, $batchRequest, $admin);
            $this->assertEquals($candidateToSave->getValue(), $batch->getValue(), "Redemption value of batch is different");
            $this->assertEquals($candidateToSave->getCurrency(), $batch->getCurrency(), "Redemption currency of batch is different");
            $this->assertEquals($candidateToSave->getProjectId(), $batch->getProject()->getId(), "Redemption project is of batch is different");
            $this->assertEquals($candidateToSave->getPurchasesCount(), $batch->getPurchases()->count(), "Redemption purchase count of batch is different");
        }
    }

    private function createTempVendor(\Doctrine\ORM\EntityManagerInterface $em): void
    {
        $id = substr(md5(uniqid()), 0, 5)."_";
        $adm1 = $this->em->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'SYR']);
        $adm2 = $this->em->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1]);

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
