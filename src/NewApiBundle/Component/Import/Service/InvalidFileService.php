<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Service;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\InvalidFile;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Workflow\WorkflowInterface;

class InvalidFileService
{
    /**
     * @var QueueRepository
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
        QueueRepository $importQueueRepository,
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

    public function generateFile(Import $import): InvalidFile
    {
        $invalidEntries = $this->importQueueRepository->getInvalidEntries($import);
        $spreadsheet = $this->importTemplate->generateTemplateSpreadsheet($import->getProject()->getIso3());

        $header = $this->importTemplate->getTemplateHeader($import->getProject()->getIso3());
        $this->writeEntries($spreadsheet, $invalidEntries, $header);

        $fileName = $this->generateInvalidFileName($import);
        $this->saveToFile($spreadsheet, $fileName);

        $importInvalidFile = new InvalidFile();
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

        /** @var Queue $entry */
        foreach ($entries as $entry) {

            foreach ($entry->getContent() as $i => $row) {
                //TODO parse json only once
                $invalidColumns = $this->parseInvalidColumns($entry->getMessage(), $i);

                foreach ($header as $column) {
                    $cell = $sheet->getCellByColumnAndRow($currentColumn, $currentRow);

                    if (isset($row[$column])) {
                        $cellValue = $row[$column][CellParameters::VALUE];

                        $cell->setValueExplicit($cellValue, $row[$column][CellParameters::DATA_TYPE]);
                        $cell->getStyle()->getNumberFormat()->setFormatCode($row[$column][CellParameters::NUMBER_FORMAT]);
                    }

                    if (in_array($column, $invalidColumns)) {
                        $cell->getStyle()
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

            WorkflowTool::checkAndApply($this->importQueueStateMachine, $entry, [QueueTransitions::INVALIDATE_EXPORT]);
        }
        $this->em->flush();
    }

    private function parseInvalidColumns(?string $messageJson, $rowNumber): array
    {
        try {
            //dept=512 is default value
            $messages = json_decode($messageJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }

        return array_map(function (array $messages) {
            return $messages['column'];
        }, $messages[$rowNumber]);
    }

    public function removeInvalidFiles(Import $import): void
    {
        $fs = new Filesystem();

        foreach ($import->getInvalidFiles() as $invalidFile) {
            $fs->remove($this->importInvalidFilesDirectory.'/'.$invalidFile->getFilename());

            $this->em->remove($invalidFile);
        };

        $this->em->flush();
    }
}
