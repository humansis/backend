<?php

namespace DataFixtures;

use DateTimeImmutable;
use Entity\Assistance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\Booklet;
use Entity\Product;
use Entity\Vendor;
use Entity\VoucherPurchase;
use Model\PurchaseService;

class VoucherPurchaseFixtures extends Fixture implements DependentFixtureInterface
{
    final public const FRACTION_TO_SPENT = 5;

    public function __construct(private readonly string $environment, private readonly PurchaseService $purchaseService)
    {
    }

    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production enviroment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        mt_srand(42);

        $booklets = $manager->getRepository(Booklet::class)->findBy([
            'status' => Booklet::DISTRIBUTED,
        ], ['id' => 'asc']);
        echo "Booklets to purchase: " . count($booklets) . ", make purchases for 1/" . self::FRACTION_TO_SPENT . "\n";
        $bookletIndex = 0;
        foreach ($booklets as $booklet) {
            /** @var Assistance $assistance */
            $assistance = $booklet->getAssistanceBeneficiary()->getAssistance();
            if (!$assistance->isValidated()) {
                continue;
            }

            if ($booklet->getId() % self::FRACTION_TO_SPENT !== 0) {
                continue;
            }

            $vendorCode = ($bookletIndex++ % VendorFixtures::VENDOR_COUNT_PER_COUNTRY) + 1;
            /** @var Vendor $vendor */
            $vendor = $this->getReference(
                VendorFixtures::REF_VENDOR_GENERIC . '_' . $assistance->getProject()->getCountryIso3(
                ) . '_' . $vendorCode
            );

            if ($booklet->getVouchers()->count() < 3) {
                echo "(too little vouchers, Booklet#{$booklet->getId()}) ";
                continue;
            }

            switch ($vendorCode) {
                case 1:
                    $this->generatePurchase($vendor, [$booklet->getVouchers()[0]], $manager);
                    $this->generatePurchase($vendor, [$booklet->getVouchers()[1]], $manager);
                    $this->generatePurchase($vendor, [$booklet->getVouchers()[2]], $manager);
                    break;
                case 2:
                    $this->generatePurchase($vendor, [
                        $booklet->getVouchers()[0],
                        $booklet->getVouchers()[1],
                    ], $manager);
                    $this->generatePurchase($vendor, [$booklet->getVouchers()[2]], $manager);
                    break;
                default:
                case 3:
                    $this->generatePurchase($vendor, [
                        $booklet->getVouchers()[0],
                        $booklet->getVouchers()[1],
                        $booklet->getVouchers()[2],
                    ], $manager);
                    break;
            }

            echo ".";
        }
        echo "\n";

        $manager->flush();
    }

    private function generatePurchase(Vendor $vendor, array $vouchers, ObjectManager $manager): VoucherPurchase
    {
        $input = new \InputType\VoucherPurchase();

        $products = [];
        for ($j = 0; $j < random_int(1, 3); ++$j) {
            $quantity = random_int(1, 10000);
            $value = random_int(1, 10000);
            $products[] = [
                'id' => $this->randomEntity(Product::class, $manager),
                'quantity' => $quantity / 100,
                'value' => $value / 100,
            ];
        }
        $input->setProducts($products);

        $input->setCreatedAt(new DateTimeImmutable('now'));
        $input->setVendorId($vendor->getId());
        $input->setVouchers(
            array_map(fn($voucher) => $voucher->getId(), $vouchers)
        );

        return $this->purchaseService->purchase($input);
    }

    private function randomEntity($classname, ObjectManager $manager)
    {
        $entities = $manager->getRepository($classname)->findBy([], ['id' => 'asc'], 10, 0);
        if (0 === count($entities)) {
            return null;
        }

        $i = random_int(0, count($entities) - 1);

        return $entities[$i];
    }

    public function getDependencies(): array
    {
        return [
            BeneficiaryTestFixtures::class,
            VendorFixtures::class,
            AssistanceFixtures::class,
            BookletFixtures::class,
            AssistanceValidationFixtures::class,
        ];
    }
}
