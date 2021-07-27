<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity\ImportFileViolation;
use NewApiBundle\Entity\ImportFile;
use Symfony\Component\HttpFoundation\File\File;

class ImportFileValidator
{
    private const MANDATORY_COLUMNS = [
        'Adm1',
        'Head',
    ];

    /** @var ImportParser */
    private $importParser;

    /** @var string */
    private $uploadDirectory;

    /** @var ImportTemplate  */
    private $importTemplate;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(string $uploadDirectory, ImportTemplate $importTemplate, EntityManagerInterface $entityManager)
    {
        $this->importParser = new ImportParser();
        $this->importTemplate = $importTemplate;
        $this->uploadDirectory = $uploadDirectory;
        $this->em = $entityManager;
    }

    /**
     * @param ImportFile $importFile
     */
    public function validate(ImportFile $importFile): void
    {
        $file = new File($this->uploadDirectory.'/'.$importFile->getSavedAsFilename());

        try {
            $fileHeaders = $this->importParser->parseHeadersOnly($file);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $violation = new ImportFileViolation('Unable to read provided file. File is malformed or it has unsupported format.');

            $importFile->setStructureViolations(json_encode([$violation]));

            $this->em->persist($importFile);
            $this->em->flush();

            return;
        }

        $templateHeaders = $this->importTemplate->getTemplateHeader($importFile->getImport()->getProject()->getIso3());

        //remove empty cells
        foreach ($templateHeaders as $key => $header) {
            if (strlen(trim($header)) === 0) {
                unset($templateHeaders[$key]);
            }
        }

        $expectedValidColumns = array_intersect($templateHeaders, $fileHeaders);
        $expectedMissingColumns = array_diff($templateHeaders, $fileHeaders);
        $unexpectedColumns = array_diff($fileHeaders, $templateHeaders);

        $importFile->setExpectedValidColumns($expectedValidColumns);
        $importFile->setExpectedMissingColumns($expectedMissingColumns);
        $importFile->setUnexpectedColumns($unexpectedColumns);

        $fileViolations = $this->validateMandatoryColumns($fileHeaders);

        if ( ($addressViolation = $this->validateAddressColumns($fileHeaders)) instanceof ImportFileViolation) {
            $fileViolations[] = $addressViolation;
        }

        if (!empty($fileViolations)) {
            $importFile->setStructureViolations(json_encode($fileViolations));
        }

        $this->em->persist($importFile);
        $this->em->flush();
    }

    private function validateMandatoryColumns(array $fileHeaders): array
    {
        $violations = [];

        foreach (self::MANDATORY_COLUMNS as $mandatoryColumn) {
            if (!in_array($mandatoryColumn, $fileHeaders)) {
                $violations[] = new ImportFileViolation('This column is mandatory.', [$mandatoryColumn]);
            }
        }

        return $violations;
    }

    private function validateAddressColumns(array $fileHeaders): ?ImportFileViolation
    {
        $addressColumns = ['Address street', 'Address number', 'Address postcode'];
        $campColumns = ['Camp name', 'Tent number'];

        $hasAddress = true;
        foreach ($addressColumns as $addressColumn) {
            if (!in_array($addressColumn, $fileHeaders)) {
                $hasAddress = false;
                break;
            }
        }

        $hasCamp = true;
        foreach ($campColumns as $campColumn) {
            if (!in_array($campColumn, $fileHeaders)) {
                $hasCamp = false;
                break;
            }
        }

        if (!($hasCamp || $hasAddress)) {
            return new ImportFileViolation('Missing columns for address or camp', array_merge($addressColumns, $campColumns));
        }

        return null;
    }
}
