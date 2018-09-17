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
        $query = array(
            'username' => 'thirdParty',
            'password' => '@pIw!n9$#',
            'client_id' => 'third_party',
            'client_secret' => '16681c9ff419d8ecc7cfe479eb02a7a'
        );
        dump($query);
        $response = Unirest\Request::post($this->url . "/oauth/token", null, $query);
        dump($response);
        return $response->body;
    }

}