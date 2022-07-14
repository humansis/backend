<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
use BeneficiaryBundle\Mapper\BeneficiaryMapper;
use DistributionBundle\Entity\AssistanceBeneficiary;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Mapper\TransactionMapper;
use VoucherBundle\Mapper\BookletMapper;

class AssistanceBeneficiaryMapper
{
    /** @var BeneficiaryMapper */
    private $beneficiaryMapper;

    /**
     * AssistanceBeneficiaryMapper constructor.
     *
     * @param BeneficiaryMapper|null  $beneficiaryMapper
     */
    public function __construct(
        ?BeneficiaryMapper $beneficiaryMapper
    ) {
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

        $serializedAB = [
            'id' => $assistanceBeneficiary->getId(),
            'transactions' => [], // TODO: remove after PIN-3249
            'booklets' => [], // TODO: remove after PIN-3249
            'general_reliefs' => [], // TODO: remove after PIN-3249
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
