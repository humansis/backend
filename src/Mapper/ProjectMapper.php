<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Assistance;
use Entity\Donor;
use Repository\BeneficiaryRepository;
use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Project;
use Entity\ProjectSector;
use Symfony\Contracts\Translation\TranslatorInterface;
use Utils\ProjectService;

class ProjectMapper implements MapperInterface
{
    protected ?Project $object = null;

    public function __construct(private readonly ProjectService $projectService, private readonly BeneficiaryRepository $beneficiaryRepository, private readonly TranslatorInterface $translator)
    {
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

    public function toFullArray(?Project $project): ?array
    {
        if (!$project) {
            return null;
        }
        $bnfCount = $this->beneficiaryRepository->countAllInProject($project);

        return [
            'id' => $project->getId(),
            'iso3' => $project->getCountryIso3(),
            'name' => $project->getName(),
            'notes' => $project->getNotes(),
            'target' => $project->getTarget(),
            'internal_id' => $project->getInternalId(),
            'donors' => $this->toMinimalDonorArrays($project->getDonors()),
            'end_date' => $project->getEndDate()->format('d-m-Y'),
            'start_date' => $project->getStartDate()->format('d-m-Y'),
            'number_of_households' => $project->getNumberOfHouseholds(),
            'sectors' => $this->toSectorArray($project->getSectors()),
            'reached_beneficiaries' => $bnfCount,
            'distributions' => $this->toMinimalAssistanceArrays($project->getDistributions()),
        ];
    }

    public function toFullArrays(array $projects): iterable
    {
        foreach ($projects as $project) {
            yield $this->toFullArray($project);
        }
    }

    public function toMinimalDonorArray(?Donor $donor): ?array
    {
        if (!$donor) {
            return null;
        }

        return [
            'id' => $donor->getId(),
            'fullname' => $donor->getFullname(),
            'shortname' => $donor->getShortname(),
        ];
    }

    public function toMinimalDonorArrays(iterable $donors): iterable
    {
        foreach ($donors as $donor) {
            yield $this->toMinimalDonorArray($donor);
        }
    }

    private function getLabel(string $enumValue): string
    {
        return $this->translator->trans('label_sector_' . $enumValue, [], 'messages', 'en');
    }

    /**
     * @param ProjectSector[] $projectSectors
     *
     * @return string[]
     */
    public function toSectorArray(iterable $projectSectors): iterable
    {
        foreach ($projectSectors as $projectSector) {
            yield [
                'id' => $projectSector->getSector(),
                'name' => $this->getLabel($projectSector->getSector()),
            ];
        }
    }

    public function toMinimalAssistanceArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
        ];
    }

    public function toMinimalAssistanceArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toMinimalAssistanceArray($assistance);
        }
    }
}
