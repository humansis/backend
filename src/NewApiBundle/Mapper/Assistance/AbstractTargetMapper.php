<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use NewApiBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Entity\GeneralReliefItem;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\Booklet;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.AssistanceBeneficiary::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getRemoved(): bool
    {
        return (bool) $this->object->getRemoved();
    }

    public function getJustification(): ?string
    {
        return $this->object->getJustification();
    }

    public function getReliefPackageIds(): array
    {
        return array_values(array_map(function (ReliefPackage $package) {
            return $package->getId();
        }, $this->object->getReliefPackages()->toArray()));
    }
}
