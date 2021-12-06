<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportTemplate
{
    public const FIRST_ENTRY_ROW = 6;

    /**
     * @var HouseholdExportCSVService
     */
    private $householdExportCSVService;

    public function __construct(HouseholdExportCSVService $householdExportCSVService)
    {
        $this->householdExportCSVService = $householdExportCSVService;
    }

    public function generateTemplateSpreadsheet(string $iso3): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $templateData = $this->householdExportCSVService->getHeaders($iso3);

        $headers = array_keys(current($templateData));
        array_unshift($templateData, $headers);

        $spreadsheet->getActiveSheet()->fromArray($templateData);

        return $spreadsheet;
    }

    public function getTemplateHeader(string $iso3): array
    {
        $templateData = $this->householdExportCSVService->getHeaders($iso3);

        return array_keys(current($templateData));
    }
}
