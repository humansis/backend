<?php

declare(strict_types=1);

namespace DataFixtures;

use Component\Smartcard\Invoice\Exception\AlreadyRedeemedInvoiceException;
use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use Component\Smartcard\Invoice\InvoiceFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectManager;
use Entity\User;
use Entity\Vendor;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\SmartcardPurchaseRepository;

class SmartcardInvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly string $environment, private readonly SmartcardPurchaseRepository $smartcardPurchaseRepository, private readonly InvoiceFactory $invoiceFactory)
    {
    }

    /**
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production enviroment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        mt_srand(42);

        /**
         * @var User $adminUser
         */
        $adminUser = $this->getReference('user_admin');

        /**
         * @var Vendor $khmVendor
         */
        $khmVendor = $this->getReference(VendorFixtures::REF_VENDOR_KHM);

        /**
         * @var Vendor $syrVendor
         */
        $syrVendor = $this->getReference(VendorFixtures::REF_VENDOR_SYR);

        $this->createInvoices($khmVendor, $adminUser);
        $this->createInvoices($syrVendor, $adminUser);
    }

    public function getDependencies(): array
    {
        return [
            SmartcardFixtures::class,
            VendorFixtures::class,
            UserFixtures::class,
        ];
    }

    /**
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createInvoices(Vendor $vendor, User $user): void
    {
        $purchases = $this->smartcardPurchaseRepository->findBy([
            'vendor' => $vendor,
            'redemptionBatch' => null,
        ], ['id' => 'asc']);
        $purchaseIds = [];
        foreach ($purchases as $purchase) {
            $purchaseIds[$purchase->getAssistance()->getProject()->getId()][] = $purchase->getId();
        }

        foreach ($purchaseIds as $ids) {
            $invoice = new SmartcardInvoiceCreateInputType();
            $invoice->setPurchaseIds(array_slice($ids, 1, 5));
            $this->invoiceFactory->create(
                $vendor,
                $invoice,
                $user
            );
        }
    }
}
