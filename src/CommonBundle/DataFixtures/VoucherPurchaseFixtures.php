<?php

namespace CommonBundle\DataFixtures;

use DateTimeImmutable;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\VoucherPurchase;

class VoucherPurchaseFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /**
     * @param string $environment
     */
    public function __construct(string $environment)
    {
        $this->environment = $environment;
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

        $assistances = $manager->getRepository(Assistance::class)->findBy([
            'targetType' => AssistanceTargetType::INDIVIDUAL,
            'validated' => true,
        ]);
        /** @var Assistance $assistance */
        foreach ($assistances as $assistance) {

            if ($assistance->getCommodities()[0]->getModalityType()->getModality()->getName() !== 'Voucher') {
                echo $assistance->getId()."#".$assistance->getName().' ';
                echo $assistance->getCommodities()[0]->getModalityType()->getModality()->getName()." is no voucher ass\n";
                continue;
            }
            echo $assistance->getId()."#".$assistance->getName().' ';

            $vendorCode = ($assistance->getId() % VendorFixtures::VENDOR_COUNT_PER_COUNTRY) + 1;
            /** @var Vendor $vendor */
            // $vendor = $this->getReference(VendorFixtures::REF_VENDOR_GENERIC.'_'.$assistance->getProject()->getIso3().'_'.$vendorCode);
            $vendor = $manager->getRepository(Vendor::class)->findOneBy([]);

            echo $assistance->getDistributionBeneficiaries()->count()."bnfs ";

            // use only vouchers of 1/3 bnfs
            $bnfCount = (int) $assistance->getDistributionBeneficiaries()->count() / 3;
            /** @var AssistanceBeneficiary $ab */
            foreach ($assistance->getDistributionBeneficiaries() as $ab) {

                if ($ab->getBooklets()->count() < 1 || $ab->getBooklets()[0]->getVouchers()->count() < 3) {
                    echo "(booklet missing, ABnf#{$ab->getId()}) ";
                    continue;
                }
                if ($ab->getBooklets()[0]->getVouchers()->count() < 3) {
                    echo "(vouchers missing, ABnf#{$ab->getId()}) ";
                    continue;
                }

                switch (rand(0,3)) {
                    case 0:
                        $this->generatePurchase($vendor, [$ab->getBooklets()[0]->getVouchers()[0]], $manager);
                        $this->generatePurchase($vendor, [$ab->getBooklets()[0]->getVouchers()[1]], $manager);
                        $this->generatePurchase($vendor, [$ab->getBooklets()[0]->getVouchers()[2]], $manager);
                        echo '3x1,';
                        break;
                    case 1:
                        $this->generatePurchase($vendor, [$ab->getBooklets()[0]->getVouchers()[0]], $manager);
                        echo '1x1,';
                        break;
                    case 2:
                        $this->generatePurchase($vendor, [
                            $ab->getBooklets()[0]->getVouchers()[0],
                            $ab->getBooklets()[0]->getVouchers()[1]
                        ], $manager);
                        echo '1x2,';
                        break;
                    case 3:
                        $this->generatePurchase($vendor, [
                            $ab->getBooklets()[0]->getVouchers()[0],
                            $ab->getBooklets()[0]->getVouchers()[1],
                            $ab->getBooklets()[0]->getVouchers()[2]
                        ], $manager);
                        echo '1x3,';
                        break;
                    default:
                        echo "|X|";
                }

                if ($bnfCount-- < 1) break;
            }
            echo "\n";
        }

        $manager->flush();
    }

    private function generatePurchase(Vendor $vendor, array $vouchers, ObjectManager $manager): VoucherPurchase
    {
        $purchase = VoucherPurchase::create($vendor, new DateTimeImmutable('now'));

        for ($j = 0; $j < rand(1, 3); ++$j) {
            $quantity = rand(1, 10000);
            $value = rand(1, 10000);
            $purchase->addRecord($this->randomEntity(Product::class, $manager), $quantity/100, $value/100);
        }

        foreach ($vouchers as $voucher) {
            $purchase->addVoucher($voucher);
        }

        return $purchase;
    }

    private function randomEntity($classname, ObjectManager $manager)
    {
        $entities = $manager->getRepository($classname)->findBy([], null, 10, 0);
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
