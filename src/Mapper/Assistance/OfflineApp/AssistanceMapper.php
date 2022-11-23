<?php

declare(strict_types=1);

namespace Mapper\Assistance\OfflineApp;

use Entity\Assistance;
use Enum\AssistanceTargetType;
use Enum\ProductCategoryType;
use InvalidArgumentException;
use Serializer\MapperInterface;

class AssistanceMapper implements MapperInterface
{
    private ?\Entity\Assistance $object = null;

    /** @var string */
    public $date_distribution;

    /** @var string|null */
    public $date_expiration;

    /** @var int */
    public $beneficiaries_count;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Assistance && isset($context[MapperInterface::OFFLINE_APP]) && true === $context[MapperInterface::OFFLINE_APP]
            && isset($context['version']) && $context['version'] === 'v1';
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Assistance) {
            $this->object = $object;

            //Workaround because of /offline-app/v1/projects/{id}/distribution API. Getters do not support snake case style. It is done properly in v2 of the endpoint.
            //This file (alongside with all code necessary for v1) should be removed in 3.9
            $this->date_distribution = $object->getDateDistribution()->format('d-m-Y');
            $this->date_expiration = $object->getDateExpiration() ? $object->getDateExpiration()->format(
                'd-m-Y'
            ) : null;
            $this->beneficiaries_count = $object->getDistributionBeneficiaries()->count();

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

    public function getType(): int
    {
        return match ($this->object->getTargetType()) {
            AssistanceTargetType::INDIVIDUAL => 1,
            AssistanceTargetType::HOUSEHOLD => 0,
            default => - 1,
        };
    }

    public function getCommodities(): array
    {
        return $this->object->getCommodities()->toArray();
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
