<?php

declare(strict_types=1);

namespace Mapper;

use Entity\CountrySpecificAnswer;
use InvalidArgumentException;
use Serializer\MapperInterface;

class CountrySpecificAnswerMapper implements MapperInterface
{
    private ?\Entity\CountrySpecificAnswer $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof CountrySpecificAnswer && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof CountrySpecificAnswer) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . CountrySpecificAnswer::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getCountrySpecificOptionId(): int
    {
        return $this->object->getCountrySpecific()->getId();
    }

    public function getAnswer(): string
    {
        return $this->object->getAnswer();
    }
}
