<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use DistributionBundle\Entity\Assistance;
use NewApiBundle\Repository\AssistanceStatisticsRepository;
use NewApiBundle\Serializer\MapperInterface;

class AssistanceOfflineAppMapper implements MapperInterface
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

    public function getProjectId(): int
    {
        return $this->object->getProject()->getId();
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

    public function getValidated(): bool
    {
        return (bool) $this->object->getValidated();
    }

    public function getCompleted(): bool
    {
        return (bool) $this->object->getCompleted();
    }

    public function getNumberOfBeneficiaries(): int
    {
        return $this->statsRepository->findByAssistance($this->object)->getNumberOfBeneficiaries();
    }
}
