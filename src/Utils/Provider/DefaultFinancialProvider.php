<?php

namespace Utils\Provider;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Entity\Transaction;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class DefaultFinancialProvider
 *
 * @package Utils\Provider
 */
abstract class DefaultFinancialProvider
{
    /**
     * Limit for one batch site due problems with timeouts
     */
    final public const MAX_BATCH_SIZE = 100;

    /** @var string $url */
    protected $url;

    /** @var string from */
    protected $from;

    /**
     * DefaultFinancialProvider constructor.
     */
    public function __construct(protected EntityManagerInterface $em, protected LoggerInterface $logger, private readonly TokenStorageInterface $tokenStorage, protected string $rootDir)
    {
    }

    /**
     * Send request to financial API
     *
     * @param Assistance $assistance
     * @param string $type type of the request ("GET", "POST", etc.)
     * @param string $route url of the request
     * @param array $body body of the request (optional)
     * @return mixed $response
     * @throws Exception
     */
    public function sendRequest(Assistance $assistance, string $type, string $route, array $body = []): mixed
    {
        throw new Exception("You need to define the financial provider for the country.");
    }

    /**
     * Send money to one beneficiary
     *
     * @return Transaction
     * @throws Exception
     */
    public function sendMoneyToOne(
        string $phoneNumber,
        AssistanceBeneficiary $assistanceBeneficiary,
        float $amount,
        string $currency
    ): Transaction {
        throw new Exception("You need to define the financial provider for the country.");
    }

    /**
     * Send money to all beneficiaries
     *
     * @return array
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function sendMoneyToAll(Assistance $assistance, float $amount, string $currency, string $from)
    {
        // temporary variables to limit the amount of money that can be sent for one distribution to: 1000$
        $cache = new FilesystemCache();
        if (!$cache->has($assistance->getId() . '-amount_sent')) {
            $cache->set($assistance->getId() . '-amount_sent', 0);
        }

        $this->logger->info("Money sending: Start");

        $this->from = $from;
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findBy([
                'assistance' => $assistance,
                'removed' => 0,
            ]);

        $this->logger->info("Money sending: Recipient count " . count($distributionBeneficiaries));

        $response = [
            'sent' => [],
            'failure' => [],
            'no_mobile' => [],
            'already_sent' => [],
        ];

        $count = 0;
        $requestCount = 0;
        foreach ($distributionBeneficiaries as $assistanceBeneficiary) {
            $cache->set($this->from . '-progression-' . $assistance->getId(), $count);
            $beneficiary = $assistanceBeneficiary->getBeneficiary();

            if ($beneficiary->getArchived() == true) {
                $this->logger->debug(
                    "Money sending: Recipient omitted - archived",
                    [$beneficiary, $assistanceBeneficiary]
                );
                array_push($response['failure'], $assistanceBeneficiary);
                continue;
            }

            $transactions = $assistanceBeneficiary->getTransactions();
            if (!$transactions->isEmpty()) {
                // if this beneficiary already has transactions
                // filter out the one that is a success (if it exists)
                $transactions = $transactions->filter(
                    fn($transaction) => $transaction->getTransactionStatus() === 1
                );
            }

            $phoneNumber = null;
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == 'mobile' || $phone->getType() === 'Mobile') {
                    $phoneNumber = '0' . $phone->getNumber();
                    break;
                }
            }

            if ($phoneNumber) {
                // if a successful transaction already exists
                if (!$transactions->isEmpty()) {
                    $this->logger->debug(
                        "Money sending: Recipient omitted - already sent",
                        [$beneficiary, $assistanceBeneficiary]
                    );
                    array_push($response['already_sent'], $assistanceBeneficiary);
                } else {
                    $amountSent = 0;
                    if ($cache->has($assistance->getId() . '-amount_sent')) {
                        $amountSent = $cache->get($assistance->getId() . '-amount_sent');
                    }
                    // if the limit hasn't been reached
                    if (empty($amountSent) || $amountSent + $amount <= 100000) {
                        try {
                            $this->logger->debug(
                                "Money sending: Recipient sending start",
                                [$beneficiary, $assistanceBeneficiary]
                            );
                            $transaction = $this->sendMoneyToOne(
                                $phoneNumber,
                                $assistanceBeneficiary,
                                $amount,
                                $currency
                            );
                            if ($transaction->getTransactionStatus() === 0) {
                                array_push($response['failure'], $assistanceBeneficiary);
                            } else {
                                // add amount to amount sent
                                $cache->set($assistance->getId() . '-amount_sent', $amountSent + $amount);
                                array_push($response['sent'], $assistanceBeneficiary);
                            }
                        } catch (Exception $e) {
                            $this->logger->warning(
                                "Money sending: Recipient error: " . $e->getMessage(),
                                [$beneficiary, $assistanceBeneficiary]
                            );
                            $this->createTransaction(
                                $assistanceBeneficiary,
                                '',
                                new DateTime(),
                                0,
                                2,
                                $e->getMessage()
                            );
                            array_push($response['failure'], $assistanceBeneficiary);
                        } finally {
                            $requestCount++;
                        }
                    } else {
                        $this->logger->warning(
                            "Money sending: Recipient omitted - money limit",
                            [$beneficiary, $assistanceBeneficiary]
                        );
                        $this->createTransaction(
                            $assistanceBeneficiary,
                            '',
                            new DateTime(),
                            0,
                            0,
                            "The maximum amount that can be sent per distribution (USD 10000) has been reached"
                        );
                    }
                }
            } else {
                $this->logger->debug(
                    "Money sending: Recipient omitted - no mobile",
                    [$beneficiary, $assistanceBeneficiary]
                );
                $this->createTransaction($assistanceBeneficiary, '', new DateTime(), 0, 2, "No Phone");
                array_push($response['no_mobile'], $assistanceBeneficiary);
            }

            $count++;

            if ($requestCount >= self::MAX_BATCH_SIZE) {
                break;
            }
        }

        $cache->delete($this->from . '-progression-' . $assistance->getId());

        return $response;
    }

    /**
     * Create transaction
     *
     * @return Transaction
     */
    public function createTransaction(
        AssistanceBeneficiary $assistanceBeneficiary,
        string $transactionId,
        DateTime $dateSent,
        string $amountSent,
        int $transactionStatus,
        string $message = null
    ) {
        $user = $this->tokenStorage->getToken()->getUser();

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($assistanceBeneficiary);
        $transaction->setDateSent($dateSent);
        $transaction->setTransactionId($transactionId);
        $transaction->setAmountSent($amountSent);
        $transaction->setTransactionStatus($transactionStatus);
        $transaction->setMessage($message);
        $transaction->setSentBy($user);

        $assistanceBeneficiary->addTransaction($transaction);
        $user->addTransaction($transaction);

        $this->em->persist($transaction);
        $this->em->persist($assistanceBeneficiary);
        $this->em->persist($user);
        $this->em->flush();

        return $transaction;
    }

    /**
     * Save transaction record in file
     *
     * @return void
     */
    public function recordTransaction(Assistance $assistance, array $data)
    {
        $dir_root = $this->rootDir;
        $dir_var = $dir_root . '/../var/logs';
        if (!is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $file_record = $dir_var . '/record_' . $assistance->getId() . '.csv';

        $fp = fopen($file_record, 'a');
        if (!file_get_contents($file_record)) {
            fputcsv($fp, ['FROM', 'DATE', 'URL', 'HTTP CODE', 'RESPONSE', 'ERROR', 'PARAMETERS'], ';');
        }

        fputcsv($fp, $data, ";");

        fclose($fp);
    }
}
