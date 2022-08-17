<?php

declare(strict_types=1);

namespace MapperDeprecated;

use Entity\AssistanceBeneficiary;
use Entity\Transaction;

class AssistanceBeneficiaryMapper
{
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
}
