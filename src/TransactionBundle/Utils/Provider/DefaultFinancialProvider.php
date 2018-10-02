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
    protected $em;

    /**
     * @var
     */
    protected $url;
    
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
     * @throws \Exception       
     */
    public function sendMoneyToOne(string $phoneNumber, DistributionBeneficiary $distributionBeneficiary)
    {
        throw new \Exception("You need to define the financial provider for the country.");
    }
    
    /**
     * Send money to all beneficiaries
     * @param  array  $beneficiaries 
     * @return array                
     */
    public function sendMoneyToAll(array $distributionBeneficiaries)
    {
        $response = array(
            'sent'       => array(),
            'failure'       => array(),
            'no_mobile'     => array(),
            'already_sent'  => array()
        );
        
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            $phoneNumber = null;
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == 'mobile') {
                    $phoneNumber = $phone->getNumber();
                    break;
                }
            }
            
            if ($phoneNumber) {
                $transaction = $distributionBeneficiary->getTransaction();
                if ($transaction && $transaction->getTransactionStatus()) {
                    array_push($response['already_sent'], $beneficiary);
                } else {
                    try {
                        $sent = $this->sendMoneyToOne($phoneNumber, $distributionBeneficiary);
                        if (property_exists($sent, 'error_code')) {
                            array_push($response['failure'], $beneficiary);
                        } else {
                            array_push($response['sent'], $beneficiary);
                        }
                    } catch (Exception $e) {
                        throw $e;
                    }
                }
            } else {
                array_push($response['no_mobile'], $beneficiary);
            }
        }
        
        return $response;
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
    public function createOrUpdateTransaction(
        DistributionBeneficiary $distributionBeneficiary,
        string $transactionId,
        \DateTime $dateSent,
        string $amountSent,
        int $transactionStatus,
        string $message = null,
        Transaction $transaction = null)
    {
        if (!$transaction) {
            $transaction = new Transaction();
        }
        $transaction->setDistributionBeneficiary($distributionBeneficiary);
        $transaction->setDateSent($dateSent);
        $transaction->setTransactionId($transactionId);
        $transaction->setAmountSent($amountSent);
        $transaction->setTransactionStatus($transactionStatus);
        $transaction->setMessage($message);

        $this->em->persist($transaction);
        $this->em->flush();
        
        return $transaction;
    }

}