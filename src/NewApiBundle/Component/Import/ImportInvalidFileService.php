<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use InvalidArgumentException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportInvalidFileService
{
    private const FIRST_ENTRY_ROW = 6;

    /**
     * @var integer
     */
    private $currentRow = 1;

    /**
     * @var integer
     */
    private $currentColumn = 1;

    /**
     * @var ImportQueueRepository
     */
    private $importQueueRepository;

    public function __construct(ImportQueueRepository $importQueueRepository)
    {
        $this->importQueueRepository = $importQueueRepository;
    }

    public function generateFile(Import $import): string
    {
        $invalidEntries = $this->importQueueRepository->getInvalidEntries($import);

        if (empty($invalidEntries)) {
            throw new InvalidArgumentException('There are no invalid entries in this import.');
        }

        //TODO id column
        //TODO write header information

        $spreadsheet = new Spreadsheet();

        $header = $this->getHeader(current($invalidEntries));
        $this->writeHeader($spreadsheet, $header);

        $this->currentRow = self::FIRST_ENTRY_ROW;

        foreach ($invalidEntries as $invalidEntry) {
            $this->writeEntry($spreadsheet, $invalidEntry, $header);
        }

        return $this->saveToFile($spreadsheet);
    }

    private function saveToFile(Spreadsheet $spreadsheet): string
    {
        $writer = new Xlsx($spreadsheet);

        $tempPath = tempnam(sys_get_temp_dir(), 'importInvalidFile');
        $writer->save($tempPath);

        return $tempPath;
    }

    private function getHeader(ImportQueue $importQueue): array
    {
        return array_keys(current($importQueue->getContent()));
    }

    private function writeHeader(Spreadsheet $spreadsheet, array $header)
    {
        $spreadsheet->getActiveSheet()->fromArray($header);
    }

    private function writeEntry(Spreadsheet $spreadsheet, ImportQueue $entry, array $header)
    {
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($entry->getContent() as $row) {
            foreach ($header as $headerValue) {
                $cell = $row[$headerValue];

                $sheet->setCellValueByColumnAndRow($this->currentColumn, $this->currentRow, $cell);
                //TODO yellow background if invalid

                ++$this->currentColumn;
            }
            $this->currentColumn = 1;
            ++$this->currentRow;
        }
    }
}
