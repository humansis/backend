<?php

declare(strict_types=1);

namespace Mapper;

use DateTimeInterface;
use DTO\SmartcardPurchasedItemDTO;
use Entity\Location;
use InvalidArgumentException;
use Repository\LocationRepository;
use Serializer\MapperInterface;

class SmartPurchasedItemDTOMapper implements MapperInterface
{
    private SmartcardPurchasedItemDTO | null $object = null;

    private Location $location;

    public function __construct(
        private readonly LocationRepository $locationRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof SmartcardPurchasedItemDTO && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof SmartcardPurchasedItemDTO) {
            $this->object = $object;
            $this->location = $this->locationRepository->getLocationByIdAndCountryCode(
                $object->getLocationId(),
                $object->getCountryIso3()
            );

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . SmartcardPurchasedItemDTO::class . ', ' . $object::class . ' given.'
        );
    }

    public function getHouseholdId(): int
    {
        return $this->object->getHouseholdId();
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiaryId();
    }

    public function getProjectId(): int
    {
        return $this->object->getProjectId();
    }

    public function getAssistanceId(): int
    {
        return $this->object->getAssistanceId();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocationId();
    }

    public function getFullLocationNames(): string
    {
        return $this->location->getFullPathNames();
    }

    public function getAdm1Id(): ?int
    {
        return $this->location->getAdm1Id();
    }

    public function getAdm2Id(): ?int
    {
        return $this->location->getAdm2Id();
    }

    public function getAdm3Id(): ?int
    {
        return $this->location->getAdm3Id();
    }

    public function getAdm4Id(): ?int
    {
        return $this->location->getAdm4Id();
    }

    public function getDatePurchase(): string
    {
        return $this->object->getDatePurchase()->format(DateTimeInterface::ATOM);
    }

    public function getSmartcardCode(): string
    {
        return $this->object->getSmartcardCode();
    }

    public function getProductId(): int
    {
        return $this->object->getProductId();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getValue(): string
    {
        return $this->object->getValue();
    }

    public function getCurrency(): ?string
    {
        return $this->object->getCurrency();
    }

    public function getVendorId(): int
    {
        return $this->object->getVendorId();
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->object->getInvoiceNumber();
    }

    public function getContractNumber(): ?string
    {
        return $this->object->getContractNumber();
    }

    public function getIdNumber(): ?string
    {
        return $this->object->getIdNumber();
    }
}
