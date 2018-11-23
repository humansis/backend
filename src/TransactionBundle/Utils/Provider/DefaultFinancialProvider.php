<?php

namespace TransactionBundle\Utils\Provider;

use Doctrine\ORM\EntityManagerInterface;

use TransactionBundle\Entity\Transaction;
use TransactionBundle\TransactionBundle;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\DistributionBeneficiary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultFinancialProvider
 * @package TransactionBundle\Utils\Provider
 */
abstract class DefaultFinancialProvider {

    /** @var EntityManagerInterface $em */
    protected $em;
    
    /** @var ContainerInterface $container */
    protected $container;

    /** @var string $url */
    protected $url;
    
    /**
     * DefaultFinancialProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
     public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
     {
         $this->em = $entityManager;
         $this->container = $container;
     }
    
    /**
     * Send request to financial API
     * @param DistributionData $distributionData
     * @param  string $type    type of the request ("GET", "POST", etc.)
     * @param  string $route   url of the request
     * @param  array  $headers headers of the request (optional)
     * @param  array  $body    body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(DistributionData $distributionData, string $type, string $route, array $body = array()) {
        throw new \Exception("You need to define the financial provider for the country.");
    }
    
    /**
     * Send money to one beneficiary
     * @param  string                  $phoneNumber
     * @param  DistributionBeneficiary $distributionBeneficiary
     * @param  float                   $amount
     * @param  string                  $currency
     * @param  Transaction             $transaction
     * @return Transaction
     * @throws \Exception       
     */
    public function sendMoneyToOne(
        string $phoneNumber,
        DistributionBeneficiary $distributionBeneficiary,
        float $amount,
        string $currency)
    {
        throw new \Exception("You need to define the financial provider for the country.");
    }
    
    /**
     * Send money to all beneficiaries
     * @param  array  $beneficiaries 
     * @param  float  $amount
     * @param  string $currency
     * @return array                
     */
    public function sendMoneyToAll(DistributionData $distributionData, float $amount, string $currency, string $from)
    {    
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['distributionData' => $distributionData]);
        
        // Record transaction
        $data = "\n\n==============="
                . "\nUSER SENDING MONEY: "
                . $from
                . "\nDATE: "
                . (new \DateTime())->format('Y-m-d H:i:s')
                . "\nCALLS TO EXTERNAL API: ";
        $this->recordTransaction($distributionData, $data);
        
        $response = array(
            'sent'       => array(),
            'failure'       => array(),
            'no_mobile'     => array(),
            'already_sent'  => array()
        );
        
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            
            $transactions = $distributionBeneficiary->getTransactions();
            if (! $transactions->isEmpty()) {
                // if this beneficiary already has transactions
                // filter out the one that is a success (if it exists)
                $transactions = $transactions->filter(
                    function($transaction) {
                        return $transaction->getTransactionStatus() === 1;
                    }
                );
            }

            $phoneNumber = null;
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == 'mobile' || $phone->getType() === 'Mobile') {
                    $phoneNumber = $phone->getNumber();
                    break;
                }
            }

            if ($phoneNumber) {
                // if a successful transaction already exists
                if (! $transactions->isEmpty()) {
                    array_push($response['already_sent'], $distributionBeneficiary);
                } else {
                    try {
                        $transaction = $this->sendMoneyToOne($phoneNumber, $distributionBeneficiary, $amount, $currency);
                        if ($transaction->getTransactionStatus() === 0) {
                            array_push($response['failure'], $distributionBeneficiary);
                        } else {
                            array_push($response['sent'], $distributionBeneficiary);
                        }
                    } catch (Exception $e) {
                        $this->createTransaction($distributionBeneficiary, '', new \DateTime(), 0, 2, $e->getMessage());
                        array_push($response['failure'], $distributionBeneficiary);
                    }
                }
            } else {
                $this->createTransaction($distributionBeneficiary, '', new \DateTime(), 0, 2, "No Phone");
                array_push($response['no_mobile'], $distributionBeneficiary);
            }
        }
        
        // Record transaction
        $data = "\nTRANSACTION DONE FOR"
                . "\nUSER SENDING MONEY: "
                . $from
                . "\nDATE: "
                . (new \DateTime())->format('Y-m-d H:i:s')
                . "\n===============";
        $this->recordTransaction($distributionData, $data);
        
        return $response;
    }
    
    /**
     * Update distribution status (check if money has been picked up)
     * @param  DistributionData $distributionData
     * @return array                            
     */
    public function updateStatusDistribution(DistributionData $distributionData)
    {
        $response = array();

        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['distributionData' => $distributionData]);
        
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $successfulTransaction = $this->em->getRepository(Transaction::class)->findOneBy(
                [
                    'distributionBeneficiary' => $distributionBeneficiary,
                    'transactionStatus'       => 1
                ]
            );
            if ($successfulTransaction) {
                try {
                    $this->updateStatusTransaction($successfulTransaction); 
                    array_push($response, $distributionBeneficiary);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
    }
    
    /**
     * Create transaction
     * @param  DistributionBeneficiary $distributionBeneficiary 
     * @param  string                  $transactionId           
     * @param  float                   $amountSent              
     * @param  int                     $transactionStatus       
     * @param  string                  $message                 
     * @return Transaction                                           
     */
    public function createTransaction(
        DistributionBeneficiary $distributionBeneficiary,
        string $transactionId,
        \DateTime $dateSent,
        string $amountSent,
        int $transactionStatus,
        string $message = null)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        
        $transaction = new Transaction();
        $transaction->setDistributionBeneficiary($distributionBeneficiary);
        $transaction->setDateSent($dateSent);
        $transaction->setTransactionId($transactionId);
        $transaction->setAmountSent($amountSent);
        $transaction->setTransactionStatus($transactionStatus);
        $transaction->setMessage($message);
        $transaction->setSentBy($user);
        
        $distributionBeneficiary->addTransaction($transaction);
        $user->addTransaction($transaction);
        
        $this->em->persist($transaction);
        $this->em->merge($distributionBeneficiary);
        $this->em->merge($user);
        $this->em->flush();
        
        return $transaction;
    }
    
    /**
     * Save transaction record in file
     * @param  DistributionData $distributionData
     * @param  string           $data           
     * @return void                           
     */
    public function recordTransaction(DistributionData $distributionData, string $data) 
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) mkdir($dir_var);
        $file_record = $dir_var . '/record_' . $distributionData->getId();
        file_put_contents($file_record, $data, FILE_APPEND);
    }

}