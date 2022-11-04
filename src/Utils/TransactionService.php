<?php

namespace Utils;

use DateTime;
use Entity\AssistanceBeneficiary;
use Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Enum\ModalityType;
use Exception;
use Enum\CacheTarget;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Entity\Transaction;
use Twig\Cache\FilesystemCache;
use Utils\Provider\DefaultFinancialProvider;
use Twig\Environment;
use Entity\User;
use Utils\Provider\KHMFinancialProvider;

/**
 * Class TransactionService
 *
 * @package Utils
 */
class TransactionService
{
    /** @var string */
    private $email;

    /**
     * TransactionService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContainerInterface $container,
        private readonly CacheInterface $cache,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly ExportService $exportService,
        private readonly KHMFinancialProvider $khmFinancialProvider
    ) {
        $this->email = $this->container->getParameter('email');
    }

    /**
     * Send money to distribution beneficiaries
     *
     *
     * @throws InvalidArgumentException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws Exception
     */
    public function sendMoney(string $countryISO3, Assistance $assistance, User $user): object
    {
        $financialProvider = $this->getFinancialProviderForCountry($countryISO3);

        if ($assistance->getCommodities()[0]->getModalityType() === ModalityType::MOBILE_MONEY) {
            $amountToSend = $assistance->getCommodities()[0]->getValue();
            $currencyToSend = $assistance->getCommodities()[0]->getUnit();
        } else {
            $this->logger->error('Assistance has no Mobile money commodity');
            throw new Exception("The commodity of the distribution does not allow this operation.");
        }

        $from = $user->getId();
        $this->cache->delete(CacheTarget::assistanceId($assistance->getId()));

        return $financialProvider->sendMoneyToAll($assistance, $amountToSend, $currencyToSend, $from);
    }

    /**
     * Get the financial provider corresponding to the current country
     *
     * @param string $countryISO3 iso3 code of the country
     * @return object|Class|DefaultFinancialProvider
     * @throws Exception
     */
    private function getFinancialProviderForCountry(string $countryISO3)
    {
        try {
            if ($countryISO3 === 'KHM') {
                $provider = $this->khmFinancialProvider;
            } else {
                $provider = null;
            }
        } catch (Exception) {
            $provider = null;
        }

        if (!($provider instanceof DefaultFinancialProvider)) {
            $this->logger->error("Country $countryISO3 has no defined financial provider");
            throw new Exception("The financial provider for " . $countryISO3 . " is not properly defined");
        }
        $this->logger->error("Financial provider for country $countryISO3: " . $provider::class);

        return $provider;
    }

    /**
     * Send email to confirm transaction
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function sendVerifyEmail(User $user, Assistance $assistance)
    {
        $code = random_int(100000, 999999);

        $id = $user->getId();
        $cache = new FilesystemCache(sys_get_temp_dir());
        $cache->set($assistance->getId() . '-' . $id . '-code_transaction_confirmation', $code);

        $commodity = $assistance->getCommodities()->get(0);
        $numberOfBeneficiaries = count($assistance->getDistributionBeneficiaries());
        $amountToSend = $numberOfBeneficiaries * $commodity->getValue();

        /*$message = (new Swift_Message('Confirm transaction for distribution ' . $assistance->getName()))
            ->setFrom($this->email)
            ->setTo($user->getEmail())
            ->setBody(
                $this->twig->render(
                    'Emails/confirm_transaction.html.twig',
                    [
                        'distribution' => $assistance->getName(),
                        'amount' => $amountToSend . ' ' . $commodity->getUnit(),
                        'number' => $numberOfBeneficiaries,
                        'date' => new DateTime(),
                        'email' => $user->getEmail(),
                        'code' => $code,
                    ]
                ),
                'text/html'
            );*/

        //$this->mailer->send($message);
        $this->logger->error("Code for verify assistance was sent to " . $user->getEmail(), [$assistance]);
    }

    /**
     * Verify confirmation code
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function verifyCode(int $code, User $user, Assistance $assistance)
    {
        $cache = new FilesystemCache(sys_get_temp_dir());

        $checkedAgainst = '';
        $id = $user->getId();
        if ($cache->has($assistance->getId() . '-' . $id . '-code_transaction_confirmation')) {
            $checkedAgainst = $cache->get($assistance->getId() . '-' . $id . '-code_transaction_confirmation');
        }

        $result = ($code === intval($checkedAgainst));

        if ($result) {
            $cache->delete($assistance->getId() . '-' . $id . '-code_transaction_confirmation');
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function exportToCsv(Assistance $assistance, string $type)
    {
        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);

        $exportableTable = [];
        foreach ($assistanceBeneficiary as $db) {
            $lastTransaction = $this->em->getRepository(Transaction::class)->findOneBy([
                'assistanceBeneficiary' => $db,
            ], ['dateSent' => 'desc']);

            $successTransaction = $this->em->getRepository(Transaction::class)->findOneBy([
                'assistanceBeneficiary' => $db,
                'transactionStatus' => Transaction::SUCCESS,
            ], ['dateSent' => 'desc']);

            $transaction = $lastTransaction;
            if (null === $lastTransaction) {
                $status = "Not sent";
            } elseif (null !== $successTransaction) { // successful transaction has priority over everything
                $status = "Success";
                $transaction = $successTransaction;
            } elseif ($lastTransaction->getTransactionStatus() == Transaction::FAILURE) {
                $status = "Error";
            } elseif ($lastTransaction->getTransactionStatus() == Transaction::NO_PHONE) {
                $status = "No Phone";
            } elseif ($lastTransaction->getTransactionStatus() == Transaction::CANCELED) {
                $status = "Canceled";
            } else {
                $status = "Unknown error";
            }

            $beneficiary = $db->getBeneficiary();
            $commonFields = $beneficiary->getCommonExportFields();

            $transactionAdditionalInfo = [
                "Amount Sent" => null,
                "Sent At" => null,
                "Phone number" => null,
                "Transaction Status" => $status,
                "Wing Transaction ID" => null,
                "Message" => null,
                "Money Received" => null,
                "Pickup Date" => null,
                "Removed" => $db->getRemoved() ? 'Yes' : 'No',
                "Justification for adding/removing" => $db->getJustification(),
            ];

            if (null !== $transaction) {
                $transactionAdditionalInfo["Amount Sent"] = $transaction->getAmountSent();
                $transactionAdditionalInfo["Message"] = $transaction->getMessage();
                $transactionAdditionalInfo["Money Received"] = $transaction->getMoneyReceived();
                $transactionAdditionalInfo["Wing Transaction ID"] = $transaction->getTransactionId();
            }
            if (null !== $transaction && $transaction->getDateSent()) {
                $transactionAdditionalInfo["Sent At"] = $transaction->getDateSent()->format('d-m-Y H:i:s');
                $phoneNumbers = [];
                foreach ($beneficiary->getPerson()->getPhones() as $phone) {
                    $phoneNumbers[] = $phone->getPrefix() . ' ' . $phone->getNumber();
                }
                $transactionAdditionalInfo["Phone number"] = implode(', ', $phoneNumbers);
            }
            if (null !== $transaction && $transaction->getPickupDate()) {
                $transactionAdditionalInfo["Pickup Date"] = $transaction->getPickupDate()->format('d-m-Y H:i:s');
            }

            array_push(
                $exportableTable,
                array_merge($commonFields, $transactionAdditionalInfo)
            );
        }

        return $this->exportService->export($exportableTable, 'transaction', $type);
    }
}
