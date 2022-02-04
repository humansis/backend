<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Serializer\MapperInterface;

class ImportBeneficiaryDuplicityMapper implements MapperInterface
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

    public function getReasons(): array
    {
        return $this->object->getReasons();
    }

}
