<?php

namespace ProjectBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use ProjectBundle\Entity\Donor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;

class DonorService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->container = $container;
    }


    /**
     * Return all donors
     *
     * @return array
     */
    public function findAll()
    {
        return $this->em->getRepository(Donor::class)->findAll();
    }

    /**
     * Create a new donor from an array. The array will be deserialized with JMS
     *
     * @param array $donorArray
     * @return mixed
     * @throws \Exception
     */
    public function create(array $donorArray)
    {
        $donor = $this->serializer->deserialize(json_encode($donorArray), Donor::class, 'json');
        $donor->setDateAdded(new \DateTime());

        $errors = $this->validator->validate($donor);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($donor);
        $this->em->flush();

        return $donor;
    }

    /**
     * @param Donor $donor
     * @param array $donorArray
     * @return Donor
     * @throws \Exception
     */
    public function edit(Donor $donor, array $donorArray)
    {
        /** @var Donor $editedDonor */
        $editedDonor = $this->serializer->deserialize(json_encode($donorArray), Donor::class, 'json');

        $editedDonor->setId($donor->getId());

        $errors = $this->validator->validate($editedDonor);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->merge($editedDonor);
        $this->em->flush();

        return $editedDonor;
    }

    /**
     * @param Donor $donor
     * @return bool
     */
    public function delete(Donor $donor)
    {
        try
        {
            $this->em->remove($donor);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
            return false;
        }

        return true;
    }

    /**
     * Export all the donors in the CSV file
     * @return mixed
     */
    public function exportToCsv() {

        $exportableTable = $this->em->getRepository(Donor::class)->findAll();

        /*$donorData = array();
        foreach ($exportableTable as $value){
            array_push($donorData, [
                "Full name" => $value->getFullName(),
                "Short name"=> $value->getShortname(),
                "Date added" => $value->getDateAdded()->format('Y-m-d H:i:s'),
                "Notes" => $value->getNotes(),
                //"Project" => $value->getProjects()->getValues(),
            ]);
        }*/

        return $this->container->get('export_csv_service')->export($exportableTable, 'donors');

    }
}
