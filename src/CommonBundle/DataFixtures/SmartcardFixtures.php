<?php

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTimeImmutable;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;

class SmartcardFixtures extends Fixture implements DependentFixtureInterface
{
    private const MAX_SMARTCARDS = 20;

    /** @var string */
    private $environment;

    private $assistanceBeneficiary;

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

    private function generatePurchases(ObjectManager $manager, AssistanceBeneficiary $ab, Vendor $vendor): void
    {
        $serialNumber = self::generateSerialNumber();
        if ($manager->getRepository(Smartcard::class)->findOneBy(['serialNumber' => $serialNumber])) {
            // fixtures already exists
            return;
        }

        $smartcard = new Smartcard($serialNumber, new DateTimeImmutable('now'));
        $smartcard->setState(self::generateState());
        $smartcard->setCurrency('SYP');

        $smartcard->setBeneficiary($ab->getBeneficiary());
        foreach (range(1, rand(2, 4)) as $i) {
            $smartcard->addDeposit(SmartcardDeposit::create(
                $smartcard,
                $this->randomEntity(User::class, $manager),
                $ab,
                rand(1, 10000),
                null,
                new DateTimeImmutable("now-${i} days")
            ));
        }

        for ($j = 0; $j < rand(0, 50); ++$j) {
            $this->generatePurchase($j, $smartcard, $vendor, $manager);
        }

        $manager->persist($smartcard);
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

    private function generatePurchase($seed, Smartcard $smartcard, Vendor $vendor, ObjectManager $manager): SmartcardPurchase
    {
        $purchase = SmartcardPurchase::create($smartcard, $vendor, new DateTimeImmutable('now'));

        for ($j = 0; $j < rand(1, 3); ++$j) {
            $quantity = rand(1, 10000);
            $value = rand(1, 10000);
            $purchase->addRecord($this->randomEntity(Product::class, $manager), $quantity, $value, $smartcard->getCurrency());
        }

        return $purchase;
    }

    private function randomEntity($classname, ObjectManager $manager)
    {
        $entities = $manager->getRepository($classname)->findBy([], null, 5, 0);
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
