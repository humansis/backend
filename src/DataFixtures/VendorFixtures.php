<?php

namespace DataFixtures;

use Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Country\Countries;
use Symfony\Component\HttpKernel\Kernel;
use Entity\User;
use Entity\Vendor;

class VendorFixtures extends Fixture implements DependentFixtureInterface
{
    public const REF_VENDOR_KHM = 'vendor_fixtures_khm';
    public const REF_VENDOR_SYR = 'vendor_fixtures_syr';
    public const REF_VENDOR_GENERIC = 'vendor_fixtures_generic';
    public const VENDOR_KHM_NAME = 'Vendor from Cambodia';
    public const VENDOR_SYR_NAME = 'Vendor from Syria';
    public const VENDOR_COUNT_PER_COUNTRY = 3;

    /** @var Kernel */
    private $kernel;

    /** @var Countries */
    private $countries;

    public function __construct(Kernel $kernel, Countries $countries)
    {
        $this->kernel = $kernel;
        $this->countries = $countries;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        srand(42);

        $vendorSyr = $this->createSyrVendor($manager);
        $vendorKhm = $this->createKhmVendor($manager);

        $manager->persist($vendorSyr);
        $manager->persist($vendorKhm);

        $this->setReference(self::REF_VENDOR_SYR, $vendorSyr);
        $this->setReference(self::REF_VENDOR_KHM, $vendorKhm);

        foreach ($this->countries->getAll() as $country) {
            foreach (range(1, self::VENDOR_COUNT_PER_COUNTRY) as $index) {
                $vendor = $this->createGenericVendor($manager, $country->getIso3());
                $this->setReference(self::REF_VENDOR_GENERIC . '_' . $country->getIso3() . '_' . $index, $vendor);
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            LocationFixtures::class,
        ];
    }

    private function createSyrVendor(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixtures::REF_VENDOR_SYR);

        $adm2 = $manager->getRepository(Location::class)->findOneBy(
            ['countryIso3' => 'SYR', 'lvl' => 2],
            ['id' => 'asc']
        );

        $vendor = new Vendor();
        $vendor
            ->setName(self::VENDOR_SYR_NAME)
            ->setShop('shop')
            ->setAddressNumber('13')
            ->setAddressStreet('Main street')
            ->setAddressPostcode('12345')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2)
            ->setVendorNo('SYR' . sprintf('%07d', random_int(100, 10000)))
            ->setContractNo('SYRSP' . sprintf('%06d', random_int(100, 10000)));

        return $vendor;
    }

    private function createKhmVendor(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixtures::REF_VENDOR_KHM);

        $adm2 = $manager->getRepository(Location::class)->findOneBy(
            ['countryIso3' => 'KHM', 'lvl' => 2],
            ['id' => 'asc']
        );

        $vendor = new Vendor();
        $vendor
            ->setName(self::VENDOR_KHM_NAME)
            ->setShop('market')
            ->setAddressNumber('1')
            ->setAddressStreet('Main boulevard')
            ->setAddressPostcode('54321')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2)
            ->setVendorNo('KHM' . sprintf('%07d', random_int(100, 10000)))
            ->setContractNo('KHMSP' . sprintf('%06d', random_int(100, 10000)));

        return $vendor;
    }

    private function createGenericVendor(ObjectManager $manager, string $country): Vendor
    {
        $user = $this->makeGenericUser($manager, $country);

        $adm2 = $manager->getRepository(Location::class)->findOneBy(
            ['countryIso3' => $country, 'lvl' => 2],
            ['id' => 'asc']
        );

        $vendor = new Vendor();
        $vendor
            ->setName('Generic vendor from ' . $country)
            ->setShop('generic')
            ->setAddressNumber(rand(1, 1000))
            ->setAddressStreet('Main street')
            ->setAddressPostcode(rand(10000, 99999))
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2)
            ->setVendorNo($country . sprintf('%07d', random_int(100, 10000)))
            ->setContractNo($country . 'SP' . sprintf('%06d', random_int(100, 10000)));

        $manager->persist($vendor);

        return $vendor;
    }

    private function makeGenericUser(ObjectManager $manager, string $country): User
    {
        static $genericUserCount = 0;
        $userIndex = ++$genericUserCount;
        $email = "vendor$userIndex.$country@example.org";
        $instance = new User();

        $instance->injectObjectManager($manager);

        $instance->setEnabled(1)
            ->setEmail($email)
            ->setEmailCanonical($email)
            ->setUsername($email)
            ->setUsernameCanonical($email)
            ->setSalt('no salt')
            ->setRoles(['ROLE_VENDOR'])
            ->setChangePassword(0);
        $instance->setPassword('no passwd');
        $manager->persist($instance);

        return $instance;
    }
}
