<?php

namespace TransactionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;

class KHMTransactionService extends DefaultTransactionService {

    /** @var EntityManagerInterface $em */
    private $em;
    
    private $token;
    
    private $url = "https://stageonline.wingmoney.com:8443/RestEngine";

    /**
     * DefaultTransactionService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Connect to API to transfer money
     * @return string token
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
        return $this->sendRequest("POST", $route, array(), $body);
    }
    
    /**
     * Send request to WING API for Cambodia
     * @param  string $type    type of the request ("GET", "POST", etc.)
     * @param  string $route   url of the request
     * @param  array  $headers headers of the request (optional)
     * @param  array  $body    body of the request (optional)
     * @return string response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route, array $headers = array(), array $body = array()) {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => "8443",
          CURLOPT_URL            => $this->url . $route,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => "POST",
          CURLOPT_POSTFIELDS     => http_build_query($body),
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
            dump($response);
            dump($result);
            return $result;
        }
    }

}