<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

use Component\Country\Countries;
use Entity\ImportQueue;
use InvalidArgumentException;
use Repository\CountrySpecificRepository;
use Repository\LocationRepository;

class ImportLineFactory
{
    public function __construct(
        private readonly Countries $countries,
        private readonly CountrySpecificRepository $countrySpecificRepository,
        private readonly LocationRepository $locationRepository,
    ) {
    }

    public function createFromData(array $data, string $countryIso): ImportLine
    {
        if (!$this->countries->hasCountry($countryIso)) {
            throw new InvalidArgumentException("Country $countryIso doesn't exist");
        }

        return new ImportLine($data, $countryIso, $this->countrySpecificRepository, $this->locationRepository);
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
