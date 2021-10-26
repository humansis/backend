<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\AssistanceService;
use NewApiBundle\Serializer\MapperInterface;

class AssistanceMapper implements MapperInterface
{
    /** @var Assistance */
    private $object;

    /** @var AssistanceService */
    private $service;

    public function __construct(AssistanceService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Assistance && isset($context[self::NEW_API]) && true === $context[self::NEW_API] && !isset($context['offline-app']);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Assistance) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Assistance::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getDateDistribution(): string
    {
        return $this->object->getDateDistribution()->format(\DateTime::ISO8601);
    }

    public function getDateExpiration(): ?string
    {
        return $this->object->getDateExpiration() ? $this->object->getDateExpiration()->format(\DateTimeInterface::ISO8601) : null;
    }

    public function getProjectId(): int
    {
        return $this->object->getProject()->getId();
    }

    public function getTarget(): string
    {
        return $this->object->getTargetType();
    }

    public function getType(): string
    {
        return $this->object->getAssistanceType();
    }

    public function getLocationId(): int
    {
        return $this->object->getLocation()->getId();
    }

    public function getAdm1Id(): ?int
    {
        return $this->object->getLocation()->getAdm1Id() ?: null;
    }

    public function getAdm2Id(): ?int
    {
        return $this->object->getLocation()->getAdm2Id() ?: null;
    }

    public function getAdm3Id(): ?int
    {
        return $this->object->getLocation()->getAdm3Id() ?: null;
    }

    public function getAdm4Id(): ?int
    {
        return $this->object->getLocation()->getAdm4Id() ?: null;
    }

    public function getSector(): string
    {
        return $this->object->getSector();
    }

    public function getSubsector(): ?string
    {
        return $this->object->getSubSector();
    }

    public function getCommodityIds(): array
    {
        $result = [];
        foreach ($this->object->getCommodities() as $commodity) {
            if ('Activity item' === $commodity->getModalityType()->getName()) {
                continue;
            }

            $result[] = $commodity->getId();
        }

        return $result;
    }

    public function getDescription(): ?string
    {
        return $this->object->getDescription();
    }

    public function getHouseholdsTargeted(): ?int
    {
        return $this->object->getHouseholdsTargeted();
    }

    public function getIndividualsTargeted(): ?int
    {
        return $this->object->getIndividualsTargeted();
    }

    public function getValidated(): bool
    {
        return (bool) $this->object->getValidated();
    }

    public function getCompleted(): bool
    {
        return (bool) $this->object->getCompleted();
    }

    public function getDistributionStarted(): bool
    {
        return $this->service->hasDistributionStarted($this->object);
    }

    public function getDeletable(): bool
    {
        return !$this->object->getValidated();
    }

    public function getSelectionId(): int
    {
        return $this->object->getAssistanceSelection()->getId();
    }

    public function getRemoteDistributionAllowed(): ?bool
    {
        return $this->object->isRemoteDistributionAllowed();
    }

    public function getFoodLimit(): ?float
    {
        return $this->object->getFoodLimit();
    }

    public function getNonFoodLimit(): ?float
    {
        return $this->object->getNonFoodLimit();
    }

    public function getCashbackLimit(): ?float
    {
        return $this->object->getCashbackLimit();
    }
}
