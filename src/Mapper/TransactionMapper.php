<?php

declare(strict_types=1);

namespace Mapper;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Transaction;

class TransactionMapper implements MapperInterface
{
    private ?\Entity\Transaction $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Transaction && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Transaction) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Transaction::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getIdentifier(): string
    {
        return $this->object->getTransactionId();
    }

    public function getSenderId(): int
    {
        return $this->object->getSentBy()->getId();
    }

    public function getAmountSent(): string
    {
        return $this->object->getAmountSent();
    }

    public function getDateSent(): string
    {
        return $this->object->getDateSent()->format(DateTimeInterface::ATOM);
    }

    public function getDatePickedUp(): ?string
    {
        return $this->object->getPickupDate()?->format(DateTimeInterface::ATOM);
    }

    public function getStatus(): string
    {
        return (string) $this->object->getTransactionStatus();
    }

    public function getMessage(): ?string
    {
        return $this->object->getMessage();
    }
}
