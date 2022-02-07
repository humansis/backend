<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Serializer\MapperInterface;

class ImportHouseholdDuplicityMapper implements MapperInterface
{
    /** @var ImportHouseholdDuplicity */
    private $object;

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

    public function getMemberDuplicities(): iterable
    {
        foreach ($this->object->getBeneficiaryDuplicities() as $duplicity) {
            yield $duplicity->getBeneficiary()->getId() => $duplicity;
        }
    }

    public function getRemovedBeneficiaries(): iterable
    {
        foreach ($this->object->getTheirs()->getBeneficiaries() as $missingBeneficiary) {
            yield $missingBeneficiary->getId();
        }
    }

    public function getAddedBeneficiaries(): iterable
    {
        foreach ($this->object->getOurs()->getContent() as $index => $beneficiaryData) {
            yield $index => $beneficiaryData['Local given name'][CellParameters::VALUE].' '.$beneficiaryData['Local family name'][CellParameters::VALUE];
        }
    }

}
