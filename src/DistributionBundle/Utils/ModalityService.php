<?php


namespace DistributionBundle\Utils;

use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ModalityService
 * @package DistributionBundle\Utils
 */
class ModalityService
{

    /** @var EntityManagerInterface $em */
    private $em;


    /**
     * ModalityService constructor.
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->em = $manager;
    }

    /**
     * @return object[]
     */
    public function getAll()
    {
        return $this->em->getRepository(Modality::class)->findAll();
    }

    /**
     * @param Modality $modality
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAllModalityTypes(Modality $modality)
    {
        return $modality->getModalityTypes();
    }
}
