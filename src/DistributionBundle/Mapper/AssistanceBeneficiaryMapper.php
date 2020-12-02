<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\DistributionBeneficiary;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Mapper\TransactionMapper;
use VoucherBundle\Mapper\BookletMapper;

class AssistanceBeneficiaryMapper
{
    /** @var BookletMapper */
    private $bookletMapper;

    /** @var GeneralReliefItemMapper */
    private $generalReliefItemMapper;

    /** @var TransactionMapper */
    private $transactionMapper;

    /**
     * AssistanceBeneficiaryMapper constructor.
     *
     * @param BookletMapper           $bookletMapper
     * @param GeneralReliefItemMapper $generalReliefItemMapper
     * @param TransactionMapper       $transactionMapper
     */
    public function __construct(BookletMapper $bookletMapper, GeneralReliefItemMapper $generalReliefItemMapper, TransactionMapper $transactionMapper)
    {
        $this->bookletMapper = $bookletMapper;
        $this->generalReliefItemMapper = $generalReliefItemMapper;
        $this->transactionMapper = $transactionMapper;
    }

    public function toMinimalArray(?DistributionBeneficiary $assistanceBeneficiary): ?array
    {
        if (!$assistanceBeneficiary) {
            return null;
        }

        return [
            'id' => $assistanceBeneficiary->getId(),
            'assistance' => $assistanceBeneficiary->getAssistance()->getId(),
            'beneficiary' => $assistanceBeneficiary->getBeneficiary()->getId(),
        ];
    }

    public function toMinimalArrays(iterable $beneficiaries): iterable
    {
        foreach ($beneficiaries as $assistanceBeneficiary) {
            yield $this->toMinimalArray($assistanceBeneficiary);
        }
    }

    public function toMinimalTransactionArray(?DistributionBeneficiary $distributionBeneficiary): ?array
    {
        if (!$distributionBeneficiary) {
            return null;
        }

        $moneyRecieved = false;
        /** @var Transaction $transaction */
        foreach ($distributionBeneficiary->getTransactions() as $transaction) {
            $moneyRecieved = $moneyRecieved || $transaction->getMoneyReceived();
        }

        return [
            'id' => $distributionBeneficiary->getId(),
            'beneficiary' => [
                'id' => $distributionBeneficiary->getBeneficiary()->getId(),
            ],
            'moneyRecieved' => (bool) $moneyRecieved,
        ];
    }

    public function toMinimalTransactionArrays(iterable $distributionBeneficiaries): iterable
    {
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            yield $this->toMinimalTransactionArray($distributionBeneficiary);
        }
    }

    protected function toBaseArray(?DistributionBeneficiary $assistanceBeneficiary): ?array
    {
        if (!$assistanceBeneficiary) {
            return null;
        }

        $serializedAB = [
            'id' => $assistanceBeneficiary->getId(),
            'transactions' => $this->transactionMapper->toValidateDistributionGroups($assistanceBeneficiary->getTransactions()),
            'booklets' => $this->bookletMapper->toValidateDistributionGroups($assistanceBeneficiary->getBooklets()),
            'general_reliefs' => $this->generalReliefItemMapper->toValidateDistributionGroups($assistanceBeneficiary->getGeneralReliefs()),
            'smartcard_distributed' => $assistanceBeneficiary->getSmartcardDistributed(),
            'smartcard_distributed_at' => null,
            'justification' => $assistanceBeneficiary->getJustification(),
            'removed' => $assistanceBeneficiary->getRemoved(),
        ];

        if (true === $assistanceBeneficiary->getSmartcardDistributed()) {
            $serializedAB['smartcard_distributed_at'] = $assistanceBeneficiary->getSmartcardDistributedAt()->format('d-m-Y H:i');
        }

        return $serializedAB;
    }

    public function toFullArray(?DistributionBeneficiary $distributionBeneficiary): ?array
    {
        if (!$distributionBeneficiary) {
            return null;
        }

        $beneficiary = $distributionBeneficiary->getBeneficiary();
        if (!$beneficiary instanceof Beneficiary && !$beneficiary instanceof Household) {
            $class = get_class($beneficiary);
            throw new \InvalidArgumentException("DistributionBeneficiary #{$distributionBeneficiary->getId()} is $class instead of ".Institution::class);
        }

        $flatBase = $this->toBaseArray($distributionBeneficiary);

        return array_merge($flatBase, [
            'beneficiary' => null,
        ]);
    }

    public function toFullArrays(iterable $beneficiaries): iterable
    {
        foreach ($beneficiaries as $assistanceBeneficiary) {
            yield $this->toFullArray($assistanceBeneficiary);
        }
    }
}
