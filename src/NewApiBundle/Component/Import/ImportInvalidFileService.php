<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportInvalidFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
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
    private const FORMULA_ERROR_CELL_VALUE = 'INVALID VALUE: formulas are not supported, please fill a value';

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

    /**
     * @var WorkflowInterface
     */
    private $importQueueStateMachine;

    public function __construct(
        ImportQueueRepository $importQueueRepository,
        ImportTemplate $importTemplate,
        string $importInvalidFilesDirectory,
        EntityManagerInterface $em,
        WorkflowInterface $importQueueStateMachine
    ) {
        $this->importTemplate = $importTemplate;
        $this->importQueueRepository = $importQueueRepository;
        $this->importInvalidFilesDirectory = $importInvalidFilesDirectory;
        $this->em = $em;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    public function generateFile(Import $import): ImportInvalidFile
    {
        $invalidEntries = $this->importQueueRepository->getInvalidEntries($import);
        $spreadsheet = $this->importTemplate->generateTemplateSpreadsheet($import->getCountryIso3());

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
        return $slugger->slug($import->getTitle()).'-'.$import->getId().'-invalid-entries_'.time().'.xlsx';
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
        $currentRow = ImportTemplate::FIRST_ENTRY_ROW;

        /** @var ImportQueue $entry */
        foreach ($entries as $entry) {
            if ($entry->getState() !== ImportQueueState::INVALID) {
                throw new \InvalidArgumentException("Wrong ImportQueue state for export invalid items: ".$entry->getState());
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
        } catch (\JsonException $e) {
            return [];
        }
    }

    private function parseInvalidColumns(array $messages, $rowNumber): array
    {
        if (!isset($messages[$rowNumber])) {
            return [];
        }

        return array_map(function (array $messages) {
            return $messages['column'];
        }, $messages[$rowNumber]);
    }

    private function parseViolations(array $messages, $rowNumber): array
    {
        if (!isset($messages[$rowNumber])) {
            return [];
        }

        return array_map(function (array $messages) {
            return $messages['column'].": ".$messages['violation'];
        }, $messages[$rowNumber]);
    }

    public function removeInvalidFiles(Import $import): void
    {
        $fs = new Filesystem();

        foreach ($import->getImportInvalidFiles() as $invalidFile) {
            $fs->remove($this->importInvalidFilesDirectory.'/'.$invalidFile->getFilename());

            $this->em->remove($invalidFile);
        };

        $this->em->flush();
    }

    /**
     * @param Worksheet $sheet
     * @param array     $header
     * @param array     $row
     * @param array     $invalidColumns
     * @param int       $currentRow
     * @param array     $validationViolations
     *
     * @throws Exception
     */
    private function writeRow(
        Worksheet $sheet,
        array     $header,
        array     $row,
        array     $invalidColumns,
        int       $currentRow,
        array     $validationViolations
    ): void {
        $errorIsElsewhereInHousehold = empty($validationViolations);
        if ($errorIsElsewhereInHousehold) {
            $validationViolations = ['Member is OK, error is in other beneficiary'];
        }

        $currentColumn = 1;
        foreach ($header as $column) {
            $cell = $sheet->getCellByColumnAndRow($currentColumn, $currentRow);

            if (isset($row[$column])) {
                $cellValue = $row[$column][CellParameters::VALUE];

                // in case of formula error => convert to string and fill cell with default message
                if ($row[$column][CellParameters::DATA_TYPE] === DataType::TYPE_FORMULA && array_key_exists(CellParameters::ERRORS,
                        $row[$column])) {
                    $dataType = DataType::TYPE_STRING;
                    $cellValue = self::FORMULA_ERROR_CELL_VALUE;
                } else {
                    $dataType = $row[$column][CellParameters::DATA_TYPE];
                }

                $cell->setValueExplicit($cellValue, $dataType);
                $cell->getStyle()->getNumberFormat()->setFormatCode($row[$column][CellParameters::NUMBER_FORMAT]);
            }
            if ($column === ImportTemplate::ROW_NAME_STATUS) {
                $cell->setValue($errorIsElsewhereInHousehold ? 'ERROR in Household' : 'ERROR');
            } else if ($column === ImportTemplate::ROW_NAME_MESSAGES) {
                $cell->setValue(implode("\n", $validationViolations));
            }

            if (count($invalidColumns) === 0) {
                $cell->getStyle()
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('CCFF99');
            } else {
                if (in_array($column, $invalidColumns)) {
                    $cell->getStyle()
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('ffff00');
                }
            }

            ++$currentColumn;
        }
    }
}
