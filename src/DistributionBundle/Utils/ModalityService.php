<?php


namespace DistributionBundle\Utils;


use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

class ModalityService
{

    /** @var EntityManagerInterface $em */
    private $em;


    public function __construct(EntityManagerInterface $manager)
    {
        $this->em = $manager;
    }

    public function getAll()
    {
        return $this->em->getRepository(Modality::class)->findAll();
    }

    public function getAllModalityTypes(Modality $modality)
    {
        return $modality->getModalityTypes();
    }

}