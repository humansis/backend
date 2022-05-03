<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\GeneralReliefItem;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\Booklet;

abstract class AbstractAssistanceBeneficiaryMapper implements MapperInterface
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

    public function getGeneralReliefItemIds(): array
    {
        return []; // TODO: remove after PIN-3249
    }

    public function getBookletIds(): array
    {
        return array_map(function (Booklet $booklet) {
            return $booklet->getId();
        }, $this->object->getBooklets()->toArray());
    }

    public function getReliefPackageIds(): array
    {
        return array_map(function (ReliefPackage $reliefPackage) {
            return $reliefPackage->getId();
        }, $this->object->getReliefPackages()->toArray());
    }
}
