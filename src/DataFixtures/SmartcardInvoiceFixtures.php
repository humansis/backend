<?php

declare(strict_types=1);

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use InputType\SmartcardInvoice;
use Repository\SmartcardPurchaseRepository;
use Utils\SmartcardService;

class SmartcardInvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /** @var SmartcardService */
    private $smartcardService;

    /**
     * @var SmartcardPurchaseRepository
     */
    private $smartcardPurchaseRepository;

    /**
     * @param string $environment
     * @param SmartcardService $smartcardService
     * @param SmartcardPurchaseRepository $smartcardPurchaseRepository
     */
    public function __construct(
        string $environment,
        SmartcardService $smartcardService,
        SmartcardPurchaseRepository $smartcardPurchaseRepository
    ) {
        $this->environment = $environment;
        $this->smartcardService = $smartcardService;
        $this->smartcardPurchaseRepository = $smartcardPurchaseRepository;
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

        $purchases = $this->smartcardPurchaseRepository->findBy([
            'vendor' => $this->getReference(VendorFixtures::REF_VENDOR_KHM),
            'redemptionBatch' => null,
        ], ['id' => 'asc']);
        $purchaseIds = [];
        foreach ($purchases as $purchase) {
            $purchaseIds[$purchase->getAssistance()->getProject()->getId()][] = $purchase->getId();
        }

        foreach ($purchaseIds as $projectId => $ids) {
            $invoice = new SmartcardInvoice();
            $invoice->setPurchases(array_slice($ids, 1, 5));
            $this->smartcardService->redeem(
                $this->getReference(VendorFixtures::REF_VENDOR_KHM),
                $invoice,
                $adminUser
            );
        }

        $purchases = $this->smartcardPurchaseRepository->findBy([
            'vendor' => $this->getReference(VendorFixtures::REF_VENDOR_SYR),
            'redemptionBatch' => null,
        ], ['id' => 'asc']);
        $purchaseIds = [];
        foreach ($purchases as $purchase) {
            $purchaseIds[$purchase->getAssistance()->getProject()->getId()][] = $purchase->getId();
        }

        foreach ($purchaseIds as $projectId => $ids) {
            $invoice = new SmartcardInvoice();
            $invoice->setPurchases(array_slice($ids, 1, 5));
            $this->smartcardService->redeem(
                $this->getReference(VendorFixtures::REF_VENDOR_SYR),
                $invoice,
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
