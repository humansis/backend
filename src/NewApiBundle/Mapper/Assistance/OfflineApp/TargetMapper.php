<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance\OfflineApp;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Mapper\Assistance\AbstractTargetMapper;
use NewApiBundle\Mapper\MapperContextTrait;
use VoucherBundle\Entity\Booklet;

class TargetMapper extends AbstractTargetMapper
{
    use MapperContextTrait;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) &&
            $object->getBeneficiary() instanceof Beneficiary &&
            $this->isOfflineApp($context);
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getLastSmartcardDepositId(): ?int
    {
        return $this->object->getSmartcardDeposits()->last() ? $this->object->getSmartcardDeposits()->last()->getId() : null;
    }

    public function getBookletIds(): array
    {
        return array_map(function (Booklet $booklet) {
            return $booklet->getId();
        }, $this->object->getBooklets()->toArray());
    }
}
