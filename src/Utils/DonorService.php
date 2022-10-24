<?php

namespace Utils;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InputType\DonorCreateInputType;
use InputType\DonorUpdateInputType;
use Entity\Donor;

/**
 * Class DonorService
 *
 * @package Utils
 */
class DonorService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ExportService */
    private $exportService;

    /**
     * DonorService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ExportService $exportService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ExportService $exportService
    ) {
        $this->em = $entityManager;
        $this->exportService = $exportService;
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
     *
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(string $type)
    {
        $exportableTable = $this->em->getRepository(Donor::class)->findAll();

        return $this->exportService->export($exportableTable, 'donors', $type);
    }
}
