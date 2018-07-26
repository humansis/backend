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

    public function create($name)
    {
        try
        {
            $modality = new Modality();
            $modality->setName($name);
            $this->em->persist($modality);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
            throw new \Exception("You can't create the modality '$name'.'");
        }

        return $modality;
    }

    public function createType(Modality $modality, $name)
    {
        try
        {
            $modalityType = new ModalityType();
            $modalityType->setName($name)
                ->setModality($modality);
            $this->em->persist($modalityType);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
            throw new \Exception("You can't create the modality type '$name'.'");
        }
        return $modalityType;
    }

}