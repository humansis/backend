<?php

namespace NewApiBundle\Mapper\Assistance;

use NewApiBundle\OutputType\Assistance\AssistanceBeneficiaryOperationOutputType;
use NewApiBundle\Serializer\MapperInterface;

class AssistanceBeneficiariesUpdateMapper implements MapperInterface
{
    /** @var AssistanceBeneficiaryOperationOutputType */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceBeneficiaryOperationOutputType;
    }

    public function populate(object $object)
    {
        if ($object instanceof AssistanceBeneficiaryOperationOutputType) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.AssistanceBeneficiaryOperationOutputType::class.', '.get_class($object).' given.');
    }

    public function getNotFound(): array
    {
        return $this->object->getNotFound();
    }

    public function getSuccess(): array
    {
        return $this->object->getSuccess();
    }

    public function getFailed(): array
    {
        return $this->object->getFailed();
    }

    public function getAlreadyRemoved(): array
    {
        return $this->object->getAlreadyRemoved();
    }
}