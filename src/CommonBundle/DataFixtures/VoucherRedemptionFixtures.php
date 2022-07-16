<?php

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\User;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\InputType\VoucherRedemptionBatch;
use VoucherBundle\Utils\VoucherService;

class VoucherRedemptionFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /** @var VoucherService */
    private $voucherService;

    /**
     * @param string         $environment
     * @param VoucherService $voucherService
     */
    public function __construct(string $environment, VoucherService $voucherService)
    {
        $this->environment = $environment;
        $this->voucherService = $voucherService;
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

        $vendors = $manager->getRepository(Vendor::class)->findAll();
        $user = $manager->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        // $user = $this->getReference('user_admin');

        foreach ($vendors as $vendor) {
            echo "Vendor ".$vendor->getName().'#'.$vendor->getId();

            $vouchers = [];
            $usedVouchers = $manager->getRepository(Voucher::class)->findUsedButUnredeemedByVendor($vendor);

            $count = count($usedVouchers);
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
