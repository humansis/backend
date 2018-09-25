<?php

namespace TransactionBundle\Utils\Provider;

use Doctrine\ORM\EntityManagerInterface;

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
     * @param  $string      $beneficiary
     * @return object       transaction
     */
    public function sendMoneyToOne(string $phone_number)
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
            'sentTo'        => array(),
            'noMobilePhone' => array(),
            'error'         => array()
        );
        
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            foreach ($beneficiary->getPhones() as $phone) {
                if ($phone->getType() == "mobile") {
                    $phoneNumber = $phone->getNumber();
                    break;
                }
            }
            
            if ($phoneNumber) {
                try {
                    $sent = $this->sendMoneyToOne($phoneNumber);
                    array_push($response['sentTo'], $sent);
                } catch (Exception $e) {
                    array_psuh($response['error'], $e);
                }
            } else {
                array_push($response['noMobilePhone'], $beneficiary);
            }
        }
    }

}