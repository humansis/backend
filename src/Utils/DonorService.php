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
    /**
     * DonorService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly ExportService $exportService)
    {
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
     * @return bool
     */
    public function delete(Donor $donor)
    {
        try {
            $this->em->remove($donor);
            $this->em->flush();
        } catch (Exception) {
            return false;
        }

        return true;
    }
}
