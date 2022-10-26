<?php

declare(strict_types=1);

namespace Mapper\Smartcard;

use Component\Smartcard\Invoice\PreliminaryInvoiceDto;
use InvalidArgumentException;
use Serializer\MapperInterface;

class PreliminaryInvoiceDtoMapper implements MapperInterface
{
    /** @var PreliminaryInvoiceDto */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof PreliminaryInvoiceDto && isset($context[self::NEW_API]) && true === $context[self::NEW_API] && !isset($context['version']);
    }

    public function populate(object $object)
    {
        if ($object instanceof PreliminaryInvoiceDto) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . PreliminaryInvoiceDto::class . ', ' . get_class(
                $object
            ) . ' given.'
        );
    }

    public function getProjectId(): ?int
    {
        return $this->object->getPreliminaryInvoice()->getProject() ? $this->object->getPreliminaryInvoice(
        )->getProject()->getId() : null;
    }

    public function getPurchaseIds(): array
    {
        return array_values(array_map('intval', $this->object->getPreliminaryInvoice()->getPurchaseIds()));
    }

    public function getValue(): string
    {
        return $this->object->getPreliminaryInvoice()->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getPreliminaryInvoice()->getCurrency();
    }

    public function getCanRedeem(): bool
    {
        return $this->object->canRedeem();
    }
}
