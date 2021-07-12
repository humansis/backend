<?php

namespace TransactionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Utils\Provider\DefaultFinancialProvider;
use UserBundle\Entity\User;

/**
 * Class TransactionService
 * @package TransactionBundle\Utils
 */
class TransactionService
{
    /** @var string */
    private $email;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;
    
    /** @var DefaultFinancialProvider $financialProvider */
    private $financialProvider;

    /** @var LoggerInterface */
    private $logger;

    /**
     * TransactionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->email = $this->container->getParameter('email');
        $this->logger = $container->get('monolog.logger.mobile');
    }

    /**
     * Send money to distribution beneficiaries
     * @param  string $countryISO3
     * @param  Assistance $assistance
     * @return object
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendMoney(string $countryISO3, Assistance $assistance, User $user)
    {
        $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);

        if ($assistance->getCommodities()[0]->getModalityType()->getName() === "Mobile Money") {
            $amountToSend = $assistance->getCommodities()[0]->getValue();
            $currencyToSend = $assistance->getCommodities()[0]->getUnit();
        } else {
            $this->logger->error('Assistance has no Mobile money commodity');
            throw new \Exception("The commodity of the distribution does not allow this operation.");
        }
        
        $from = $user->getId();
        
        return $this->financialProvider->sendMoneyToAll($assistance, $amountToSend, $currencyToSend, $from);
    }
    
    /**
     * Get the financial provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @return object|Class|DefaultFinancialProvider
     * @throws \Exception
     */
    private function getFinancialProviderForCountry(string $countryISO3)
    {
        try {
            $provider = $this->container->get('transaction.' . strtolower($countryISO3) . '_financial_provider');
        } catch (\Exception $e) {
            $provider = null;
        }
        
        if (! ($provider instanceof DefaultFinancialProvider)) {
            $this->logger->error("Country $countryISO3 has no defined financial provider");
            throw new \Exception("The financial provider for " . $countryISO3 . " is not properly defined");
        }
        $this->logger->error("Financial provider for country $countryISO3: ".get_class($provider));
        return $provider;
    }

    /**
     * Send email to confirm transaction
     * @param  User $user
     * @param  Assistance $assistance
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendVerifyEmail(User $user, Assistance $assistance)
    {
        $code = random_int(100000, 999999);

        $id = $user->getId();
        $cache = new FilesystemCache();
        $cache->set($assistance->getId() . '-' . $id . '-code_transaction_confirmation', $code);

        $commodity = $assistance->getCommodities()->get(0);
        $numberOfBeneficiaries = count($assistance->getDistributionBeneficiaries());
        $amountToSend = $numberOfBeneficiaries * $commodity->getValue();

        $message = (new \Swift_Message('Confirm transaction for distribution ' . $assistance->getName()))
            ->setFrom($this->email)
            ->setTo($user->getEmail())
            ->setBody(
                $this->container->get('templating')->render(
                    'Emails/confirm_transaction.html.twig',
                    array(
                        'distribution' => $assistance->getName(),
                        'amount' => $amountToSend . ' ' . $commodity->getUnit(),
                        'number' => $numberOfBeneficiaries,
                        'date' => new \DateTime(),
                        'email' => $user->getEmail(),
                        'code' => $code
                    )
                ),
                'text/html'
            );

        $this->container->get('mailer')->send($message);
        $this->logger->error("Code for verify assistance was sent to ".$user->getEmail(), [$assistance]);
    }

    /**
     * Send logs by email
     * @param User $user
     * @param Assistance $assistance
     */
    public function sendLogsEmail(User $user, Assistance $assistance)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $file_record = $dir_var . '/record_' . $assistance->getId() . '.csv';

        if (is_file($file_record) && file_get_contents($file_record)) {
            $message = (new \Swift_Message('Transaction logs for ' . $assistance->getName()))
                ->setFrom($this->email)
                ->setTo($user->getEmail())
                ->setBody(
                    $this->container->get('templating')->render(
                        'Emails/logs_transaction.html.twig',
                        array(
                            'user' => $user->getUsername(),
                            'distribution' => $assistance->getName()
                        )
                    ),
                    'text/html'
                );
            $message->attach(\Swift_Attachment::fromPath($dir_root . '/../var/data/record_' . $assistance->getId() . '.csv')->setFilename('logsTransaction.csv'));
        } else {
            $message = (new \Swift_Message('Transaction logs for ' . $assistance->getName()))
                ->setFrom($this->email)
                ->setTo($user->getEmail())
                ->setBody(
                    $this->container->get('templating')->render(
                        'Emails/no_logs_transaction.html.twig',
                        array(
                            'user' => $user->getUsername(),
                            'distribution' => $assistance->getName()
                        )
                    ),
                    'text/html'
                );
        }

        $this->container->get('mailer')->send($message);
    }

    /**
     * Verify confirmation code
     * @param  int $code
     * @param User $user
     * @param Assistance $assistance
     * @return boolean
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function verifyCode(int $code, User $user, Assistance $assistance)
    {
        $cache = new FilesystemCache();

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
     * Update transaction status
     * @param $countryISO3
     * @param  Assistance $assistance
     * @return AssistanceBeneficiary[]
     * @throws \Exception
     */
    public function updateTransactionStatus(string $countryISO3, Assistance $assistance): array
    {
        $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);

        return $this->financialProvider->updateStatusDistribution($assistance);
    }

    /**
     * Test API connection
     * @param  string $countryISO3
     * @param  Assistance $assistance
     * @return string
     * @throws \Exception
     */
    public function testConnection(string $countryISO3, Assistance $assistance)
    {
        $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);

        return $this->financialProvider->getToken($assistance);
    }

    /**
     * Test API connection
     * @param User $user
     * @param  Assistance $assistance
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checkProgression(User $user, Assistance $assistance)
    {
        $cache = new FilesystemCache();
        if ($cache->has($user->getEmail() . '-progression-' . $assistance->getId())) {
            return $cache->get($user->getEmail() . '-progression-' . $assistance->getId());
        } else {
            return 0;
        }
    }

    /**
     * @param string $country
     * @return mixed
     */
    public function getFinancialCredential(string $country)
    {
        $FP = $this->em->getRepository(DefaultFinancialProvider::class)->findByCountry($country);

        return $FP;
    }

    /**
     * @param array $data
     * @return FinancialProvider
     */
    public function updateFinancialCredential(array $data)
    {
        $FP = $this->em->getRepository(DefaultFinancialProvider::class)->findOneByCountry($data['__country']);

        if ($FP) {
            $FP->setUsername($data['username'])
                ->setPassword($data['password'])
                ->setCountry($data['__country']);

            $this->em->persist($FP);
            $this->em->flush();
        }

        return $FP;
    }

    /**
     * @param Assistance $assistance
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(Assistance $assistance, string $type)
    {
        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);

        $exportableTable = array();
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

            $transactionAdditionalInfo = array(
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
            );

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
                    $phoneNumbers[] = $phone->getPrefix().' '.$phone->getNumber();
                }
                $transactionAdditionalInfo["Phone number"] = implode(', ', $phoneNumbers);
            }
            if (null !== $transaction && $transaction->getPickupDate()) {
                $transactionAdditionalInfo["Pickup Date"] = $transaction->getPickupDate()->format('d-m-Y H:i:s');
            }

            array_push($exportableTable,
                array_merge($commonFields, $transactionAdditionalInfo)
            );
        }

        return $this->container->get('export_csv_service')->export($exportableTable, 'transaction', $type);
    }
}
