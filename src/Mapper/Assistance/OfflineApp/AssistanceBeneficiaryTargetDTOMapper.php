<?php

declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use DateTimeInterface;
use DTO\AssistanceBeneficiaryTargetDTO;
use InvalidArgumentException;
use Mapper\MapperContextTrait;
use Repository\Assistance\ReliefPackageRepository;
use Repository\BookletRepository;
use Repository\NationalIdRepository;
use Serializer\MapperInterface;

class AssistanceBeneficiaryTargetDTOMapper implements MapperInterface
{
    use MapperContextTrait;

    protected AssistanceBeneficiaryTargetDTO | null $object;

    public function __construct(
        private readonly NationalIdRepository $nationalIdRepository,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly BookletRepository $bookletRepository
    ) {
    }

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceBeneficiaryTargetDTO &&
            $this->isNewApi($context) &&
            $this->isOfflineApp($context) &&
            isset($context['expanded']) &&
            true === $context['expanded'] &&
            isset($context['version']) && $context['version'] === 'v4';
    }

    public function populate(object $object)
    {
        if ($object instanceof AssistanceBeneficiaryTargetDTO) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . AssistanceBeneficiaryTargetDTO::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getBeneficiary(): array
    {
        return [
            'id' => $this->object->getBeneficiaryId(),
            'localFamilyName' => $this->object->getLocalFamilyName(),
            'localGivenName' => $this->object->getLocalGivenName(),
            'referralType' => $this->object->getReferralType(),
            'referralComment' => $this->object->getReferralComment(),
            'nationalCardIds' => $this->nationalIdRepository->getNationalIdsInfoByPersonId($this->object->getPersonId()),
        ];
    }

    public function getDistributedAt(): string | null
    {
        return $this->object->getDistributedAt()?->format(DateTimeInterface::ATOM);
    }

    public function getCurrentSmartcardSerialNumber(): string | null
    {
        return $this->object->getSmartcardSerialNumber();
    }

    public function getReliefPackages(): iterable
    {
        $reliefPackages = $this->reliefPackageRepository->getReliefPackageDTOByIds($this->object->getReliefPackageIds());
        $transformedReliefPackages = [];

        foreach ($reliefPackages as $reliefPackage) {
            $transformedReliefPackages[] = [
                'id' => $reliefPackage->getId(),
                'state' => $reliefPackage->getState(),
                'modalityType' => $reliefPackage->getModalityType(),
                'notes' => $reliefPackage->getNotes(),
                'amountDistributed' => $reliefPackage->getAmountDistributed(),
                'amountToDistribute' => $reliefPackage->getAmountToDistribute(),
                'unit' => $reliefPackage->getUnit(),
                'createdAt' => $reliefPackage->getCreatedAt(),
                'lastModifiedAt' => $reliefPackage->getLastModifiedAt(),
                'distributedAt' => $reliefPackage->getDistributedAt(),
            ];
        }

        return $transformedReliefPackages;
    }

    public function getBooklets(): array
    {
        $booklets = $this->bookletRepository->getBookletDTOByIds($this->object->getBookletIds());
        $transformedBooklets = [];

        foreach ($booklets as $booklet) {
            $transformedBooklets[] = [
                'id' => $booklet->getId(),
                'code' => $booklet->getCode(),
                'currency' => $booklet->getCurrency(),
                'status' => $booklet->getStatus(),
                'voucherValues' => $booklet->getVoucherValues(),
            ];
        }

        return $transformedBooklets;
    }
}
