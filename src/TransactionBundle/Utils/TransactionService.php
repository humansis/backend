<?php

namespace TransactionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;
use TransactionBundle\Utils\Provider\DefaultFinancialProvider;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\DistributionBeneficiary;
use UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TransactionService
 * @package TransactionBundle\Utils
 */
class TransactionService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;
    
    /** @var DefaultFinancialProvider $financialProvider */
    private $financialProvider;

    /**
     * TransactionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * Send money to distribution beneficiaries
     * @param  string $countryISO3
     * @param  DistributionData $distributionData
     * @return object 
     * @throws \Exception
     */
    public function sendMoney(string $countryISO3, DistributionData $distributionData, User $user)
    {
        try {            
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw $e;
        }
        
        if ($distributionData->getCommodities()[0]->getModalityType()->getModality()->getName() === "CTP") {
            $amountToSend = $distributionData->getCommodities()[0]->getValue();
            $currencyToSend = $distributionData->getCommodities()[0]->getUnit();
        } else {
            throw new \Exception("The commodity of the distribution does not allow this operation.");
        }
        
        $from = $user->getEmail();
        
        try {            
            return $this->financialProvider->sendMoneyToAll($distributionData, $amountToSend, $currencyToSend, $from);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get the financial provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @return object|Class|DefaultFinancialProvider
     * @throws \Exception
     */
    private function getFinancialProviderForCountry(string $countryISO3)
    {
        $provider = $this->container->get('transaction.' . strtolower($countryISO3) . '_financial_provider');
        
        if (! ($provider instanceof DefaultFinancialProvider)) {
            throw new \Exception("The financial provider for " . $countryISO3 . "is not properly defined");
        }
        return $provider;
    }

    /**
     * Send email to confirm transaction
     * @param  User $user
     * @param  DistributionData $distributionData
     * @param bool $generateCode
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function sendVerifyEmail(User $user, DistributionData $distributionData)
    {
        $code = random_int(100000, 999999);

        $id = $user->getId();
        $cache = new FilesystemCache();
        $cache->set($distributionData->getId() . '-' . $id . '-code_transaction_confirmation', $code);

        $commodity = $distributionData->getCommodities()->get(0);
        $numberOfBeneficiaries = count($distributionData->getDistributionBeneficiaries());
        $amountToSend = $numberOfBeneficiaries * $commodity->getValue();

        dump($code);
        $message = (new \Swift_Message('Confirm transaction for distribution ' . $distributionData->getName()))
            ->setFrom('admin@bmstaging.info')
            ->setTo($user->getEmail())
            ->setBody(
                $this->container->get('templating')->render(
                    'Emails/confirm_transaction.html.twig',
                    array(
                        'distribution' => $distributionData->getName(),
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
    }

    /**
     * Send logs by email
     * @param User $user
     * @param DistributionData $distributionData
     */
    public function sendLogsEmail(User $user, DistributionData $distributionData) {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) mkdir($dir_var);
        $file_record = $dir_var . '/record_' . $distributionData->getId() . '.csv';

        if (is_file($file_record) && file_get_contents($file_record)) {
            $message = (new \Swift_Message('Transaction logs for ' . $distributionData->getName()))
                ->setFrom('admin@bmstaging.info')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->container->get('templating')->render(
                        'Emails/logs_transaction.html.twig',
                        array(
                            'user' => $user->getUsername(),
                            'distribution' => $distributionData->getName()
                        )
                    ),
                    'text/html'
                );
            $message->attach(\Swift_Attachment::fromPath($dir_root . '/../var/data/record_' . $distributionData->getId() . '.csv')->setFilename('logsTransaction.csv'));
        }
        else {
            $message = (new \Swift_Message('Transaction logs for ' . $distributionData->getName()))
                ->setFrom('admin@bmstaging.info')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->container->get('templating')->render(
                        'Emails/no_logs_transaction.html.twig',
                        array(
                            'user' => $user->getUsername(),
                            'distribution' => $distributionData->getName()
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
     * @param DistributionData $distributionData
     * @return boolean
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function verifyCode(int $code, User $user, DistributionData $distributionData)
    {
        $cache = new FilesystemCache();

        $checkedAgainst = '';
        $id = $user->getId();
        if ($cache->has($distributionData->getId() . '-' . $id . '-code_transaction_confirmation'))
            $checkedAgainst = $cache->get($distributionData->getId() . '-' . $id . '-code_transaction_confirmation');

        $result = ($code === intval($checkedAgainst));

        if ($result) {
            $cache->delete($distributionData->getId() . '-' . $id . '-code_transaction_confirmation');
        }
        return $result;
    }

    /**
     * Update transaction status
     * @param $countryISO3
     * @param  DistributionData $distributionData
     * @return array
     * @throws \Exception
     */
    public function updateTransactionStatus(string $countryISO3, DistributionData $distributionData)
    {
        try {
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw $e;
        }
        
        try {
            return $this->financialProvider->updateStatusDistribution($distributionData);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Test API connection
     * @param  string $countryISO3
     * @param  DistributionData $distributionData
     * @return string
     * @throws \Exception
     */
    public function testConnection(string $countryISO3, DistributionData $distributionData)
    {
        try {
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw $e;
        }
        
        try {
            return $this->financialProvider->getToken($distributionData);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Test API connection
     * @param User $user
     * @param  DistributionData $distributionData
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checkProgression(User $user, DistributionData $distributionData)
    {
        $cache = new FilesystemCache();
        if ($cache->has($user->getEmail() . '-progression-' . $distributionData->getId()))
            return $cache->get($user->getEmail() . '-progression-' . $distributionData->getId());
        else
            return 0;

    }

}