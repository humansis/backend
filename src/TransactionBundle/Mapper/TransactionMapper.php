<?php

declare(strict_types=1);

namespace TransactionBundle\Mapper;

use Symfony\Component\Serializer\Serializer;
use NewApiBundle\Entity\Transaction;

class TransactionMapper
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
     * @param Transaction|null $transaction
     *
     * @return array
     */
    public function toValidateDistributionGroup(?Transaction $transaction): ?array
    {
        if (!$transaction) {
            return null;
        }

        return $this->serializer->normalize(
            $transaction,
            'json',
            [
                'groups' => ['ValidatedAssistance'],
                'datetime_format' => 'd-m-Y H:i',
            ]
        );
    }

    /**
     * @deprecated wrapper to symfony serialization group
     *
     * @param iterable $transactions
     *
     * @return \Generator
     */
    public function toValidateDistributionGroups(iterable $transactions)
    {
        foreach ($transactions as $transaction) {
            yield $this->toValidateDistributionGroup($transaction);
        }
    }
}
