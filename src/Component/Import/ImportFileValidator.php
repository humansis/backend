<?php

declare(strict_types=1);

namespace Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use Component\Import\Integrity\ImportFileViolation;
use Entity\ImportFile;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\HttpFoundation\File\File;

class ImportFileValidator
{
    private const MANDATORY_COLUMNS = [
        'Adm1',
        'Head',
    ];

    public function __construct(
        private readonly string $uploadDirectory,
        private readonly ImportTemplate $importTemplate,
        private readonly ImportParser $importParser,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function validate(ImportFile $importFile): void
    {
        $file = new File($this->uploadDirectory . '/' . $importFile->getSavedAsFilename());

        try {
            $fileHeaders = $this->importParser->parseHeadersOnly($file);
        } catch (Exception) {
            $violation = new ImportFileViolation(
                'Unable to read provided file. File is malformed or it has unsupported format.'
            );

            $importFile->setStructureViolations([$violation]);

            $this->em->persist($importFile);
            $this->em->flush();

            return;
        }

        $templateHeaders = $this->importTemplate->getTemplateHeader($importFile->getImport()->getCountryIso3());

        //remove empty cells
        foreach ($templateHeaders as $key => $header) {
            if (strlen(trim((string) $header)) === 0) {
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

        if (($addressViolation = $this->validateAddressColumns($fileHeaders)) instanceof ImportFileViolation) {
            $fileViolations[] = $addressViolation;
        }

        if (!empty($fileViolations)) {
            $importFile->setStructureViolations($fileViolations);
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
            return new ImportFileViolation(
                'Missing columns for address or camp',
                array_merge($addressColumns, $campColumns)
            );
        }

        return null;
    }
}
