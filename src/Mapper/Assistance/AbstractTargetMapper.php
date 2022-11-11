<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\AssistanceBeneficiary;
use Entity\GeneralReliefItem;
use Entity\Assistance\ReliefPackage;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Booklet;

abstract class AbstractTargetMapper implements MapperInterface
{
    /** @var AssistanceBeneficiary */
    protected $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceBeneficiary && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof AssistanceBeneficiary) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . AssistanceBeneficiary::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getRemoved(): bool
    {
        return (bool) $this->object->getRemoved();
    }

    public function getJustification(): string|null
    {
        return $this->object->getJustification();
    }

    public function getReliefPackageIds(): array
    {
        return array_values(
            array_map(fn(ReliefPackage $package) => $package->getId(), $this->object->getReliefPackages()->toArray())
        );
    }
}
