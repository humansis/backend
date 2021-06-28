<?php
declare(strict_types=1);

namespace NewApiBundle\Component\WingMoney;

use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Repository\PhoneRepository;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\WingMoney\ValueObject\ReportEntry;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Repository\TransactionRepository;
use UserBundle\Entity\User;

class ImportService
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var PhoneRepository
     */
    private $phoneRepository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(TransactionRepository $transactionRepository, PhoneRepository $phoneRepository, EntityManagerInterface $em)
    {
        $this->transactionRepository = $transactionRepository;
        $this->phoneRepository = $phoneRepository;
        $this->em = $em;
    }

    /**
     * @param array $entries
     *
     * @return array
     */
    public function filterExistingTransactions(array $entries): array
    {
        return array_filter($entries, function (ReportEntry $entry) {
            $transaction = $this->transactionRepository->findOneBy([
                'transactionId' => $entry->getTransactionId(),
            ]);

            return !$transaction instanceof Transaction;
        });
    }

    /**
     * @param array      $entries
     * @param Assistance $assistance
     *
     * @return array
     */
    public function filterTransactionsInAssistanceOnly(array $entries, Assistance $assistance): array
    {
        return array_filter($entries, function (ReportEntry $entry) use ($assistance) {
            return $this->findAssistanceBeneficiaryByPhoneNumber($entry, $assistance) instanceof AssistanceBeneficiary;
        });
    }

    private function findAssistanceBeneficiaryByPhoneNumber(ReportEntry $entry, Assistance $assistance): ?AssistanceBeneficiary
    {
        $number = substr($entry->getPhoneNumber(), 1);

        $phone = $this->phoneRepository->findOneBy(['number' => $number]);

        if (!$phone instanceof Phone) {
            return null;
        }

        $beneficiary = $phone->getPerson()->getBeneficiary();

        if (is_null($beneficiary)) {
            return null;
        }

        $assistanceBeneficiaries = $beneficiary->getDistributionBeneficiaries();

        foreach ($assistanceBeneficiaries as $assistanceBeneficiary) {
            if ($assistanceBeneficiary->getAssistance()->getId() === $assistance->getId()) {
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
        $transaction->setAmountSent($entry->getCurrency().' '.number_format($entry->getAmount(), 2));
        $transaction->setDateSent($entry->getTransactionDate());
        $transaction->setTransactionStatus(Transaction::SUCCESS);
        $transaction->setSentBy($user);

        $this->em->persist($transaction);
        $this->em->flush();
    }
}
