<?php

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\User;
use Entity\Vendor;
use Entity\Voucher;
use InputType\VoucherRedemptionBatch;
use Utils\VoucherService;

class VoucherRedemptionFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly string $environment, private readonly VoucherService $voucherService)
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

        $vendors = $manager->getRepository(Vendor::class)->findAll();
        $user = $manager->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        // $user = $this->getReference('user_admin');

        foreach ($vendors as $vendor) {
            echo "Vendor " . $vendor->getName() . '#' . $vendor->getId();

            $vouchers = [];
            $usedVouchers = $manager->getRepository(Voucher::class)->findUsedButUnredeemedByVendor($vendor);

            $count = is_countable($usedVouchers) ? count($usedVouchers) : 0;
            echo " ($count)";

            $purchaseCount = $vendor->getId();
            foreach ($usedVouchers as $voucher) {
                $vouchers[] = $voucher->getId();
                echo '.';

                if (count($vouchers) >= $purchaseCount) {
                    $redemptionBatch = new VoucherRedemptionBatch();
                    $redemptionBatch->setVouchers($vouchers);
                    $this->voucherService->redeemBatch($vendor, $redemptionBatch, $user);
                    $vouchers = [];
                    $purchaseCount--;
                    echo ';';
                }
                if ($purchaseCount <= 0) {
                    break;
                }
            }

            if (count($vouchers) > 0) {
                $redemptionBatch = new VoucherRedemptionBatch();
                $redemptionBatch->setVouchers($vouchers);
                $this->voucherService->redeemBatch($vendor, $redemptionBatch, $user);
                echo ';';
            }
            echo "\n";
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VoucherPurchaseFixtures::class,
            VendorFixtures::class,
            UserFixtures::class,
        ];
    }
}
