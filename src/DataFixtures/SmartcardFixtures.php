<?php

namespace DataFixtures;

use DateTimeImmutable;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Smartcard\Deposit\DepositFactory;
use Entity\Assistance\ReliefPackage;
use Enum\ModalityType;
use Entity\User;
use InputType\Smartcard\DepositInputType;
use Repository\Assistance\ReliefPackageRepository;
use Entity\Product;
use Entity\Smartcard;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Model\PurchaseService;
use Repository\SmartcardRepository;
use Utils\SmartcardService;

class SmartcardFixtures extends Fixture implements DependentFixtureInterface
{
    private const MAX_SMARTCARDS = 20;

    /** @var string */
    private $environment;

    /** @var SmartcardService */
    private $smartcardService;

    /**
     * @var PurchaseService
     */
    private $purchaseService;

    /**
     * @var DepositFactory
     */
    private $depositFactory;

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    /**
     * @param string                  $environment
     * @param SmartcardService        $smartcardService
     * @param PurchaseService         $purchaseService
     * @param DepositFactory          $depositFactory
     * @param ReliefPackageRepository $reliefPackageRepository
     * @param SmartcardRepository     $smartcardRepository
     */
    public function __construct(
        string                  $environment,
        SmartcardService        $smartcardService,
        PurchaseService         $purchaseService,
        DepositFactory          $depositFactory,
        ReliefPackageRepository $reliefPackageRepository,
        SmartcardRepository     $smartcardRepository
    )
    {
        $this->environment = $environment;
        $this->smartcardService = $smartcardService;
        $this->purchaseService = $purchaseService;
        $this->depositFactory = $depositFactory;
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->smartcardRepository = $smartcardRepository;
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

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_KHR)->getDistributionBeneficiaries() as $ab) {
            $this->generatePackages($manager, $ab, 'KHR');
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_USD)->getDistributionBeneficiaries() as $ab) {
            $this->generatePackages($manager, $ab, 'USD');
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_SYP)->getDistributionBeneficiaries() as $ab) {
            $this->generatePackages($manager, $ab, 'SYP');
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_USD)->getDistributionBeneficiaries() as $ab) {
            $this->generatePackages($manager, $ab, 'USD');
        }

        $manager->flush();

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_KHR)->getDistributionBeneficiaries() as $ab) {
            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_USD)->getDistributionBeneficiaries() as $ab) {
            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_SYP)->getDistributionBeneficiaries() as $ab) {
            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_USD)->getDistributionBeneficiaries() as $ab) {
            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
        }

        $manager->flush();

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_KHR)->getDistributionBeneficiaries() as $ab) {
            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_USD)->getDistributionBeneficiaries() as $ab) {
            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_SYP)->getDistributionBeneficiaries() as $ab) {
            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
        }

        foreach ($this->getReference(AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_USD)->getDistributionBeneficiaries() as $ab) {
            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
        }

        $manager->flush();
    }

    private function generatePackages(ObjectManager $manager, AssistanceBeneficiary $ab, string $currency): void
    {
        $serialNumber = self::generateSerialNumber();
        $smartcard = new Smartcard($serialNumber, new DateTimeImmutable('2000-01-01'));
        $smartcard->setState(Smartcard::STATE_ACTIVE);
        $smartcard->setCurrency($currency);
        $smartcard->setBeneficiary($ab->getBeneficiary());
        $manager->persist($ab);
        $manager->persist($ab->getBeneficiary());
        $manager->persist($smartcard);
        $manager->flush();

        foreach (range(1, rand(2, 4)) as $i) {
            $reliefPackage = new ReliefPackage(
                $ab,
                ModalityType::SMART_CARD,
                $ab->getAssistance()->getCommodities()[0]->getValue(),
                $ab->getAssistance()->getCommodities()[0]->getUnit(),
            );
            $manager->persist($reliefPackage);
        }
    }

    private function generateDeposits(ObjectManager $manager, AssistanceBeneficiary $ab, Vendor $vendor): void
    {
        $packages = $this->reliefPackageRepository->findBy(['assistanceBeneficiary' => $ab], ['id' => 'asc']);

        foreach ($packages as $package) {
            $i = rand(5, 10);
            $this->depositFactory->create(
                $ab->getBeneficiary()->getSmartcardSerialNumber(),
                DepositInputType::create(
                    $package->getId(),
                    $package->getAmountToDistribute(),
                    null,
                    new DateTimeImmutable("now-${i} days")
                ),
                $this->randomEntity(User::class, $manager)
            );
        }
    }

    private function generatePurchases(ObjectManager $manager, AssistanceBeneficiary $ab, Vendor $vendor): void
    {
        $smartcard = $this->smartcardRepository->findActiveBySerialNumber($ab->getBeneficiary()->getSmartcardSerialNumber());
        for ($j = 0; $j < rand(0, 50); ++$j) {
            $this->generatePurchase($j, $smartcard, $vendor, $j > 3 ? $ab->getAssistance() : null, $manager);
        }
    }

    private static function generateSerialNumber()
    {
        static $i = 0;

        return substr(md5(++$i), 0, 7);
    }

    private static function generateState()
    {
        $i = rand(0, count(Smartcard::states()) - 1);

        return Smartcard::states()[$i];
    }

    private function generatePurchase($seed, Smartcard $smartcard, Vendor $vendor, ?Assistance $assistance, ObjectManager $manager): SmartcardPurchase
    {
        $date = new DateTimeImmutable('now');
        $purchase = SmartcardPurchase::create($smartcard, $vendor, $date, $assistance);
        $purchase->setHash($this->purchaseService->hashPurchase($smartcard->getBeneficiary(), $vendor, $date));

        for ($j = 0; $j < rand(1, 3); ++$j) {
            $quantity = rand(1, 10000);
            $value = rand(1, 10000);
            $purchase->addRecord($this->randomEntity(Product::class, $manager), $quantity, $value, $smartcard->getCurrency());
        }

        return $purchase;
    }

    private function randomEntity($classname, ObjectManager $manager)
    {
        $entities = $manager->getRepository($classname)->findBy([], ['id' => 'asc'], 5, 0);
        if (0 === count($entities)) {
            return null;
        }

        $i = rand(0, count($entities) - 1);

        return $entities[$i];
    }

    public function getDependencies()
    {
        return [
            BeneficiaryTestFixtures::class,
            VendorFixtures::class,
            AssistanceFixtures::class,
            ProductFixtures::class,
        ];
    }
}
