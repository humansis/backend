<?php

namespace TransactionBundle\Utils\Provider;

use Doctrine\ORM\EntityManagerInterface;

use DistributionBundle\Entity\DistributionBeneficiary;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\TransactionBundle;

/**
 * Class KHMFinancialProvider
 * @package TransactionBundle\Utils\Provider
 */
class KHMFinancialProvider extends DefaultFinancialProvider {

    /** @var EntityManagerInterface $em */
    protected $em;

    /**
     * @var string
     */
    protected $url = "https://stageonline.wingmoney.com:8443/RestEngine";
    /**
     * @var string
     */
    private $token;
    /**
     * @var \DateTime
     */
    private $lastTokenDate;
    
    private $transaction;

    /**
     * DefaultFinancialProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Get token to connect to API
     * @return object token
     * @throws \Exception
     */
    public function getToken()
    {
        $route = "/oauth/token";
        $body = array(
            "username"      => "thirdParty",
            "password"      => "ba0228f6e48ba7942d79e2b44e6072ee",
            "grant_type"    => "password",
            "client_id"     => "third_party",
            "client_secret" => "16681c9ff419d8ecc7cfe479eb02a7a",
            "scope"         => "trust"
        );
        
        try {
            $this->token = $this->sendRequest("POST", $route, $body);
            $this->lastTokenDate = new \DateTime();
            return $this->token;
        } catch (Exception $e) {
            throw $e;
        }
        
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
        string $currency,
        $transaction = null)
    {
        $route = "/api/v1/sendmoney/nonwing/commit";
        $body = array(
            "amount"          => $amount,
            "currency"        => $currency,
            "sender_msisdn"   => "012249184",
            "receiver_msisdn" => $phoneNumber,
            "sms_to"          => "PAYEE"
        );
        
        try {
            $sent = $this->sendRequest("POST", $route, $body);
            dump($sent);
            if (property_exists($sent, 'error_code')) {
                $transaction = $this->createOrUpdateTransaction(
                    $distributionBeneficiary, 
                    '',
                    new \DateTime(),
                    50,
                    0,
                    $sent->message ?: '',
                    $transaction);
                
                return $transaction;
            }
        } catch (Exception $e) {
            throw $e;
        }
        
        try {
            $response = $this->getStatus($sent->transaction_id);
        } catch (\Exception $e) {
            throw $e;
        }
        
        $transaction = $this->createOrUpdateTransaction(
            $distributionBeneficiary, 
            $response->transaction_id,
            new \DateTime(),
            $response->amount,
            $response->transaction_status === 'Success' ? 1 : 0,
            property_exists($response, 'message') ? $response->message : '',
            $transaction);
        
        return $transaction;
    }
    
    /**
     * Get status of transaction
     * @param  string $transaction_id 
     * @return object                 
     */
    public function getStatus(string $transaction_id)
    {
        $route = "/api/v1/sendmoney/nonwing/txn_inquiry";
        $body = array(
            "transaction_id" => $transaction_id
        );
        
        try {
            $sent = $this->sendRequest("POST", $route, $body);
        } catch (Exception $e) {
            throw $e;
        }    
        return $sent;
    }

    /**
     * Send request to WING API for Cambodia
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @param  array $body body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route, array $body = array()) {
        $curl = curl_init();
        
        $headers = array();
        
        // Not authentication request
        if(!preg_match('/\/oauth\/token/', $route)) {
            if (!$this->lastTokenDate ||
            (new \DateTime())->getTimestamp() - $this->lastTokenDate->getTimestamp() > $this->token->expires_in) {
                $this->getToken();
            }
            array_push($headers, "Authorization: Bearer " . $this->token->access_token, "Content-type: application/json");
            $body = json_encode((object) $body);
        }
        // Authentication request
        else {
            $body = http_build_query($body); // Pass body as url-encoded string
        }
                
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => "8443",
          CURLOPT_URL            => $this->url . $route,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => $type,
          CURLOPT_POSTFIELDS     => $body,
          CURLOPT_HTTPHEADER     => $headers,
          CURLOPT_FAILONERROR    => true
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
    
        if ($err) {
            throw new \Exception($err);
        } else {
            $result = json_decode($response);
            return $result;
        }
    }

}