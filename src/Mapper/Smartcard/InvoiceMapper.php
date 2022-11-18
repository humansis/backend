<?php

declare(strict_types=1);

namespace Mapper\Smartcard;

use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Invoice;

class InvoiceMapper implements MapperInterface
{
    private ?\Entity\Invoice $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Invoice && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Invoice) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Invoice::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getProjectId(): ?int
    {
        return $this->object->getProject() ? $this->object->getProject()->getId() : null;
    }

    public function getContractNumber(): ?string
    {
        return $this->object->getContractNo();
    }

    public function getValue()
    {
        return $this->object->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }

    public function getQuantity(): int
    {
        return $this->object->getPurchases()->count();
    }

    public function getDate(): string
    {
        return $this->object->getInvoicedAt()->format(DateTimeInterface::ATOM);
    }
}
