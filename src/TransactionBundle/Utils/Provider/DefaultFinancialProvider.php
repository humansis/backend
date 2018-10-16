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
            $transaction = $distributionBeneficiary->getTransaction();
            $phoneNumber = null;
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == 'mobile') {
                    $phoneNumber = $phone->getNumber();
                    break;
                }
            }

            if ($phoneNumber) {
                if ($transaction && $transaction->getTransactionStatus() === 1) {
                    array_push($response['already_sent'], $beneficiary);
                } else {
                    try {
                        $transaction = $this->sendMoneyToOne($phoneNumber, $distributionBeneficiary);
                        if ($transaction->getTransactionStatus() === 1) {
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

                if(!$transaction || $transaction->getTransactionStatus() !== 1) {
                    $this->createOrUpdateTransaction($distributionBeneficiary, '', new \DateTime(), 0, 2);
                }
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