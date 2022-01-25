<?php

declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DateTimeInterface;
use InvalidArgumentException;
use NewApiBundle\Serializer\MapperInterface;
use ProjectBundle\Entity\Project;
use ProjectBundle\Entity\ProjectSector;
use ProjectBundle\Utils\ProjectService;

class ProjectMapper implements MapperInterface
{
    /** @var Project */
    private $object;

    /** @var ProjectService */
    private $projectService;

    /** @var BeneficiaryRepository */
    private $beneficiaryRepository;

    public function __construct(ProjectService $projectService, BeneficiaryRepository $beneficiaryRepository)
    {
        $this->projectService = $projectService;
        $this->beneficiaryRepository = $beneficiaryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Project && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
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

        throw new InvalidArgumentException('Invalid argument. It should be instance of '.Project::class.', '.get_class($object).' given.');
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
        return array_map(function (ProjectSector $item) {
            return $item->getSector();
        }, $this->object->getSectors()->toArray());
    }

    public function getDonorIds(): array
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $this->object->getDonors()->toArray());
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
