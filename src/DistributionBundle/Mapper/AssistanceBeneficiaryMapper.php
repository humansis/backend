<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use DistributionBundle\Entity\DistributionBeneficiary;
use TransactionBundle\Entity\Transaction;

class AssistanceBeneficiaryMapper
{
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

    public function toFlatArray(?DistributionBeneficiary $assistanceBeneficiary): ?array
    {
        if (!$assistanceBeneficiary) {
            return null;
        }

        $serializedAB = [
            'id' => $assistanceBeneficiary->getId(),
            'transactions' => [],
            'distributedItems' => [],
            'smartcardDistributedAt' => null,
            'justification' => $assistanceBeneficiary->getJustification(),
            'removed' => $assistanceBeneficiary->getRemoved(),
        ];

        if ($assistanceBeneficiary->getSmartcardDistributed()) {
            $serializedAB['smartcardDistributedAt'] = $assistanceBeneficiary->getSmartcardDistributedAt()->format('d-m-Y H:i');
        }

        foreach ($assistanceBeneficiary->getTransactions() as $transaction) {
            $serializedAB['transactions'][] = $transaction->getId();
        }

        foreach ($assistanceBeneficiary->getGeneralReliefs() as $item) {
            $serializedAB['distributedItems'][] = $item->getId();
        }

        return $serializedAB;
    }

    public function toFlatArrays(iterable $beneficiaries): iterable
    {
        foreach ($beneficiaries as $assistanceBeneficiary) {
            yield $this->toFlatArray($assistanceBeneficiary);
        }
    }
}
