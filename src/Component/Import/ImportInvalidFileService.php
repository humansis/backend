<?php

declare(strict_types=1);

namespace Component\Import;

use Component\Import\Enum\ImportCsoEnum;
use InvalidArgumentException;
use JsonException;
use Utils\HouseholdExportCSVService;
use Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\InvalidCell\InvalidCell;
use Entity\Import;
use Entity\ImportInvalidFile;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Repository\ImportQueueRepository;
use Workflow\ImportQueueTransitions;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportInvalidFileService
{
    private const
        MEMBER_ERROR = 'ERROR',
        HOUSEHOLD_ERROR = 'ERROR in Household',
        MEMBER_IS_OK_MESSAGE = 'Beneficiary is OK, but cannot be imported due to errors in another beneficiaries in the same household',
        SUCCESS_COLOR = 'CCFF99',
        WARNING_COLOR = 'ffff00';

    public function __construct(
        private readonly ImportQueueRepository $importQueueRepository,
        private readonly ImportTemplate $importTemplate,
        private readonly string $importInvalidFilesDirectory,
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $importQueueStateMachine,
        private readonly ExportService $exportService,
        private readonly HouseholdExportCSVService $householdExportCSVService
    ) {
    }

    /**
     * @throws Exception
     */
    public function generateFile(Import $import): ImportInvalidFile
    {
        $invalidEntries = $this->importQueueRepository->getInvalidEntries($import);
        $header = $this->householdExportCSVService->getHeaders($import->getCountryIso3());
        $spreadsheet = $this->exportService->generateSpreadsheet($header);

        $header = $this->importTemplate->getTemplateHeader($import->getCountryIso3());
        $this->writeEntries($spreadsheet, $invalidEntries, $header);

        $fileName = $this->generateInvalidFileName($import);
        $this->saveToFile($spreadsheet, $fileName);

        $importInvalidFile = new ImportInvalidFile();
        $importInvalidFile->setFilename($fileName);
        $importInvalidFile->setImport($import);
        $importInvalidFile->setInvalidQueueCount(count($invalidEntries));

        $this->em->persist($importInvalidFile);
        $this->em->flush();

        return $importInvalidFile;
    }

    private function generateInvalidFileName(Import $import): string
    {
        $slugger = new AsciiSlugger();

        return $slugger->slug($import->getTitle()) . '-' . $import->getId() . '-invalid-entries_' . time() . '.xlsx';
    }

    private function saveToFile(Spreadsheet $spreadsheet, string $name): void
    {
        $path = $this->importInvalidFilesDirectory . '/' . $name;

        if (!is_dir($this->importInvalidFilesDirectory)) {
            mkdir($this->importInvalidFilesDirectory, 0775, true);
        }

        $writer = new Xlsx($spreadsheet);

        if (file_exists($path)) {
            unlink($path);
        }

        $writer->save($path);
    }

    private function writeEntries(Spreadsheet $template, array $entries, array $header)
    {
        $sheet = $template->getActiveSheet();
        $currentRow = ImportTemplate::FIRST_ENTRY_ROW;

        /** @var ImportQueue $entry */
        foreach ($entries as $entry) {
            if ($entry->getState() !== ImportQueueState::INVALID) {
                throw new InvalidArgumentException(
                    "Wrong ImportQueue state for export invalid items: " . $entry->getState()
                );
            }

            $messages = $this->decodeMessages($entry->getMessage());

            foreach ($entry->getContent() as $i => $row) {
                $invalidColumns = $this->parseInvalidColumns($messages, $i);
                $violations = $this->parseViolations($messages, $i);

                $this->writeRow($sheet, $header, $row, $invalidColumns, $currentRow, $violations);
                ++$currentRow;
            }

            $this->importQueueStateMachine->apply($entry, ImportQueueTransitions::INVALIDATE_EXPORT);
        }
        $this->em->flush();
    }

    private function decodeMessages(?string $messageJson): array
    {
        try {
            //depth=512 is default value
            return json_decode($messageJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    private function parseInvalidColumns(array $messages, $rowNumber): array
    {
        if (!isset($messages[$rowNumber])) {
            return [];
        }

        return array_map(fn(array $messages) => $messages['column'], $messages[$rowNumber]);
    }

    private function parseViolations(array $messages, $rowNumber): array
    {
        if (!isset($messages[$rowNumber])) {
            return [];
        }

        return array_map(
            fn(array $messages) => $messages['column'] . ": " . $messages['violation'],
            $messages[$rowNumber]
        );
    }

    public function removeInvalidFiles(Import $import): void
    {
        $fs = new Filesystem();

        foreach ($import->getImportInvalidFiles() as $invalidFile) {
            $fs->remove($this->importInvalidFilesDirectory . '/' . $invalidFile->getFilename());

            $this->em->remove($invalidFile);
        }

        $this->em->flush();
    }

    /**
     *
     * @throws Exception
     */
    private function writeRow(
        Worksheet $sheet,
        array $header,
        array $row,
        array $invalidColumns,
        int $currentRow,
        array $validationViolations
    ): void {
        $errorIsElsewhereInHousehold = empty($validationViolations);
        if ($errorIsElsewhereInHousehold) {
            $validationViolations = [self::MEMBER_IS_OK_MESSAGE];
        }

        $currentColumn = 1;
        foreach ($header as $column) {
            $cell = $sheet->getCellByColumnAndRow($currentColumn, $currentRow);

            if (isset($row[$column])) {
                $cellValue = $row[$column][CellParameters::VALUE];
                $dataType = $row[$column][CellParameters::DATA_TYPE];
                $cellErrors = array_key_exists(
                    CellParameters::ERRORS,
                    $row[$column]
                ) ? $row[$column][CellParameters::ERRORS] : null;
                $invalidCell = new InvalidCell($column, $cellValue, $dataType, $cellErrors);
                $cell->setValueExplicit($invalidCell->getCellValue(), $invalidCell->getCellDataType());
                $cell->getStyle()->getNumberFormat()->setFormatCode($row[$column][CellParameters::NUMBER_FORMAT]);
            }
            if ($column === ImportTemplate::ROW_NAME_STATUS) {
                $cell->setValue($errorIsElsewhereInHousehold ? self::HOUSEHOLD_ERROR : self::MEMBER_ERROR);
            } else {
                if ($column === ImportTemplate::ROW_NAME_MESSAGES) {
                    $cell->setValue(implode("\n", $validationViolations));
                }
            }

            if (count($invalidColumns) === 0) {
                $cell->getStyle()
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB(self::SUCCESS_COLOR);
            } else {
                if ($this->hasColumnWarning($column, $invalidColumns)) {
                    $cell->getStyle()
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB(self::WARNING_COLOR);
                }
            }

            ++$currentColumn;
        }
    }

    private function hasColumnWarning(string $column, array $invalidColumns): bool
    {
        return in_array($column, $invalidColumns) || in_array(
            ImportCsoEnum::getCsoColumnMapping($column),
            $invalidColumns
        );
    }
}
