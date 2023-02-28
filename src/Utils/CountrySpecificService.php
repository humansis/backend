<?php

namespace Utils;

use Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CountrySpecificService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly ExportService $exportService)
    {
    }

    /**
     * @return bool
     */
    public function delete(CountrySpecific $countrySpecific)
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
