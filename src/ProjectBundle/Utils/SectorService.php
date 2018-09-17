<?php


namespace ProjectBundle\Utils;


use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Sector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SectorService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /**
     * SectorService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @return object[]
     */
    public function findAll()
    {
        return $this->em->getRepository(Sector::class)->findAll();
    }

    /**
     * @param $sectorArray
     * @return array|mixed|object
     * @throws \Exception
     */
    public function create($sectorArray)
    {
        $sector = $this->serializer->deserialize(json_encode($sectorArray), Sector::class, 'json');

        $errors = $this->validator->validate($sector);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray));
        }

        $this->em->persist($sector);

        $this->em->flush();

        return $sector;
    }

    /**
     * @param Sector $sector
     * @param array $sectorArray
     * @return Sector
     * @throws \Exception
     */
    public function edit(Sector $sector, array $sectorArray)
    {
        /** @var Sector $editedSector */
        $editedSector = $this->serializer->deserialize(json_encode($sectorArray), Sector::class, 'json');

        $editedSector->setId($sector->getId());

        $errors = $this->validator->validate($editedSector);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->merge($editedSector);
        $this->em->flush();

        return $editedSector;
    }
}