<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Mapper\BeneficiaryMapper;
use DistributionBundle\Entity\AssistanceBeneficiary;
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

    /** @var BeneficiaryMapper */
    private $beneficiaryMapper;

    /**
     * AssistanceBeneficiaryMapper constructor.
     *
     * @param BookletMapper           $bookletMapper
     * @param GeneralReliefItemMapper $generalReliefItemMapper
     * @param TransactionMapper       $transactionMapper
     * @param BeneficiaryMapper|null  $beneficiaryMapper
     */
    public function __construct(
        BookletMapper $bookletMapper,
        GeneralReliefItemMapper $generalReliefItemMapper,
        TransactionMapper $transactionMapper,
        ?BeneficiaryMapper $beneficiaryMapper
    ) {
        $this->bookletMapper = $bookletMapper;
        $this->generalReliefItemMapper = $generalReliefItemMapper;
        $this->transactionMapper = $transactionMapper;
        $this->beneficiaryMapper = $beneficiaryMapper;
    }

    public function toMinimalArray(?AssistanceBeneficiary $assistanceBeneficiary): ?array
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

    public function toMinimalTransactionArray(?AssistanceBeneficiary $assistanceBeneficiary): ?array
    {
        if (!$assistanceBeneficiary) {
            return null;
        }

        $moneyRecieved = false;
        /** @var Transaction $transaction */
        foreach ($assistanceBeneficiary->getTransactions() as $transaction) {
            $moneyRecieved = $moneyRecieved || $transaction->getMoneyReceived();
        }

        return [
            'id' => $assistanceBeneficiary->getId(),
            'beneficiary' => [
                'id' => $assistanceBeneficiary->getBeneficiary()->getId(),
            ],
            'moneyRecieved' => (bool) $moneyRecieved,
        ];
    }

    public function toMinimalTransactionArrays(iterable $distributionBeneficiaries): iterable
    {
        foreach ($distributionBeneficiaries as $assistanceBeneficiary) {
            yield $this->toMinimalTransactionArray($assistanceBeneficiary);
        }
    }

    protected function toBaseArray(?AssistanceBeneficiary $assistanceBeneficiary): ?array
    {
        if (!$assistanceBeneficiary) {
            return null;
        }

        // send only successful transactions or all failed
        $transactions = [];
        foreach ($assistanceBeneficiary->getTransactions() as $transaction) {
            if (Transaction::SUCCESS === $transaction->getTransactionStatus()) {
                $transactions[] = $transaction;
            }
        }
        if (empty($transactions) && !empty($assistanceBeneficiary->getTransactions())) {
            $transactions = $assistanceBeneficiary->getTransactions();
        }

        $serializedAB = [
            'id' => $assistanceBeneficiary->getId(),
            'transactions' => $this->transactionMapper->toValidateDistributionGroups($transactions),
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

    public function toFullArray(?AssistanceBeneficiary $assistanceBeneficiary): ?array
    {
        if (!$assistanceBeneficiary) {
            return null;
        }

        $beneficiary = $assistanceBeneficiary->getBeneficiary();
        if (!$beneficiary instanceof Beneficiary && !$beneficiary instanceof Household) {
            $class = get_class($beneficiary);
            throw new \InvalidArgumentException("AssistanceBeneficiary #{$assistanceBeneficiary->getId()} is $class instead of ".Beneficiary::class);
        }

        $flatBase = $this->toBaseArray($assistanceBeneficiary);

        return array_merge($flatBase, [
            'beneficiary' => $this->beneficiaryMapper->toFullBeneficiaryGroup($beneficiary),
        ]);
    }

    public function toFullArrays(iterable $beneficiaries): iterable
    {
        foreach ($beneficiaries as $assistanceBeneficiary) {
            yield $this->toFullArray($assistanceBeneficiary);
        }
    }
}
