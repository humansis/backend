<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Beneficiary;
use InvalidArgumentException;
use Serializer\MapperInterface;

class BeneficiaryOfflineAppMapper implements MapperInterface
{
    private ?\Entity\Beneficiary $object = null;

    /**
     * @param null $format
     * @param array|null $context
     *
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Beneficiary &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API] &&
            isset($context['offline-app']) && $context['version'] = true;
    }

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

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getLocalFamilyName(): string
    {
        return $this->object->getPerson()->getLocalFamilyName();
    }

    public function getLocalGivenName(): string
    {
        return $this->object->getPerson()->getLocalGivenName();
    }

    public function getNationalIdCards(): array
    {
        return array_values(
            array_map(fn($item) => $item->getId(), $this->object->getPerson()->getNationalIds()->toArray())
        );
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
}
