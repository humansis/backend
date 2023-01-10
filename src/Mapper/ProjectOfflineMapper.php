<?php

declare(strict_types=1);

namespace Mapper;

use DateTimeInterface;
use InvalidArgumentException;
use Repository\BeneficiaryRepository;
use Serializer\MapperInterface;
use Entity\Project;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectOfflineMapper implements MapperInterface
{
    use MapperContextTrait;

    private ?\Entity\Project $object = null;

    public function __construct(private readonly BeneficiaryRepository $beneficiaryRepository, private readonly TranslatorInterface $translator)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Project && $this->isOfflineApp($context);
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
        $sectors = array();
        foreach ($this->object->getSectors() as $item) {
            $sectors[] = [
                'id' => $item->getId(),
                'name' => $this->translator->trans('label_sector_' . $item->getSector(), [], 'messages', 'en')
            ];
        }
        return $sectors;
    }

    public function getBeneficiariesReached(): int
    {
        return $this->beneficiaryRepository->countAllInProject($this->object);
    }

    public function getDonorIds(): array
    {
        $donors = array();
        foreach ($this->object->getDonors() as $item) {
            $donors[] = [
                'id' => $item->getId(),
                'fullname' => $item->getFullname(),
                'shortname' => $item->getShortname()
            ];
        }
        return $donors;
    }

    public function getNumberOfHouseholds(): int
    {
        return $this->object->getNumberOfHouseholds();
    }

    public function getDistributions(): array
    {
        $distributions = array();
        foreach ($this->object->getDistributions() as $item) {
            $distributions[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
            ];
        }
        return $distributions;
    }
}
