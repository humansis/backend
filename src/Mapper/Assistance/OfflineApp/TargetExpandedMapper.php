<?php

declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use DateTimeInterface;
use Entity\Beneficiary;
use Entity\AssistanceBeneficiary;
use Entity\GeneralReliefItem;
use InvalidArgumentException;
use Mapper\MapperContextTrait;
use Serializer\MapperInterface;
use Entity\Voucher;

class TargetExpandedMapper implements MapperInterface
{
    use MapperContextTrait;

    /** @var AssistanceBeneficiary */
    protected $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceBeneficiary &&
            $this->isNewApi($context) &&
            $object->getBeneficiary() instanceof Beneficiary &&
            $this->isOfflineApp($context) &&
            isset($context['expanded']) &&
            true === $context['expanded'];
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getBeneficiary(): array
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->object->getBeneficiary();

        $beneficiaryMapped = [];
        $beneficiaryMapped['id'] = $beneficiary->getId();
        $beneficiaryMapped['localFamilyName'] = $beneficiary->getPerson()->getLocalFamilyName();
        $beneficiaryMapped['localGivenName'] = $beneficiary->getPerson()->getLocalGivenName();

        $beneficiaryMapped['referralType'] = $beneficiary->getPerson()->getReferral() ? $beneficiary->getPerson(
        )->getReferral()->getType() : null;
        $beneficiaryMapped['referralComment'] = $beneficiary->getPerson()->getReferral() ? $beneficiary->getPerson(
        )->getReferral()->getComment() : null;

        $beneficiaryMapped['nationalCardId'] = $this->getNationalId();

        return $beneficiaryMapped;
    }

    private function getNationalId(): ?string
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->object->getBeneficiary();

        foreach ($beneficiary->getPerson()->getNationalIds() as $nationalId) {
            return $nationalId->getIdNumber();
        }

        return null;
    }

    public function getDistributedAt(): ?string
    {
        return $this->object->getSmartcardDistributedAt() ? $this->object->getSmartcardDistributedAt()->format(
            DateTimeInterface::ISO8601
        ) : null;
    }

    public function getCurrentSmartcardSerialNumber(): ?string
    {
        return $this->object->getBeneficiary()->getSmartcardSerialNumber();
    }

    public function getReliefPackages(): iterable
    {
        return $this->object->getReliefPackages();
    }

    public function getBooklets(): array
    {
        $booklets = [];

        foreach ($this->object->getBooklets() as $booklet) {
            $booklets[] = [
                'id' => $booklet->getId(),
                'code' => $booklet->getCode(),
                'currency' => $booklet->getCurrency(),
                'status' => $booklet->getStatus(),
                'voucherValues' => $booklet->getVouchers()->map(function (Voucher $voucher) {
                    return $voucher->getValue();
                }),
            ];
        }

        return $booklets;
    }

    public function populate(object $object)
    {
        if ($object instanceof AssistanceBeneficiary) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . AssistanceBeneficiary::class . ', ' . get_class(
                $object
            ) . ' given.'
        );
    }
}
