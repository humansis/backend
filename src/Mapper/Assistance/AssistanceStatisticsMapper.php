<?php declare(strict_types=1);

namespace Mapper\Assistance;

use Component\Assistance\DTO\Statistics;
use Serializer\MapperInterface;

class AssistanceStatisticsMapper implements MapperInterface
{

    /** @var Statistics */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Statistics && isset($context[self::NEW_API]) && true === $context[self::NEW_API];

    }

    public function populate(object $object)
    {
        if ($object instanceof Statistics) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Statistics::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getAmountDistributed(): float
    {
        return (float) $this->object->getAmountDistributed();
    }

    public function getAmountPickedUp(): float
    {
        return (float) $this->object->getAmountPickedUp();
    }

    public function getAmountSent(): float
    {
        return (float) $this->object->getAmountSent();
    }

    public function getAmountTotal(): float
    {
        return (float) $this->object->getAmountTotal();
    }

    public function getAmountUsed(): float
    {
        return (float) $this->object->getAmountUsed();
    }

    public function getBeneficiariesTotal(): int
    {
        return $this->object->getBeneficiariesTotal();
    }

    public function getBeneficiariesDeleted(): float
    {
        return $this->object->getBeneficiariesDeleted();
    }

}
