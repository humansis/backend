<?php

namespace TransactionBundle\Utils;
use Doctrine\ORM\EntityManagerInterface;

abstract class DefaultTransactionService {

    /** @var EntityManagerInterface $em */
    private $em;
    
    private $url;

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
     * @return [type] [description]
     */
    public function connect()
    {
        
    }

}