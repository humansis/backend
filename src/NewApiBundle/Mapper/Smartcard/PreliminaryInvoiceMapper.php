<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Smartcard;

use NewApiBundle\Entity\Smartcard\PreliminaryInvoice;
use NewApiBundle\Serializer\MapperInterface;

class PreliminaryInvoiceMapper implements MapperInterface
{
    /** @var PreliminaryInvoice */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.PreliminaryInvoice::class.', '.get_class($object).' given.');
    }

    public function getProjectId(): ?int
    {
        return $this->object->getProject() ? $this->object->getProject()->getId() : null;
    }

    public function getPurchaseIds(): array
    {
        return array_map('intval', $this->object->getPurchaseIds());
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
