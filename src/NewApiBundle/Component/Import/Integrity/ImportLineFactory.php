<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportQueue;

class ImportLineFactory
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string[] */
    private $countries;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string[]               $countries
     */
    public function __construct(EntityManagerInterface $entityManager, array $countries)
    {
        $this->entityManager = $entityManager;
        $this->countries = $countries;
    }

    public function createFromData(array $data, string $countryIso): ImportLine
    {
        // if (!in_array($countryIso, $this->countries)) {
        //     throw new \InvalidArgumentException("Country $countryIso doesn't exist");
        // }
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
            yield $this->createFromData($date, $importQueue->getImport()->getProject()->getIso3());
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
        return $this->createFromData($importQueue->getContent()[$beneficiaryIndex], $importQueue->getImport()->getProject()->getIso3());
    }
}
