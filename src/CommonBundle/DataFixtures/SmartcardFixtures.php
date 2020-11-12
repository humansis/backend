<?php

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTimeImmutable;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\DistributionBeneficiary;
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
    /** @var string */
    private $enviroment;

    private $distributionBeneficiary;

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
        if ('prod' === $this->enviroment) {
            // this fixtures are not for production enviroment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        srand(42);

        foreach ([VendorFixtures::REF_VENDOR_KHM, VendorFixtures::REF_VENDOR_SYR] as $i => $ref) {
            $this->generateSeed($manager, $this->getReference($ref));
        }

        $manager->flush();
    }

    private function generateSeed(ObjectManager $manager, Vendor $vendor)
    {
        for ($i = 0; $i < 20; ++$i) {
            $serialNumber = self::generateSerialNumber();
            if ($manager->getRepository(Smartcard::class)->findOneBy(['serialNumber' => $serialNumber])) {
                // fixtures already exists
                return;
            }

            $smartcard = new Smartcard($serialNumber, new DateTimeImmutable('now'));
            $smartcard->setBeneficiary($this->randomEntity(Beneficiary::class, $manager));
            $smartcard->setState(self::generateState());

            for ($j = 0; $j < rand(0, 5); ++$j) {
                $this->generatePurchase($j, $smartcard, $vendor, $manager);
            }

            for ($j = 0; $j < rand(0, 5); ++$j) {
                $this->generateDeposit($j, $smartcard, $manager);
            }

            $manager->persist($smartcard);
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

    private function generatePurchase($seed, Smartcard $smartcard, Vendor $vendor, ObjectManager $manager): SmartcardPurchase
    {
        $purchase = SmartcardPurchase::create($smartcard, $vendor, new DateTimeImmutable('now'));

        for ($j = 0; $j < rand(1, 3); ++$j) {
            $quantity = rand(1, 10000);
            $value = rand(1, 10000);
            $purchase->addRecord($this->randomEntity(Product::class, $manager), $quantity, $value);
        }

        return $purchase;
    }

    private function generateDeposit($seed, Smartcard $smartcard, ObjectManager $manager): SmartcardDeposit
    {
        $value = rand(1, 10000);

        return SmartcardDeposit::create(
            $smartcard,
            $this->randomEntity(User::class, $manager),
            $this->getDistributionBeneficiary($manager),
            $value,
            new DateTimeImmutable('now')
        );
    }

    private function getDistributionBeneficiary(ObjectManager $manager)
    {
        if (null === $this->distributionBeneficiary) {
            $this->distributionBeneficiary = new DistributionBeneficiary();
            $this->distributionBeneficiary->setBeneficiary($this->randomEntity(Beneficiary::class, $manager));
            $this->distributionBeneficiary->setAssistance($this->randomEntity(Assistance::class, $manager));
            $this->distributionBeneficiary->setRemoved(false);
            $manager->persist($this->distributionBeneficiary);
        }

        return $this->distributionBeneficiary;
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
        ];
    }
}
