<?php

namespace Utils;

use Entity\Commodity;
use Entity\Assistance;
use Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CommodityService
 *
 * @package Utils
 */
class CommodityService
{
    /**
     * CommodityService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
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
