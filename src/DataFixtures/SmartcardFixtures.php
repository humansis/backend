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
use Entity\SmartcardBeneficiary;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Model\PurchaseService;
use Repository\SmartcardBeneficiaryRepository;
use Utils\SmartcardService;

class SmartcardFixtures extends Fixture implements DependentFixtureInterface
{
    private const MAX_SMARTCARDS = 20;

    public function __construct(
        private readonly string $environment,
        private readonly SmartcardService $smartcardService,
        private readonly PurchaseService $purchaseService,
        private readonly DepositFactory $depositFactory,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository
    ) {
    }

    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production enviroment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        mt_srand(42);

        foreach (
            $this->getReference(
                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_KHR
            )->getDistributionBeneficiaries() as $ab
        ) {
            $this->generatePackages($manager, $ab, 'KHR');
        }

        //todo to not use XXX_USD until todo in AssistanceFixtures is resolved
//        foreach (
//            $this->getReference(
//                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_USD
//            )->getDistributionBeneficiaries() as $ab
//        ) {
//            $this->generatePackages($manager, $ab, 'USD');
//        }

        foreach (
            $this->getReference(
                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_SYP
            )->getDistributionBeneficiaries() as $ab
        ) {
            $this->generatePackages($manager, $ab, 'SYP');
        }

//        foreach (
//            $this->getReference(
//                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_USD
//            )->getDistributionBeneficiaries() as $ab
//        ) {
//            $this->generatePackages($manager, $ab, 'USD');
//        }

        $manager->flush();

        foreach (
            $this->getReference(
                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_KHR
            )->getDistributionBeneficiaries() as $ab
        ) {
            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
        }

//        foreach (
//            $this->getReference(
//                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_USD
//            )->getDistributionBeneficiaries() as $ab
//        ) {
//            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
//        }

        foreach (
            $this->getReference(
                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_SYP
            )->getDistributionBeneficiaries() as $ab
        ) {
            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
        }

//        foreach (
//            $this->getReference(
//                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_USD
//            )->getDistributionBeneficiaries() as $ab
//        ) {
//            $this->generateDeposits($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
//        }

        $manager->flush();

        foreach (
            $this->getReference(
                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_KHR
            )->getDistributionBeneficiaries() as $ab
        ) {
            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
        }

//        foreach (
//            $this->getReference(
//                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_KHM_USD
//            )->getDistributionBeneficiaries() as $ab
//        ) {
//            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_KHM));
//        }

        foreach (
            $this->getReference(
                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_SYP
            )->getDistributionBeneficiaries() as $ab
        ) {
            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
        }

//        foreach (
//            $this->getReference(
//                AssistanceFixtures::REF_SMARTCARD_ASSISTANCE_SYR_USD
//            )->getDistributionBeneficiaries() as $ab
//        ) {
//            $this->generatePurchases($manager, $ab, $this->getReference(VendorFixtures::REF_VENDOR_SYR));
//        }

        $manager->flush();
    }

    private function generatePackages(ObjectManager $manager, AssistanceBeneficiary $ab, string $currency): void
    {
        $serialNumber = self::generateSerialNumber();
        $smartcardBeneficiary = new SmartcardBeneficiary($serialNumber, new DateTimeImmutable('2000-01-01'));
        $smartcardBeneficiary->setState(SmartcardBeneficiary::STATE_ACTIVE);
        $smartcardBeneficiary->setCurrency($currency);
        $smartcardBeneficiary->setBeneficiary($ab->getBeneficiary());
        $manager->persist($ab);
        $manager->persist($ab->getBeneficiary());
        $manager->persist($smartcardBeneficiary);
        $manager->flush();

        $reliefPackage = new ReliefPackage(
            $ab,
            ModalityType::SMART_CARD,
            $ab->getAssistance()->getCommodities()[0]->getValue(),
            $ab->getAssistance()->getCommodities()[0]->getUnit(),
        );
        $manager->persist($reliefPackage);
    }

    private function generateDeposits(ObjectManager $manager, AssistanceBeneficiary $ab, Vendor $vendor): void
    {
        $packages = $this->reliefPackageRepository->findBy(['assistanceBeneficiary' => $ab], ['id' => 'asc']);

        foreach ($packages as $package) {
            $i = random_int(5, 10);
            $this->depositFactory->create(
                $ab->getBeneficiary()->getSmartcardSerialNumber(),
                DepositInputType::create(
                    $package->getId(),
                    $package->getAmountToDistribute(),
                    null,
                    new \DateTime("now-${i} days")
                ),
                $this->randomEntity(User::class, $manager)
            );
        }
    }

    private function generatePurchases(ObjectManager $manager, AssistanceBeneficiary $ab, Vendor $vendor): void
    {
        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findActiveBySerialNumber(
            $ab->getBeneficiary()->getSmartcardSerialNumber()
        );
        $max = $smartcardBeneficiary->getDeposites()[0]->getReliefPackage()->getAmountDistributed();
        $purchasesCount = $this->generateRandomNumbers($max, random_int(1, 10));

        foreach ($purchasesCount as $index => $purchaseMax) {
            if ($purchaseMax === 0) {
                continue;
            }
            $this->generatePurchase($index, $smartcardBeneficiary, $vendor, $ab->getAssistance(), $manager, $purchaseMax);
        }
    }

    private static function generateSerialNumber()
    {
        static $i = 0;

        return substr(md5(++$i), 0, 7);
    }

    private static function generateState()
    {
        $i = random_int(0, count(SmartcardBeneficiary::states()) - 1);

        return SmartcardBeneficiary::states()[$i];
    }

    private function generatePurchase(
        $seed,
        SmartcardBeneficiary $smartcardBeneficiary,
        Vendor $vendor,
        Assistance $assistance,
        ObjectManager $manager,
        int $max
    ): SmartcardPurchase {
        $date = new DateTimeImmutable('now');
        $purchase = SmartcardPurchase::create($smartcardBeneficiary, $vendor, $date, $assistance);
        $purchase->setHash($this->purchaseService->hashPurchase($smartcardBeneficiary->getBeneficiary(), $vendor, $date));

        $currency = $smartcardBeneficiary->getDeposites()[0]->getReliefPackage()->getUnit();
        $spent = 0;

        for ($j = 0; $j < random_int(1, 5); ++$j) {
            $quantity = random_int(1, 10000);
            $value = random_int(1, $max);
            $spent += $value;
            if ($spent > $max) {
                break;
            }
            $purchase->addRecord($this->randomEntity(Product::class, $manager), $quantity, $value, $currency);
        }

        return $purchase;
    }

    private function randomEntity($classname, ObjectManager $manager)
    {
        $entities = $manager->getRepository($classname)->findBy([], ['id' => 'asc'], 5, 0);
        if (0 === count($entities)) {
            return null;
        }

        $i = random_int(0, count($entities) - 1);

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

    //helper to randomly divide amount_distributed of relief_package
    public function generateRandomNumbers($max, $count): array
    {
        $numbers = [];

        for ($i = 1; $i < $count; $i++) {
            $random = random_int(1, (int)($max / ($count - $i)));
            $numbers[] = $random;
            $max -= $random;
        }

        $numbers[] = $max;

        shuffle($numbers);

        return $numbers;
    }
}
