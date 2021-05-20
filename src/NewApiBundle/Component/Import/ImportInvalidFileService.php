<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportInvalidFileService
{
    /**
     * @var ImportQueueRepository
     */
    private $importQueueRepository;

    /**
     * @var ImportTemplate
     */
    private $importTemplate;

    /**
     * @var string
     */
    private $importInvalidFilesDirectory;

    public function __construct(ImportQueueRepository $importQueueRepository, ImportTemplate $importTemplate, string $importInvalidFilesDirectory)
    {
        $this->importTemplate = $importTemplate;
        $this->importQueueRepository = $importQueueRepository;
        $this->importInvalidFilesDirectory = $importInvalidFilesDirectory;
    }

    public function generateFile(Import $import): void
    {
        $invalidEntries = $this->importQueueRepository->getInvalidEntries($import);
        $spreadsheet = $this->importTemplate->generateTemplateSpreadsheet($import->getProject()->getIso3());

        //TODO id column

        $header = $this->importTemplate->getTemplateHeader($import->getProject()->getIso3());
        $this->writeEntries($spreadsheet, $invalidEntries, $header);

        $path = $this->generateInvalidFilePath($import);

        $this->saveToFile($spreadsheet, $path);
    }

    public function generateInvalidFilePath(Import $import): string
    {
        return $this->importInvalidFilesDirectory.'/'.$import->getTitle().'-'.$import->getId().'-import-invalid-entries.xlsx';
    }

    private function saveToFile(Spreadsheet $spreadsheet, string $path): void
    {
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

        /** @var ImportQueue $entry */
        foreach ($entries as $entry) {
            $currentRow = ImportTemplate::FIRST_ENTRY_ROW;
            $currentColumn = 1;

            foreach ($entry->getContent() as $row) {
                foreach ($header as $column) {
                    if (isset($row[$column])) {
                        $cellValue = $row[$column];
                    } else {
                        $cellValue = '';
                    }

                    $sheet->setCellValueByColumnAndRow($currentColumn, $currentRow, $cellValue);
                    //TODO yellow background if invalid

                    ++$currentColumn;
                }
                $currentColumn = 1;
                ++$currentRow;
            }
        }
    }
}
