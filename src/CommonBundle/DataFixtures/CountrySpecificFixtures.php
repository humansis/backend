<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use BeneficiaryBundle\Entity\CountrySpecific;
use Symfony\Component\HttpKernel\KernelInterface;

class CountrySpecificFixtures extends Fixture
{
    private $data = [
        ['IDPoor', 'number', 'KHM'],
        ['equityCardNo', 'text', 'KHM']
    ];

    /** @var KernelInterface */
    private $kernel;

    /**
     * CampFixtures constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManagerInterface
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $countrySpecific = $manager->getRepository(CountrySpecific::class)->findOneBy([
                "fieldString" => $datum[0],
                "type" => $datum[1],
                "countryIso3" => $datum[2]
            ]);
            if (!$countrySpecific instanceof CountrySpecific) {
                $countrySpecific = new CountrySpecific($datum[0], $datum[1], $datum[2]);
                $manager->persist($countrySpecific);
                $manager->flush();
            }
        }
    }
}
