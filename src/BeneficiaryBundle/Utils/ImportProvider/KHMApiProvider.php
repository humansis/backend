<?php

namespace BeneficiaryBundle\Utils\ImportProvider;

use Doctrine\ORM\EntityManagerInterface;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Utils\ImportProvider\DefaultApiProvider;

/**
 * Class KHMApiProvider
 * @package BeneficiaryBundle\Utils\ImportProvider
 */
class KHMApiProvider extends DefaultApiProvider {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var string
     */
    private $url = "http://hub.cam-monitoring.info";
    /**
     * @var
     */
    private $token;

    /**
     * KHMApiProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->token = 'K45nDocxQ5sEFfqSWwDm-2DxskYEDYFe';
    }
    /**
     * Get beneficiaries from API
     * @param int $countryCode
     * @return object transaction
     * @throws \Exception
     */
    public function getBeneficiaries(int $countryCode)
    {
        $route = "/api/idpoor8/". $countryCode .".json?email=james.happell%40peopleinneed.cz&token=K45nDocxQ5sEFfqSWwDm-2DxskYEDYFe";
        
        try {
            $sent = $this->sendRequest("GET", $route);
            return $sent;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Send request to WING API for Cambodia
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route) {
        $curl = curl_init();
        
        $headers = array();

        array_push($headers, "Authorization: Basic d2ZwOndmcCMxMjM0NQ==");
                
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => "8383",
          CURLOPT_URL            => $this->url . $route,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => $type,
          CURLOPT_HTTPHEADER     => $headers,
          CURLOPT_FAILONERROR    => true,
          CURLINFO_HEADER_OUT    => true
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