<?php

namespace BeneficiaryBundle\Utils\ImportProvider;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DefaultApiProvider
 * @package BeneficiaryBundle\Utils\ImportProvider
 */
abstract class DefaultApiProvider {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var
     */
    private $url;

    /**
     * DefaultApiProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Send request to API
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route) {
        throw new \Exception("You need to define the financial provider for the country.");
    }
}