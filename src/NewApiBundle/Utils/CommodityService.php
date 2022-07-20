<?php


namespace NewApiBundle\Utils;

use NewApiBundle\Entity\Commodity;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CommodityService
 * @package NewApiBundle\Utils
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
            ->setModalityType(
                $this->em->getRepository(ModalityType::class)
                    ->find($commodityArray["modality_type"]["id"])
            )
            ->setDescription($commodityArray["description"]);

        if ($flush) {
            $this->em->persist($commodity);
            $this->em->flush();
        }

        return $commodity;
    }
}
