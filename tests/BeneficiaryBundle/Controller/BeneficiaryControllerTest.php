<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;



class BeneficiaryControllerTest extends BMSServiceTestCase {



    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    public function testGetAllBeneficiaryApi(){

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            "countryCode" => "010201",
            "flush" => false
        ];

        $crawler = $this->request('POST', '/api/wsse/beneficiaries/import/api', $body);
        $listHousehold = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(gettype($listHousehold) == "array");

        return true;
    }
}
