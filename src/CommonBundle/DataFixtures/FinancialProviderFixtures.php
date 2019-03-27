<?php

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use TransactionBundle\Entity\FinancialProvider;

class FinancialProviderFixtures extends Fixture
{

    /** @var UserManager $manager */
    private $manager;

    /** @var EncoderFactoryInterface $encoderFactory */
    private $encoderFactory;

    public function __construct(UserManager $manager, EncoderFactoryInterface $encoderFactory)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
    }

    private $data = [
        ['thirdParty', '16681c9ff419d8ecc7cfe479eb02a7a']
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $instance = $manager->getRepository(FinancialProvider::class)->findOneByUsername($datum[0]);
            if (!$instance instanceof FinancialProvider) {
                $instance = new FinancialProvider();
                $instance->setUsername($datum[0])
                    ->setCountry('KHM');

                $instance->setPassword(base64_encode($datum[1]));
                $manager->persist($instance);

                $manager->flush();
            }
        }
    }
}
