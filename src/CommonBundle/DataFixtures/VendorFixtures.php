<?php


namespace CommonBundle\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use VoucherBundle\Entity\Vendor;
use UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Kernel;


class VendorFixtures extends Fixture
{

    /** @var Kernel $kernel */
    private $kernel;

    private $data = [
        ['vendor', 'shop', 'address', 0, 'vendor']
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
        if ($this->kernel->getEnvironment() === "test") {
            foreach ($this->data as $datum)
            {
                $user = $manager->getRepository(User::class)->findOneByUsername($datum[4]);
                $vendor = new Vendor();
                $vendor->setName($datum[0])
                ->setShop($datum[1])
                ->setAddress($datum[2])
                ->setArchived($datum[3])
                ->setUser($user);
                $manager->persist($vendor);
                $manager->flush();
            }
        }     
    }
}