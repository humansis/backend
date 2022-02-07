<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Serializer\MapperInterface;

class ImportBeneficiaryDuplicityMapper implements MapperInterface
{
    /** @var ImportBeneficiaryDuplicity */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportBeneficiaryDuplicity && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ImportBeneficiaryDuplicity) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportBeneficiaryDuplicity::class.', '.get_class($object).' given.');
    }

    public function getReasons(): iterable
    {
        return $this->object->getReasons();
    }

    public function getDifferences(): iterable
    {
        return $this->object->getDifferences();
    }

}
