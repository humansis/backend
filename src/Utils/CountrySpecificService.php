<?php

declare(strict_types=1);

namespace Utils;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InputType\CountrySpecificCreateInputType;
use InputType\CountrySpecificUpdateInputType;

class CountrySpecificService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ExportService $exportService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @throws UniqueConstraintViolationException
     */
    public function create(CountrySpecificCreateInputType $inputType): CountrySpecific
    {
        $countrySpecific = new CountrySpecific($inputType->getField(), $inputType->getType(), $inputType->getIso3());

        $this->managerRegistry->getManager()->persist($countrySpecific);
        $this->managerRegistry->getManager()->flush();

        return $countrySpecific;
    }

    /**
     * @throws UniqueConstraintViolationException
     */
    public function update(CountrySpecific $countrySpecific, CountrySpecificUpdateInputType $inputType): CountrySpecific
    {
        $countrySpecific->setFieldString($inputType->getField());
        $countrySpecific->setType($inputType->getType());

        $this->managerRegistry->getManager()->persist($countrySpecific);
        $this->managerRegistry->getManager()->flush();

        return $countrySpecific;
    }

    public function delete(CountrySpecific $countrySpecific): bool
    {
        try {
            $this->em->remove($countrySpecific);
            $this->em->flush();
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Export all the countries specifics in a CSV file
     *
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryIso3)
    {
        $exportableTable = $this->em->getRepository(CountrySpecific::class)->findBy(
            ['countryIso3' => $countryIso3],
            ['id' => 'asc']
        );

        return $this->exportService->export($exportableTable, 'country', $type);
    }
}
