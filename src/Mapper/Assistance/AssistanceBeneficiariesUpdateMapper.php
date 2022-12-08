<?php

namespace Mapper\Assistance;

use InvalidArgumentException;
use OutputType\Assistance\AssistanceBeneficiaryOperationOutputType;
use Serializer\MapperInterface;

class AssistanceBeneficiariesUpdateMapper implements MapperInterface
{
    private ?\OutputType\Assistance\AssistanceBeneficiaryOperationOutputType $object = null;

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

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . AssistanceBeneficiaryOperationOutputType::class . ', ' . $object::class . ' given.'
        );
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

    public function getAlreadyProcessed(): array
    {
        return $this->object->getAlreadyProcessed();
    }
}
