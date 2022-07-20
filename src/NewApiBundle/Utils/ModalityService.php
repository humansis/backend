<?php


namespace NewApiBundle\Utils;

use NewApiBundle\Entity\Modality;
use NewApiBundle\Entity\ModalityType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ModalityService
 * @package NewApiBundle\Utils
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
     * @return array
     */
    public function getAllModalityTypes(Modality $modality)
    {
        return $modality->getModalityTypes()->filter(function (ModalityType $mt) {
            return !$mt->isInternal();
        })->getValues();
    }
}
