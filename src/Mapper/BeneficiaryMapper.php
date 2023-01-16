<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Beneficiary;
use Enum\PersonGender;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Utils\DateTime\DateOnlyFormat;

class BeneficiaryMapper implements MapperInterface
{
    private ?\Entity\Beneficiary $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Beneficiary &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API] &&
            !isset($context['offline-app']);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Beneficiary) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Beneficiary::class . ', ' . $object::class . ' given.'
        );
    }

    public function getEnParentsName(): ?string
    {
        return $this->object->getPerson()->getEnParentsName();
    }

    public function getGender(): string
    {
        return PersonGender::MALE === $this->object->getPerson()->getGender() ? 'M' : 'F';
    }

    public function getNationalIds(): array
    {
        return array_values(
            array_map(fn($item) => $item->getId(), $this->object->getPerson()->getNationalIds()->toArray())
        );
    }

    public function getPhoneIds(): array
    {
        return array_values(
            array_map(fn($item) => $item->getId(), $this->object->getPerson()->getPhones()->toArray())
        );
    }

    public function getResidencyStatus(): string
    {
        return $this->object->getResidencyStatus();
    }

    public function getReferralType(): ?string
    {
        return $this->object->getPerson()->getReferral() ? $this->object->getPerson()->getReferral()->getType() : null;
    }

    public function getReferralComment(): ?string
    {
        return $this->object->getPerson()->getReferral() ? $this->object->getPerson()->getReferral()->getComment(
        ) : null;
    }

    public function getIsHead(): bool
    {
        return $this->object->isHead();
    }

    public function getVulnerabilityCriteria(): ?array
    {

        return $this->object->getVulnerabilityCriteria() ?  array_values($this->object->getVulnerabilityCriteria())
            : null;
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getDateOfBirth(): ?string
    {
        return $this->object->getPerson()->getDateOfBirth() ? $this->object->getPerson()->getDateOfBirth()->format(
            DateOnlyFormat::FORMAT
        ) : null;
    }

    public function getLocalFamilyName(): string
    {
        return $this->object->getPerson()->getLocalFamilyName();
    }

    public function getLocalGivenName(): string
    {
        return $this->object->getPerson()->getLocalGivenName();
    }

    public function getLocalParentsName(): ?string
    {
        return $this->object->getPerson()->getLocalParentsName();
    }

    public function getEnFamilyName(): ?string
    {
        return $this->object->getPerson()->getEnFamilyName();
    }

    public function getEnGivenName(): ?string
    {
        return $this->object->getPerson()->getEnGivenName();
    }

    public function getHouseholdId(): int
    {
        return $this->object->getHousehold()->getId();
    }
}
