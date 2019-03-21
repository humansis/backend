<?php


namespace CommonBundle\DataFixtures;


use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;


class ModalityFixtures extends Fixture
{

    private $data = [
        [
            'Cash', 
            [
                'Mobile Money'
                // 'Electronic Bank Transfer',
                // 'Digital Wallet',
            ]
        ],
        [
            'Voucher',
            [
                // 'E-Voucher',
                // 'QR Code Voucher',
                'Paper Voucher'
            ]
        ],
        [
            'In Kind', 
            [
                'Food',
                'RTE Kit',
                'Bread',
                'Agricultural Kit',
                'WASH Kit'
            ]
        ],
        [
            'Other',
            [
                'Loan'
            ]
        ]
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum)
        {
            $instance = $manager->getRepository(Modality::class)->findOneByName($datum[0]);
            if (!$instance instanceof Modality)
            {
                $instance = new Modality();
                $instance->setName($datum[0]);
                $manager->persist($instance);
                $manager->flush();
            }
        }
        foreach ($this->data as $datum)
        {
            $instance = $manager->getRepository(Modality::class)->findOneByName($datum[0]);
            foreach ($datum[1] as $item)
            {
                $instance2 = $manager->getRepository(ModalityType::class)->findOneBy([
                    "modality" => $instance,
                    "name" => $item
                ]);
                if (!$instance2 instanceof ModalityType)
                {
                    $instance2 = new ModalityType();
                    $instance2->setName($item)
                    ->setModality($instance);
                    $manager->persist($instance2);
                    $manager->flush();
                }
            }
        }
    }
}