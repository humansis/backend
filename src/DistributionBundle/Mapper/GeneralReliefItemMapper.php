<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use DistributionBundle\Entity\GeneralReliefItem;
use Symfony\Component\Serializer\Serializer;

/**
 * @deprecated GRI is deprecated
 */
class GeneralReliefItemMapper
{
    /** @var Serializer */
    private $serializer;

    /**
     * BookletMapper constructor.
     *
     * @param Serializer $serializer
     */
    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @deprecated wrapper to symfony serialization group
     *
     * @param GeneralReliefItem|null $item
     *
     * @return array
     */
    public function toValidateDistributionGroup(?GeneralReliefItem $item): ?array
    {
        if (!$item) {
            return null;
        }

        return $this->serializer->normalize(
            $item,
            'json',
            [
                'groups' => ['ValidatedAssistance'],
                'datetime_format' => 'd-m-Y H:i',
            ]
        );
    }

    /**
     * @deprecated wrapper to symfony serialization group
     * @param iterable $items
     *
     * @return \Generator
     */
    public function toValidateDistributionGroups(iterable $items)
    {
        foreach ($items as $item) {
            yield $this->toValidateDistributionGroup($item);
        }
    }
}
