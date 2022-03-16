<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Import\Duplicity;

use NewApiBundle\Component\Import\Integrity\ImportLineFactory;
use NewApiBundle\Component\Import\ValueObject\HouseholdCompare;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Serializer\MapperInterface;

class HouseholdDuplicityMapper implements MapperInterface
{
    /** @var ImportHouseholdDuplicity */
    private $object;
    /** @var ImportLineFactory */
    private $importLineFactory;

    /**
     * @param ImportLineFactory                $importLineFactory
     */
    public function __construct(ImportLineFactory $importLineFactory)
    {
        $this->importLineFactory = $importLineFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportHouseholdDuplicity && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ImportHouseholdDuplicity) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportHouseholdDuplicity::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getItemId(): int
    {
        return $this->object->getOurs()->getId();
    }

    public function getDuplicityCandidateId(): int
    {
        return $this->object->getTheirs()->getId();
    }

    public function getState(): string
    {
        return $this->object->getState();
    }

    public function getDifferences(): HouseholdCompare
    {
        return new HouseholdCompare(
            $this->importLineFactory->create($this->object->getOurs(), 0),
            $this->object->getTheirs()
        );
    }

    public function getMemberDuplicities(): iterable
    {
        foreach ($this->object->getBeneficiaryDuplicities() as $duplicity) {
            yield $duplicity->getBeneficiary()->getId() => $duplicity;
        }
    }

    public function getRemovedBeneficiaries(): iterable
    {
        foreach ($this->object->getTheirs()->getBeneficiaries() as $missingBeneficiary) {
            yield [
                'id' => $missingBeneficiary->getId(),
                'name' => sprintf('%s %s',
                    $missingBeneficiary->getPerson()->getLocalGivenName() ?: $missingBeneficiary->getPerson()->getEnGivenName(),
                    $missingBeneficiary->getPerson()->getLocalFamilyName() ?: $missingBeneficiary->getPerson()->getEnFamilyName()
                ),
            ];
        }
    }

    public function getAddedBeneficiaries(): iterable
    {
        $importLines = $this->importLineFactory->createAll($this->object->getOurs());
        foreach($importLines as $index => $importLine){
            yield $index => $importLine->localGivenName.' '.$importLine->localFamilyName;
        }
    }

}
