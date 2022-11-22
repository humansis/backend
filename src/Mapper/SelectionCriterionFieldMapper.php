<?php

declare(strict_types=1);

namespace Mapper;

use Component\SelectionCriteria\Structure\Field;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SelectionCriterionFieldMapper implements MapperInterface
{
    /** @var Field */
    private $object;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Field && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Field) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Field::class . ', ' . get_class($object) . ' given.'
        );
    }

    public function getCode(): string
    {
        return $this->object->getCode();
    }

    public function getValue(): string
    {
        return $this->translator->trans($this->object->getLabel());
    }

    public function getType(): string
    {
        return $this->object->getType();
    }
}
