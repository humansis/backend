<?php

declare(strict_types=1);

namespace DataFixtures;

use Component\Smartcard\Invoice\InvoiceFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ObjectManager;
use Entity\User;
use Entity\Vendor;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Repository\SmartcardPurchaseRepository;

class SmartcardInvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly string $environment,
        private readonly SmartcardPurchaseRepository $smartcardPurchaseRepository,
        private readonly InvoiceFactory $invoiceFactory,
        private readonly PreliminaryInvoiceRepository $preliminaryInvoiceRepository,
    ) {
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

        $this->createInvoices($khmVendor, $adminUser, 'KHR');
        $this->createInvoices($syrVendor, $adminUser, 'SYP');
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
    private function createInvoices(Vendor $vendor, User $user, string $currency): void
    {
        $preliminaryInvoices = $this->preliminaryInvoiceRepository->findBy(
            ['vendor' => $vendor, 'currency' => $currency]
        );
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $invoiceInputType = new SmartcardInvoiceCreateInputType();
            $invoiceInputType->setPurchaseIds(array_slice($preliminaryInvoice->getPurchaseIds(), 1, 5));
            $this->invoiceFactory->create(
                $vendor,
                $invoiceInputType,
                $user
            );
        }
    }
}
