<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use VoucherBundle\Entity\Vendor;
use UserBundle\Entity\User;
use CommonBundle\Entity\Location;
use Symfony\Component\HttpKernel\Kernel;

class VendorFixtures extends Fixture
{

    /** @var Kernel $kernel */
    private $kernel;

    private $data = [
        ['vendor', 'shop', '1', 'rue de la Paix', '75000', 0, 'vendor', 1]
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
        if ($this->kernel->getEnvironment() === "test" || $this->kernel->getEnvironment() === "dev") {
            foreach ($this->data as $datum) {
                $user = $manager->getRepository(User::class)->findOneByUsername($datum[6]);
                $location = $manager->getRepository(Location::class)->find($datum[7]);
                $vendor = new Vendor();
                $vendor->setName($datum[0])
                ->setShop($datum[1])
                ->setAddressNumber($datum[2])
                ->setAddressStreet($datum[3])
                ->setAddressPostcode($datum[4])
                ->setArchived($datum[5])
                ->setUser($user)
                ->setLocation($location);
                $manager->persist($vendor);
                $manager->flush();
            }
        }
    }
}
