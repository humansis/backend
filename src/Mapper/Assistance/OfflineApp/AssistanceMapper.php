<?php
declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use Entity\Assistance;
use Repository\AssistanceStatisticsRepository;
use Serializer\MapperInterface;

class AssistanceMapper implements MapperInterface
{
    /** @var Assistance */
    private $object;

    /** @var AssistanceStatisticsRepository */
    private $statsRepository;

    public function __construct(AssistanceStatisticsRepository $statisticsRepository)
    {
        $this->statsRepository = $statisticsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Assistance && isset($context['offline-app']) && true === $context['offline-app'];
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
        return $this->object->getDateDistribution()->format(\DateTimeInterface::ISO8601);
    }

    public function getExpirationDate(): ?string
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

    public function getCommodityIds(): array
    {
        $result = [];
        foreach ($this->object->getCommodities() as $commodity) {
            $result[] = $commodity->getId();
        }

        return $result;
    }

    public function getDescription(): ?string
    {
        return $this->object->getDescription();
    }

    public function getValidated(): bool
    {
        return $this->object->isValidated();
    }

    public function getCompleted(): bool
    {
        return (bool) $this->object->getCompleted();
    }

    public function getFoodLimit(): ?string
    {
        return $this->object->getFoodLimit();
    }

    public function getNonfoodLimit(): ?string
    {
        return $this->object->getNonFoodLimit();
    }

    public function getCashbackLimit(): ?string
    {
        return $this->object->getCashbackLimit();
    }

    public function getRemote(): bool
    {
        return (bool) $this->object->isRemoteDistributionAllowed();
    }

    public function getNumberOfBeneficiaries(): int
    {
        return $this->statsRepository->findByAssistance($this->object)->getNumberOfBeneficiaries();
    }

    public function getNote(): ?string
    {
        return $this->object->getNote();
    }

    public function getRound(): ?int
    {
        return $this->object->getRound();
    }
}
