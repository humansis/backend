<?php

namespace TransactionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use \Unirest as Unirest;

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
     */
    public function getToken()
    {
        // $query = array(
        //     'username'      => 'thirdParty',
        //     'password'      => 'ba0228f6e48ba7942d79e2b44e6072ee',
        //     'grant_type'    => 'password',
        //     'client_id'     => 'third_party',
        //     'client_secret' => '16681c9ff419d8ecc7cfe479eb02a7a',
        //     'scope'         => 'trust'
        // );
        // 
        // // $method = 'aes256';
        // // $key = hash('sha256', 'HG58YZ3CR9');
        // // $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        // // dump($method, $key);
        // // $encrypted = base64_encode(openssl_encrypt('wingmoney', $method, $key, OPENSSL_RAW_DATA, $iv));
        // // dump($encrypted);
        // // $decrypted = openssl_decrypt(base64_decode('TsvWLCay+F+erP167XyzBw=='), $method, $key, OPENSSL_RAW_DATA, $iv);
        // // dump($decrypted);
        // // 
        // $response = Unirest\Request::post($this->url . "/oauth/token", null, $query);
        // dump($response);
        // return $response->body;
         
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_PORT => "9443",
          CURLOPT_URL => "https://stageonline.wingmoney.com:8443/RestEngine/oauth/token",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "username=thirdParty&password=ba0228f6e48ba7942d79e2b44e6072ee&grant_type=password&client_id=third_party&client_secret=16681c9ff419d8ecc7cfe479eb02a7a&scope=trust",
          CURLOPT_HTTPHEADER => array(
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        dump($response);
        dump($err);

        curl_close($curl);

        if ($err) {
          return $err;
        } else {
          return $response;
        }
    }

}