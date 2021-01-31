<?php

namespace ProjectBundle\Utils;

use CommonBundle\Utils\ExportService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use NewApiBundle\InputType\DonorCreateInputType;
use NewApiBundle\InputType\DonorUpdateInputType;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use ProjectBundle\Entity\Donor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;

/**
 * Class DonorService
 * @package ProjectBundle\Utils
 */
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

    /** @var ExportService */
    private $exportCSVService;

    /**
     * DonorService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Serializer             $serializer
     * @param ValidatorInterface     $validator
     * @param ContainerInterface     $container
     * @param ExportService          $exportCSVService
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator, ContainerInterface $container,
                                ExportService $exportCSVService)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->container = $container;
        $this->exportCSVService = $exportCSVService;}


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
     * @throws Exception
     * @deprecated
     */
    public function createFromArray(array $donorArray)
    {
        $donor = new Donor();

        $donor->setFullname($donorArray["fullname"])
            ->setShortname($donorArray["shortname"])
            ->setNotes($donorArray["notes"])
            ->setLogo($donorArray["logo"]);

        $donor->setDateAdded(new DateTime());

        $errors = $this->validator->validate($donor);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            throw new Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($donor);
        $this->em->flush();

        return $donor;
    }

    public function create(DonorCreateInputType $inputType): Donor
    {
        $donor = (new Donor())
            ->setFullname($inputType->getFullname())
            ->setShortname($inputType->getShortname())
            ->setNotes($inputType->getNotes())
            ->setLogo($inputType->getLogo())
            ->setDateAdded(new DateTime());

        $this->em->persist($donor);
        $this->em->flush();

        return $donor;
    }

    public function update(Donor $donor, DonorUpdateInputType $inputType): void
    {
        $donor
            ->setFullname($inputType->getFullname())
            ->setShortname($inputType->getShortname())
            ->setNotes($inputType->getNotes())
            ->setLogo($inputType->getLogo());

        $this->em->persist($donor);
        $this->em->flush();
    }

    /**
     * @param Donor $donor
     * @param array $donorArray
     * @return Donor
     * @throws Exception
     * @deprecated
     */
    public function edit(Donor $donor, array $donorArray)
    {
        /** @var Donor $editedDonor */
    $donor->setFullname($donorArray["fullname"])
        ->setShortname($donorArray["shortname"])
        ->setNotes($donorArray["notes"])
        ->setLogo($donorArray["logo"]);


        $errors = $this->validator->validate($donor);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            throw new Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($donor);
        $this->em->flush();

        return $donor;
    }

    /**
     * @param Donor $donor
     * @return bool
     */
    public function delete(Donor $donor)
    {
        try {
            $this->em->remove($donor);
            $this->em->flush();
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Export all the donors in the CSV file
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(string $type)
    {
        $exportableTable = $this->em->getRepository(Donor::class)->findAll();

        return $this->exportCSVService->export($exportableTable, 'donors', $type);
    }
}
