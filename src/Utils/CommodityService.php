<?php


namespace Utils;

use Entity\Commodity;
use Entity\Assistance;
use Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CommodityService
 * @package Utils
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
     *
     * @deprecated
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
