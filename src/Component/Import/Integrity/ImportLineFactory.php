<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

use Doctrine\ORM\EntityManagerInterface;
use Component\Country\Countries;
use Entity\ImportQueue;
use InvalidArgumentException;

class ImportLineFactory
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly Countries $countries)
    {
    }

    public function createFromData(array $data, string $countryIso): ImportLine
    {
        if (!$this->countries->hasCountry($countryIso)) {
            throw new InvalidArgumentException("Country $countryIso doesn't exist");
        }

        return new ImportLine($data, $countryIso, $this->entityManager);
    }

    /**
     * @return ImportLine[]
     */
    public function createAll(ImportQueue $importQueue): iterable
    {
        foreach ($importQueue->getContent() as $date) {
            yield $this->createFromData($date, $importQueue->getImport()->getCountryIso3());
        }
    }

    public function create(ImportQueue $importQueue, int $beneficiaryIndex): ImportLine
    {
        return $this->createFromData(
            $importQueue->getContent()[$beneficiaryIndex],
            $importQueue->getImport()->getCountryIso3()
        );
    }
}
