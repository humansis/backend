<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\GeneralReliefItem;
use NewApiBundle\Serializer\MapperInterface;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\SmartcardDeposit;

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
        return array_map(function (GeneralReliefItem $generalReliefItem) {
            return $generalReliefItem->getId();
        }, $this->object->getGeneralReliefs()->toArray());
    }

    public function getSmartcardDepositIds(): array
    {
        return array_map(function (SmartcardDeposit $smartcardDeposit) {
            return $smartcardDeposit->getId();
        }, $this->object->getSmartcardDeposits()->toArray());
    }

    public function getBookletIds(): array
    {
        return array_map(function (Booklet $booklet) {
            return $booklet->getId();
        }, $this->object->getBooklets()->toArray());
    }
}
