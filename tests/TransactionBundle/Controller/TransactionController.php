<?php


namespace Tests\UserBundle\Controller;


use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
use Tests\DistributionBundle\Controller\DistributionControllerTest;


class TransactionController extends BMSServiceTestCase
{

    /** @var string $username */
    private $username = "TESTER_PHPUNIT@gmail.com";


    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }


}