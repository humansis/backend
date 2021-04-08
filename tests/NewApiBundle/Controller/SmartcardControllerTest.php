<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\SmartcardDeposit;

class SmartcardControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testListByAssistanceAndBeneficiary()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var SmartcardDeposit $item */
        $item = self::$container->get('doctrine')->getRepository(SmartcardDeposit::class)->findBy([])[0];
        $assistanceId = $item->getAssistanceBeneficiary()->getAssistance()->getId();
        $beneficiaryId = $item->getAssistanceBeneficiary()->getBeneficiary()->getId();

        $this->request('GET', '/api/basic/assistances/'.$assistanceId.'/beneficiaries/'.$beneficiaryId.'/smartcard-deposits');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
