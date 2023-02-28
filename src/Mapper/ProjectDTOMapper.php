<?php

declare(strict_types=1);

namespace Mapper;

use DTO\ProjectDTO;
use Repository\BeneficiaryRepository;
use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\ProjectSector;
use Utils\ProjectService;

class ProjectDTOMapper implements MapperInterface
{
    private ?ProjectDTO $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ProjectDTO && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof ProjectDTO) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ProjectDTO::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getInternalId(): ?string
    {
        return $this->object->getInternalId();
    }

    public function getIso3(): string
    {
        return $this->object->getIso3();
    }

    public function getNotes(): ?string
    {
        return $this->object->getNotes();
    }

    public function getTarget(): int
    {
        return (int) $this->object->getTarget();
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->object->getStartDate();
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->object->getEndDate();
    }

    public function getSectors(): array
    {
        return $this->object->getSectors();
    }

    public function getDonorIds(): array
    {
        return $this->object->getDonorIds();
    }

    public function getNumberOfHouseholds(): int
    {
        return $this->object->getNumberOfHouseholds();
    }

    public function getDeletable(): bool
    {
        return $this->object->isDeletable();
    }

    public function getBeneficiariesReached(): int
    {
        return $this->object->getBeneficiariesReached();
    }

    public function getProjectInvoiceAddressLocal(): ?string
    {
        return $this->object->getProjectInvoiceAddressLocal();
    }

    public function getProjectInvoiceAddressEnglish(): ?string
    {
        return $this->object->getProjectInvoiceAddressEnglish();
    }

    public function getAllowedProductCategoryTypes(): array
    {
        return $this->object->getAllowedProductCategoryTypes();
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->object->getCreatedAt();
    }

    public function getLastModifiedAt(): DateTimeInterface
    {
        return $this->object->getLastModifiedAt();
    }
}
