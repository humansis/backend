<?php

declare(strict_types=1);

namespace Mapper\Smartcard;

use Entity\Smartcard\PreliminaryInvoice;
use InvalidArgumentException;
use Serializer\MapperInterface;

class PreliminaryInvoiceMapper implements MapperInterface
{
    private ?\Entity\Smartcard\PreliminaryInvoice $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof PreliminaryInvoice && isset($context[self::NEW_API]) && true === $context[self::NEW_API] && !isset($context['version']);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof PreliminaryInvoice) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . PreliminaryInvoice::class . ', ' . $object::class . ' given.'
        );
    }

    public function getProjectId(): ?int
    {
        return $this->object->getProject() ? $this->object->getProject()->getId() : null;
    }

    public function getPurchaseIds(): array
    {
        return array_values(array_map('intval', $this->object->getPurchaseIds()));
    }

    public function getValue(): string
    {
        return $this->object->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }

    public function getCanRedeem(): bool
    {
        return $this->object->isRedeemable();
    }
}
