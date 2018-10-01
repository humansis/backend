<?php

namespace TransactionBundle\Utils\Provider;

use Doctrine\ORM\EntityManagerInterface;

use TransactionBundle\Entity\Transaction;
use DistributionBundle\Entity\DistributionBeneficiary;

/**
 * Class DefaultFinancialProvider
 * @package TransactionBundle\Utils\Provider
 */
abstract class DefaultFinancialProvider {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var
     */
    private $url;

    /**
     * DefaultFinancialProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
    
    /**
     * Send request to financial API
     * @param  string $type    type of the request ("GET", "POST", etc.)
     * @param  string $route   url of the request
     * @param  array  $headers headers of the request (optional)
     * @param  array  $body    body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route, array $body = array()) {
        throw new \Exception("You need to define the financial provider for the country.");
    }
    
    /**
     * Send money to one beneficiary
     * @param  string                  $phoneNumber
     * @param  DistributionBeneficiary $distributionBeneficiary
     * @return Transaction       
     */
    public function sendMoneyToOne(string $phoneNumber, DistributionBeneficiary $distributionBeneficiary)
    {
        return null;
    }
    
    /**
     * Send money to all beneficiaries
     * @param  array  $beneficiaries 
     * @return array                
     */
    public function sendMoneyToAll(array $distributionBeneficiaries)
    {
        $response = array(
            'success'       => array(),
            'failure'       => array(),
            'no_mobile'     => array(),
            'already_sent'  => array()
        );
        
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == 'mobile') {
                    $phoneNumber = $phone->getNumber();
                    break;
                }
            }
            
            if ($phoneNumber) {
                $transaction = $distributionBeneficiary->getTransaction();
                if ($transaction && $transaction->getTransactionStatus()) {
                    array_push(response['already_sent'], $distributionBeneficiary);
                } else {
                    try {
                        $sent = $this->sendMoneyToOne($phoneNumber, $distributionBeneficiary);
                        array_push($response['sent'], $distributionBeneficiary);
                    } catch (Exception $e) {
                        return new \Exception($e);
                    }
                }
            } else {
                array_push($response['no_mobile'], $distributionBeneficiary);
            }
        }
    }
    
    /**
     * Create transaction
     * @param  DistributionBeneficiary $distributionBeneficiary 
     * @param  string                  $transactionId           
     * @param  float                   $amountSent              
     * @param  int                     $transactionStatus       
     * @param  string | null           $message                 
     * @return Transaction                                           
     */
    public function createOrUpdateTransaction(
        DistributionBeneficiary $distributionBeneficiary,
        string $transactionId,
        string $amountSent,
        int $transactionStatus,
        string $message = null,
        Transaction $transaction = null)
    {
        if (!$transaction) {
            $transaction = new Transaction();
        }
        $transaction->setDistributionBeneficiary($distributionBeneficiary);
        $transaction->setTransactionId($transactionId);
        $transaction->setAmountSent($amountSent);
        $transaction->setTransactionStatus($transactionStatus);
        $transaction->setMessage($message);
        
        $this->em->persist($transaction);
        $this->em->flush();
        
        return $transaction;
    }

}