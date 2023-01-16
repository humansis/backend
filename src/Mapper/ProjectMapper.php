<?php

declare(strict_types=1);

namespace Mapper;

use Repository\BeneficiaryRepository;
use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Project;
use Entity\ProjectSector;
use Utils\ProjectService;

class ProjectMapper implements MapperInterface
{
    protected ?Project $object = null;

    public function __construct(
        protected readonly ProjectService $projectService,
        protected readonly BeneficiaryRepository $beneficiaryRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof Project &&
            isset($context[self::NEW_API]) &&
            true === $context[self::NEW_API] &&
            false === array_key_exists('detail', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Project) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Project::class . ', ' . $object::class . ' given.'
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
        return $this->object->getCountryIso3();
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
        return array_values(
            array_map(fn(ProjectSector $item) => $item->getSector(), $this->object->getSectors()->toArray())
        );
    }

    public function getDonorIds(): array
    {
        return array_values(
            array_map(fn($item) => $item->getId(), $this->object->getDonors()->toArray())
        );
    }

    public function getNumberOfHouseholds(): int
    {
        return $this->object->getNumberOfHouseholds();
    }

    public function getDeletable(): bool
    {
        return $this->projectService->isDeletable($this->object);
    }

    public function getBeneficiariesReached(): int
    {
        return $this->beneficiaryRepository->countAllInProject($this->object);
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
