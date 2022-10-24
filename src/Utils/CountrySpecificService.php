<?php

namespace Utils;

use Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CountrySpecificService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ExportService */
    private $exportService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ExportService $exportService
    ) {
        $this->em = $entityManager;
        $this->exportService = $exportService;
    }

    /**
     * @param CountrySpecific $countrySpecific
     * @return bool
     */
    public function delete(CountrySpecific $countrySpecific)
    {
        try {
            $this->em->remove($countrySpecific);
            $this->em->flush();
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Export all the countries specifics in a CSV file
     *
     * @param string $type
     * @param string $countryIso3
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
