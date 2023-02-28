<?php

declare(strict_types=1);

namespace Component\Import;

use Utils\HouseholdExportCSVService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportTemplate
{
    final public const FIRST_ENTRY_ROW = 6;
    final public const ROW_NAME_STATUS = 'Humansis data';
    final public const ROW_NAME_MESSAGES = 'Humansis comment';
    final public const CURRENT_TEMPLATE_VERSION = ImportParser::VERSION_2_SRC;

    public function __construct(private readonly HouseholdExportCSVService $householdExportCSVService)
    {
    }

    public function getTemplateHeader(string $iso3): array
    {
        $templateData = $this->householdExportCSVService->getHeaders($iso3);

        return array_keys(current($templateData));
    }
}
