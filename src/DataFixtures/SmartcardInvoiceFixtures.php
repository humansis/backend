<?php

declare(strict_types=1);

namespace DataFixtures;

use Component\Smartcard\Invoice\Exception\AlreadyRedeemedInvoiceException;
use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use Component\Smartcard\Invoice\InvoiceFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectManager;
use Entity\User;
use Entity\Vendor;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\SmartcardPurchaseRepository;

class SmartcardInvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /**
     * @var SmartcardPurchaseRepository
     */
    private $smartcardPurchaseRepository;

    /**
     * @var InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @param string $environment
     * @param SmartcardPurchaseRepository $smartcardPurchaseRepository
     * @param InvoiceFactory $invoiceFactory
     */
    public function __construct(
        string $environment,
        SmartcardPurchaseRepository $smartcardPurchaseRepository,
        InvoiceFactory $invoiceFactory
    ) {
        $this->environment = $environment;
        $this->smartcardPurchaseRepository = $smartcardPurchaseRepository;
        $this->invoiceFactory = $invoiceFactory;
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws AlreadyRedeemedInvoiceException
     * @throws NotRedeemableInvoiceException
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
        srand(42);

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
     * @param Vendor $vendor
     * @param User $user
     *
     * @return void
     * @throws AlreadyRedeemedInvoiceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotRedeemableInvoiceException
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

        foreach ($purchaseIds as $projectId => $ids) {
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
