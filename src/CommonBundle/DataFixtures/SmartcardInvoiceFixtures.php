<?php declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\InputType\SmartcardInvoice;
use VoucherBundle\InputType\VoucherRedemptionBatch;
use VoucherBundle\Utils\SmartcardService;

class SmartcardInvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /** @var SmartcardService */
    private $smartcardService;

    /**
     * @param string           $environment
     * @param SmartcardService $smartcardService
     */
    public function __construct(string $environment, SmartcardService $smartcardService)
    {
        $this->environment = $environment;
        $this->smartcardService = $smartcardService;
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

        $adminUser = $this->getReference('user_admin');

        $purchases = $manager->getRepository(SmartcardPurchase::class)->findBy([
            'vendor' => $this->getReference(VendorFixtures::REF_VENDOR_KHM),
            'redemptionBatch' => null,
        ], ['id' => 'asc']);
        $purchaseIds = [];
        foreach ($purchases as $purchase) {
            $purchaseIds[$this->smartcardService->extractPurchaseProjectId($purchase)][] = $purchase->getId();
        }

        foreach ($purchaseIds as $projectId => $ids) {
            $batch = new SmartcardInvoice();
            $batch->setPurchases(array_slice($ids, 1, 5));
            $this->smartcardService->redeem(
                $this->getReference(VendorFixtures::REF_VENDOR_KHM),
                $batch,
                $adminUser
            );
        }

        $purchases = $manager->getRepository(SmartcardPurchase::class)->findBy([
            'vendor' => $this->getReference(VendorFixtures::REF_VENDOR_SYR),
            'redemptionBatch' => null,
        ], ['id' => 'asc']);
        $purchaseIds = [];
        foreach ($purchases as $purchase) {
            $purchaseIds[$this->smartcardService->extractPurchaseProjectId($purchase)][] = $purchase->getId();
        }

        foreach ($purchaseIds as $projectId => $ids) {
            $batch = new SmartcardInvoice();
            $batch->setPurchases(array_slice($ids, 1, 5));
            $this->smartcardService->redeem(
                $this->getReference(VendorFixtures::REF_VENDOR_SYR),
                $batch,
                $adminUser
            );
        }
    }

    public function getDependencies(): array
    {
        return [
            SmartcardFixtures::class,
            VendorFixtures::class,
            UserFixtures::class,
        ];
    }
}
