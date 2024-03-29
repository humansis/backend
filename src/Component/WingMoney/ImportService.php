<?php

declare(strict_types=1);

namespace Component\WingMoney;

use Entity\Beneficiary;
use Entity\Phone;
use Repository\PhoneRepository;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Component\WingMoney\ValueObject\ReportEntry;
use Entity\Transaction;
use Repository\TransactionRepository;
use Entity\User;

class ImportService
{
    public function __construct(private readonly TransactionRepository $transactionRepository, private readonly PhoneRepository $phoneRepository, private readonly EntityManagerInterface $em)
    {
    }

    public function filterExistingTransactions(array $entries): array
    {
        return array_filter($entries, function (ReportEntry $entry) {
            $transaction = $this->transactionRepository->findOneBy([
                'transactionId' => $entry->getTransactionId(),
            ]);

            return !$transaction instanceof Transaction;
        });
    }

    public function filterTransactionsInAssistanceOnly(array $entries, Assistance $assistance): array
    {
        return array_filter($entries, fn(ReportEntry $entry) => $this->findAssistanceBeneficiaryByPhoneNumber($entry, $assistance) instanceof AssistanceBeneficiary);
    }

    private function findAssistanceBeneficiaryByPhoneNumber(
        ReportEntry $entry,
        Assistance $assistance
    ): ?AssistanceBeneficiary {
        $number = substr($entry->getPhoneNumber(), 1);

        $phone = $this->phoneRepository->findOneBy(['number' => $number]);

        if (!$phone instanceof Phone) {
            return null;
        }

        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy([
            'person' => $phone->getPerson(),
            'archived' => false,
        ]);

        if (is_null($beneficiary)) {
            return null;
        }

        $assistanceBeneficiaries = $beneficiary->getDistributionBeneficiaries();

        foreach ($assistanceBeneficiaries as $assistanceBeneficiary) {
            if (
                !$assistanceBeneficiary->getRemoved() && $assistanceBeneficiary->getAssistance()->getId(
                ) === $assistance->getId()
            ) {
                $commodities = $assistance->getCommodities();

                if (empty($commodities)) {
                    return null;
                }

                $commodity = $commodities->first();

                if (round($commodity->getValue()) !== round($entry->getAmount())) {
                    return null;
                }

                return $assistanceBeneficiary;
            }
        }

        return null;
    }

    public function createTransactionFromReportEntry(ReportEntry $entry, Assistance $assistance, User $user)
    {
        $transaction = new Transaction();

        $transaction->setAssistanceBeneficiary($this->findAssistanceBeneficiaryByPhoneNumber($entry, $assistance));
        $transaction->setTransactionId($entry->getTransactionId());
        $transaction->setAmountSent($entry->getCurrency() . ' ' . number_format($entry->getAmount(), 2));
        $transaction->setDateSent($entry->getTransactionDate());
        $transaction->setTransactionStatus(Transaction::SUCCESS);
        $transaction->setSentBy($user);

        $this->em->persist($transaction);
        $this->em->flush();
    }
}
