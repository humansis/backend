<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportInvalidFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(ImportQueueRepository $importQueueRepository, ImportTemplate $importTemplate, string $importInvalidFilesDirectory, EntityManagerInterface $em)
    {
        $this->importTemplate = $importTemplate;
        $this->importQueueRepository = $importQueueRepository;
        $this->importInvalidFilesDirectory = $importInvalidFilesDirectory;
        $this->em = $em;
    }

    public function generateFile(Import $import): ImportInvalidFile
    {
        $invalidEntries = $this->importQueueRepository->getInvalidEntries($import);
        $spreadsheet = $this->importTemplate->generateTemplateSpreadsheet($import->getProject()->getIso3());

        $header = $this->importTemplate->getTemplateHeader($import->getProject()->getIso3());
        $this->writeEntries($spreadsheet, $invalidEntries, $header);

        $fileName = $this->generateInvalidFileName($import);
        $this->saveToFile($spreadsheet, $fileName);

        $importInvalidFile = new ImportInvalidFile();
        $importInvalidFile->setFilename($fileName);
        $importInvalidFile->setImport($import);

        $this->em->persist($importInvalidFile);
        $this->em->flush();

        return $importInvalidFile;
    }

    private function generateInvalidFileName(Import $import): string
    {
        return $import->getTitle().'-'.$import->getId().'-invalid-entries_'.time().'.xlsx';
    }

    private function saveToFile(Spreadsheet $spreadsheet, string $name): void
    {
        $path = $this->importInvalidFilesDirectory.'/' . $name;

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

            $invalidColumns = $this->parseInvalidColumns($entry->getMessage());

            foreach ($entry->getContent() as $row) {

                foreach ($header as $column) {
                    if (isset($row[$column])) {
                        $cellValue = $row[$column];
                    } else {
                        $cellValue = '';
                    }

                    $sheet->setCellValueByColumnAndRow($currentColumn, $currentRow, $cellValue);

                    if (in_array($column, $invalidColumns)) {
                        $sheet->getStyleByColumnAndRow($currentColumn, $currentRow)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('ffff00');
                    }

                    ++$currentColumn;
                }
                $currentColumn = 1;
                ++$currentRow;
            }

            //$this->em->remove($entry);
        }
    }

    private function parseInvalidColumns(?string $messageJson): array
    {
        try {
            //dept=512 is default value
            $messages = json_decode($messageJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }

        return array_map(function (array $messages) {
            return $messages['column'];
        }, $messages);
    }
}
