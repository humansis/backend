<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Serializer\MapperInterface;

class BeneficiaryMapper implements MapperInterface
{
    /** @var Beneficiary */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Beneficiary::class.', '.get_class($object).' given.');
    }

    public function getEnParentsName(): ?string
    {
        return $this->object->getPerson()->getEnParentsName();
    }

    public function getGender(): string
    {
        return 1 === $this->object->getPerson()->getGender() ? 'M' : 'F';
    }

    public function getNationalIds(): array
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $this->object->getPerson()->getNationalIds()->toArray());
    }

    public function getPhoneIds(): array
    {
        return array_map(function ($item) {
            return $item->getId();
        }, $this->object->getPerson()->getPhones()->toArray());
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
        return $this->object->getPerson()->getReferral() ? $this->object->getPerson()->getReferral()->getComment() : null;
    }

    public function getIsHead(): bool
    {
        return $this->object->isHead();
    }

    public function getVulnerabilityCriteria(): array
    {
        $data = [];

        foreach ($this->object->getVulnerabilityCriteria() as $criterion) {
            if ($criterion->isActive()) {
                $data[] = (string) $criterion->getFieldString();
            }
        }

        return $data;
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getDateOfBirth(): string
    {
        return $this->object->getPerson()->getDateOfBirth()->format(\DateTime::ISO8601);
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
}
