<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\DTO\PurchaseRedemptionBatch;

class SmartcardRedemptionCandidateVersion3Mapper implements MapperInterface
{
    /** @var PurchaseRedemptionBatch */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof PurchaseRedemptionBatch &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API] &&
            isset($context['version']) && 3 === $context['version'];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof PurchaseRedemptionBatch) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.PurchaseRedemptionBatch::class.', '.get_class($object).' given.');
    }

    public function getProjectId(): int
    {
        return $this->object->getProjectId();
    }

    public function getValue()
    {
        return $this->object->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }
}
