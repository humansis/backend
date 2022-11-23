<?php

declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use DateTimeInterface;
use Entity\Assistance;
use Enum\ProductCategoryType;
use InvalidArgumentException;
use Serializer\MapperInterface;

class AssistanceMapperV2 implements MapperInterface
{
    private ?\Entity\Assistance $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Assistance && isset($context[MapperInterface::OFFLINE_APP]) && true === $context[MapperInterface::OFFLINE_APP]
            && isset($context['version']) && $context['version'] === 'v2';
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

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Assistance::class . ', ' . $object::class . ' given.'
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

    public function getDateDistribution(): string
    {
        return $this->object->getDateDistribution()->format(DateTimeInterface::ATOM);
    }

    public function getDateExpiration(): ?string
    {
        return $this->object->getDateExpiration() ? $this->object->getDateExpiration()->format(
            DateTimeInterface::ATOM
        ) : null;
    }

    public function getTargetType(): string
    {
        return $this->object->getTargetType();
    }

    public function getCommodities(): array
    {
        return $this->object->getCommodities()->toArray();
    }

    public function getBeneficiariesCount(): int
    {
        return $this->object->getDistributionBeneficiaries()->count();
    }

    public function getFoodLimit(): ?string
    {
        $isFoodAllowed = in_array(ProductCategoryType::FOOD, $this->object->getAllowedProductCategoryTypes());

        return $isFoodAllowed ? $this->object->getFoodLimit() : '0.00';
    }

    public function getNonfoodLimit(): ?string
    {
        $isNonFoodAllowed = in_array(ProductCategoryType::NONFOOD, $this->object->getAllowedProductCategoryTypes());

        return $isNonFoodAllowed ? $this->object->getNonfoodLimit() : '0.00';
    }

    public function getCashbackLimit(): ?string
    {
        $isCashbackAllowed = in_array(ProductCategoryType::CASHBACK, $this->object->getAllowedProductCategoryTypes());

        return $isCashbackAllowed ? $this->object->getCashbackLimit() : '0.00';
    }

    public function getRemoteDistributionAllowed(): bool
    {
        return (bool) $this->object->isRemoteDistributionAllowed();
    }

    public function getCompleted(): bool
    {
        return (bool) $this->object->getCompleted();
    }

    public function getValidated(): bool
    {
        return $this->object->isValidated();
    }

    public function getArchived(): bool
    {
        return (bool) $this->object->getArchived();
    }
}
