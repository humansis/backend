<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Entity\ImportQueue;

class ImportLineFactory
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Countries */
    private $countries;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Countries              $countries
     */
    public function __construct(EntityManagerInterface $entityManager, Countries $countries)
    {
        $this->entityManager = $entityManager;
        $this->countries = $countries;
    }

    public function createFromData(array $data, string $countryIso): ImportLine
    {
        if (!$this->countries->hasCountry($countryIso)) {
            throw new \InvalidArgumentException("Country $countryIso doesn't exist");
        }
        return new ImportLine($data, $countryIso, $this->entityManager);
    }

    /**
     * @param ImportQueue $importQueue
     *
     * @return ImportLine[]
     */
    public function createAll(ImportQueue $importQueue): iterable
    {
        foreach ($importQueue->getContent() as $date) {
            yield $this->createFromData($date, $importQueue->getImport()->getCountryIso3());
        }
    }

    /**
     * @param ImportQueue $importQueue
     * @param int         $beneficiaryIndex
     *
     * @return ImportLine
     */
    public function create(ImportQueue $importQueue, int $beneficiaryIndex): ImportLine
    {
        return $this->createFromData($importQueue->getContent()[$beneficiaryIndex], $importQueue->getImport()->getCountryIso3());
    }
}
