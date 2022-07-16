<?php

namespace NewApiBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\InputType\DonorCreateInputType;
use NewApiBundle\InputType\DonorUpdateInputType;
use ProjectBundle\Entity\Donor;
use Psr\Container\ContainerInterface;

/**
 * Class DonorService
 * @package ProjectBundle\Utils
 */
class DonorService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * DonorService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function create(DonorCreateInputType $inputType): Donor
    {
        $donor = (new Donor())
            ->setFullname($inputType->getFullname())
            ->setShortname($inputType->getShortname())
            ->setNotes($inputType->getNotes())
            ->setLogo($inputType->getLogo())
            ->setDateAdded(new \DateTime());

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
     * @return bool
     */
    public function delete(Donor $donor)
    {
        try {
            $this->em->remove($donor);
            $this->em->flush();
        } catch (\Exception $exception) {
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

        return $this->container->get('export_csv_service')->export($exportableTable, 'donors', $type);
    }
}
