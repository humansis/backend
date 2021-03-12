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
        return $object instanceof Beneficiary && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
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

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getResidencyStatus(): string
    {
        return $this->object->getResidencyStatus();
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
                $data[] = $criterion->getFieldString();
            }
        }

        return $data;
    }

    public function getPersonId(): ?int
    {
        return $this->object->getPerson()->getId();
    }
}
