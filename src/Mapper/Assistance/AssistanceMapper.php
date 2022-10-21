<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Component\Codelist\CodeItem;
use DateTime;
use DateTimeInterface;
use Entity;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\Domain;
use Entity\Location;
use InvalidArgumentException;
use Entity\ScoringBlueprint;
use Serializer\MapperInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AssistanceMapper implements MapperInterface
{
    private ?\Entity\Assistance $object = null;

    private ?\Component\Assistance\Domain\Assistance $domainObject = null;

    public function __construct(private readonly AssistanceFactory $factory, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof Entity\Assistance || $object instanceof Domain\Assistance)
            && isset($context[self::NEW_API])
            && true === $context[self::NEW_API]
            && !isset($context['offline-app']);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Entity\Assistance) {
            $this->object = $object;
            $this->domainObject = $this->factory->hydrate($object);

            return;
        }

        if ($object instanceof Domain\Assistance) {
            $this->object = $object->getAssistanceRoot();
            $this->domainObject = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Entity\Assistance::class . ', ' . $object::class . ' given.'
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
        return $this->object->getDateDistribution()->format(DateTime::ATOM);
    }

    public function getDateExpiration(): ?string
    {
        return $this->object->getDateExpiration() ? $this->object->getDateExpiration()->format(
            DateTimeInterface::ATOM
        ) : null;
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

    public function getLocationId(): Location
    {
        return $this->object->getLocation();
    }

    public function getAdm1(): ?Location
    {
        return $this->object->getLocation()->getLocationByLevel(1) ?: null;
    }

    public function getAdm2(): ?Location
    {
        return $this->object->getLocation()->getLocationByLevel(2) ?: null;
    }

    public function getAdm3(): ?Location
    {
        return $this->object->getLocation()->getLocationByLevel(3) ?: null;
    }

    public function getAdm4(): ?Location
    {
        return $this->object->getLocation()->getLocationByLevel(4) ?: null;
    }

    public function getSector(): string
    {
        return $this->object->getSector();
    }

    public function getSubsector(): ?string
    {
        return $this->object->getSubSector();
    }

    public function getScoringBlueprint(): ?ScoringBlueprint
    {
        return $this->object->getScoringBlueprint();
    }

    public function getCommodityIds(): array
    {
        return $this->domainObject->getCommodityIds();
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
        return $this->object->isValidated();
    }

    public function getCompleted(): bool
    {
        return $this->object->getCompleted();
    }

    public function getState(): CodeItem
    {
        return new CodeItem(
            $this->object->getState(),
            $this->translator->trans($this->object->getState())
        );
    }

    public function getDistributionStarted(): bool
    {
        return $this->domainObject->hasDistributionStarted();
    }

    public function getDeletable(): bool
    {
        return !$this->object->isValidated();
    }

    public function getSelectionId(): int
    {
        return $this->object->getAssistanceSelection()->getId();
    }

    public function getThreshold(): ?int
    {
        return $this->object->getAssistanceSelection()->getThreshold();
    }

    public function getRemoteDistributionAllowed(): ?bool
    {
        return $this->object->isRemoteDistributionAllowed();
    }

    public function getRound(): ?int
    {
        return $this->object->getRound();
    }

    public function getNote(): ?string
    {
        return $this->object->getNote();
    }

    public function getFoodLimit(): ?string
    {
        return $this->object->getFoodLimit();
    }

    public function getNonFoodLimit(): ?string
    {
        return $this->object->getNonFoodLimit();
    }

    public function getCashbackLimit(): ?string
    {
        return $this->object->getCashbackLimit();
    }

    public function getAllowedProductCategoryTypes(): array
    {
        return $this->object->getAllowedProductCategoryTypes();
    }
}
