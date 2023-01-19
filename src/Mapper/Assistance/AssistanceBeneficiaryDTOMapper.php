<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use DTO\AssistanceBeneficiaryDTO;
use InvalidArgumentException;
use Serializer\MapperInterface;

class AssistanceBeneficiaryDTOMapper implements MapperInterface
{
    private AssistanceBeneficiaryDTO | null $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceBeneficiaryDTO && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof AssistanceBeneficiaryDTO) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . AssistanceBeneficiaryDTO::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiaryId();
    }

    public function getRemoved(): bool
    {
        return $this->object->isRemoved();
    }

    public function getJustification(): string|null
    {
        return $this->object->getJustification();
    }

    /**
     * @return int[]
     */
    public function getReliefPackageIds(): array
    {
        return $this->object->getReliefPackageIds();
    }
}
