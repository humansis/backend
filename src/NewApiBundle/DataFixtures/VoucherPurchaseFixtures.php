<?php

namespace NewApiBundle\DataFixtures;

use DateTimeImmutable;
use NewApiBundle\Entity\Assistance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\Booklet;
use NewApiBundle\Entity\Product;
use NewApiBundle\Entity\Vendor;
use NewApiBundle\Entity\VoucherPurchase;
use NewApiBundle\Model\PurchaseService;

class VoucherPurchaseFixtures extends Fixture implements DependentFixtureInterface
{
    const FRACTION_TO_SPENT = 5;

    /** @var string */
    private $environment;

    /** @var PurchaseService */
    private $purchaseService;

    /**
     * @param string          $environment
     * @param PurchaseService $purchaseService
     */
    public function __construct(string $environment, PurchaseService $purchaseService)
    {
        $this->environment = $environment;
        $this->purchaseService = $purchaseService;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production enviroment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        srand(42);

        $booklets = $manager->getRepository(Booklet::class)->findBy([
            'status' => Booklet::DISTRIBUTED,
        ], ['id' => 'asc']);
        echo "Booklets to purchase: ".count($booklets).", make purchases for 1/".self::FRACTION_TO_SPENT."\n";
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
            $vendor = $this->getReference(VendorFixtures::REF_VENDOR_GENERIC.'_'.$assistance->getProject()->getIso3().'_'.$vendorCode);

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
                        $booklet->getVouchers()[1]
                    ], $manager);
                    $this->generatePurchase($vendor, [$booklet->getVouchers()[2]], $manager);
                    break;
                default:
                case 3:
                    $this->generatePurchase($vendor, [
                        $booklet->getVouchers()[0],
                        $booklet->getVouchers()[1],
                        $booklet->getVouchers()[2]
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
        $input = new \NewApiBundle\InputType\VoucherPurchase();

        $products = [];
        for ($j = 0; $j < rand(1, 3); ++$j) {
            $quantity = rand(1, 10000);
            $value = rand(1, 10000);
            $products[] = [
                'id' => $this->randomEntity(Product::class, $manager),
                'quantity' => $quantity/100,
                'value' =>$value/100
            ];
        }
        $input->setProducts($products);

        $input->setCreatedAt(new DateTimeImmutable('now'));
        $input->setVendorId($vendor->getId());
        $input->setVouchers(array_map(function ($voucher) {return $voucher->getId(); }, $vouchers));

        return $this->purchaseService->purchase($input);
    }

    private function randomEntity($classname, ObjectManager $manager)
    {
        $entities = $manager->getRepository($classname)->findBy([], ['id' => 'asc'], 10, 0);
        if (0 === count($entities)) {
            return null;
        }

        $i = rand(0, count($entities) - 1);

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
