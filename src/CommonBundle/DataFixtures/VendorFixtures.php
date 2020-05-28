<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use VoucherBundle\Entity\Vendor;
use UserBundle\Entity\User;
use CommonBundle\Entity\Location;
use Symfony\Component\HttpKernel\Kernel;

class VendorFixtures extends Fixture implements DependentFixtureInterface
{
    const VENDOR_EMAIL = 'vendor@example.org';

    /** @var Kernel $kernel */
    private $kernel;

    private $data = [
        ['vendor', 'shop', '1', 'rue de la Paix', '75000', 0, self::VENDOR_EMAIL, 1]
    ];


    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() !== "prod") {
            foreach ($this->data as $vendorData) {
                $user = $manager->getRepository(User::class)->findOneByUsername($vendorData[6]);
                if (!empty($manager->getRepository(Vendor::class)->getVendorByUser($user))) {
                    echo "Vendor {$vendorData[0]} already exists. Ommiting.\n";
                    continue;
                }

                $location = $manager->getRepository(Location::class)->find($vendorData[7]);
                $vendor = new Vendor();
                $vendor->setName($vendorData[0])
                ->setShop($vendorData[1])
                ->setAddressNumber($vendorData[2])
                ->setAddressStreet($vendorData[3])
                ->setAddressPostcode($vendorData[4])
                ->setArchived($vendorData[5])
                ->setUser($user)
                ->setLocation($location);
                $manager->persist($vendor);
                $manager->flush();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            LocationFixtures::class
        ];
    }
}
