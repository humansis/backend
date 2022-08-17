<?php
declare(strict_types=1);

namespace Component\Import;

use Utils\HouseholdExportCSVService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportTemplate
{
    public const FIRST_ENTRY_ROW = 6;
    public const ROW_NAME_STATUS = 'Humansis data';
    public const ROW_NAME_MESSAGES = 'Humansis comment';
    public const CURRENT_TEMPLATE_VERSION = ImportParser::VERSION_2_SRC;

    /**
     * @var HouseholdExportCSVService
     */
    private $householdExportCSVService;

    public function __construct(HouseholdExportCSVService $householdExportCSVService)
    {
        $this->householdExportCSVService = $householdExportCSVService;
    }

    public function getTemplateHeader(string $iso3): array
    {
        $templateData = $this->householdExportCSVService->getHeaders($iso3);

        return array_keys(current($templateData));
    }
}
