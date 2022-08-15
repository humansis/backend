<?php

declare(strict_types=1);

namespace VoucherBundle\Mapper;

use Symfony\Component\Serializer\Serializer;
use NewApiBundle\Entity\Booklet;

class BookletMapper
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
     * @param Booklet|null $booklet
     *
     * @return array
     */
    public function toValidateDistributionGroup(?Booklet $booklet): ?array
    {
        if (!$booklet) {
            return null;
        }

        return $this->serializer->normalize(
            $booklet,
            'json',
            [
                'groups' => ['ValidatedAssistance'],
                'datetime_format' => 'd-m-Y H:i',
            ]
        );
    }

    /**
     * @deprecated wrapper to symfony serialization group
     * @param iterable $booklets
     *
     * @return \Generator
     */
    public function toValidateDistributionGroups(iterable $booklets)
    {
        foreach ($booklets as $booklet) {
            yield $this->toValidateDistributionGroup($booklet);
        }
    }
}
