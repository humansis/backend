<?php


namespace DistributionBundle\Utils;

use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CommodityService
 * @package DistributionBundle\Utils
 */
class CommodityService
{

    /** @var EntityManagerInterface $em */
    private $em;


    /**
     * CommodityService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param Assistance $distribution
     * @param array $commodityArray
     * @param bool $flush
     * @return Commodity
     */
    public function create(Assistance $distribution, array $commodityArray, bool $flush)
    {
        $commodity = new Commodity();
        $commodity->setValue($commodityArray["value"])
            ->setAssistance($distribution)
            ->setUnit($commodityArray["unit"])
            ->setModalityType($commodityArray["modality_type"])
            ->setDescription($commodityArray["description"]);

        if ($flush) {
            $this->em->persist($commodity);
            $this->em->flush();
        }

        return $commodity;
    }
}
