<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Kernel;
use VoucherBundle\Entity\Vendor;

class VendorFixtures extends Fixture implements DependentFixtureInterface
{
    const REF_VENDOR_KHM = 'vendor_fixtures_khm';
    const REF_VENDOR_SYR = 'vendor_fixtures_syr';

    /** @var Kernel */
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $vendorSyr = $this->createSyrVendor($manager);
        $vendorKhm = $this->createKhmVendor($manager);

        $manager->persist($vendorSyr);
        $manager->persist($vendorKhm);
        $manager->flush();

        $this->setReference(self::REF_VENDOR_SYR, $vendorSyr);
        $this->setReference(self::REF_VENDOR_KHM, $vendorKhm);
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

        $adm1 = $manager->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'SYR']);
        $adm2 = $manager->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1]);

        $vendor = new Vendor();
        $vendor
            ->setName('Vendor from Syria')
            ->setShop('shop')
            ->setAddressNumber('13')
            ->setAddressStreet('Main street')
            ->setAddressPostcode('12345')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2->getLocation());

        return $vendor;
    }

    private function createKhmVendor(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixtures::REF_VENDOR_KHM);

        $adm1 = $manager->getRepository(Adm1::class)->findOneBy(['countryISO3' => 'KHM']);
        $adm2 = $manager->getRepository(Adm2::class)->findOneBy(['adm1' => $adm1]);

        $vendor = new Vendor();
        $vendor
            ->setName('Vendor from Cambodia')
            ->setShop('market')
            ->setAddressNumber('1')
            ->setAddressStreet('Main boulevard')
            ->setAddressPostcode('54321')
            ->setArchived(false)
            ->setUser($user)
            ->setLocation($adm2->getLocation());

        return $vendor;
    }
}
