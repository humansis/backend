<?php
declare(strict_types=1);

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
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Workflow\WorkflowInterface;

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
        $currentColumn = 1;

        /** @var ImportQueue $entry */
        foreach ($entries as $entry) {
            if ($entry->getState() !== ImportQueueState::INVALID) {
                throw new \InvalidArgumentException("Wrong ImportQueue state for export invalid items: ".$entry->getState());
            }

            $messages = $this->decodeMessages($entry->getMessage());

            foreach ($entry->getContent() as $i => $row) {
                $invalidColumns = $this->parseInvalidColumns($messages, $i);

                foreach ($header as $column) {
                    $cell = $sheet->getCellByColumnAndRow($currentColumn, $currentRow);

                    if (isset($row[$column])) {
                        $cellValue = $row[$column][CellParameters::VALUE];

                        // Formulas with error can't be written as type 'f' => it triggers exception in Spreadsheet library during saving a file
                        if ($row[$column][CellParameters::DATA_TYPE] === DataType::TYPE_FORMULA && array_key_exists(CellParameters::ERRORS,
                                $row[$column])) {
                            $dataType = DataType::TYPE_STRING;
                        } else {
                            $dataType = $row[$column][CellParameters::DATA_TYPE];
                        }

                        $cell->setValueExplicit($cellValue, $dataType);
                        $cell->getStyle()->getNumberFormat()->setFormatCode($row[$column][CellParameters::NUMBER_FORMAT]);
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

                $currentColumn = 1;
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

    public function removeInvalidFiles(Import $import): void
    {
        $fs = new Filesystem();

        foreach ($import->getImportInvalidFiles() as $invalidFile) {
            $fs->remove($this->importInvalidFilesDirectory.'/'.$invalidFile->getFilename());

            $this->em->remove($invalidFile);
        };

        $this->em->flush();
    }
}
