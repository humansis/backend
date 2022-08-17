<?php

namespace Tests\Controller\WebApp\Smartcards;

use Entity\Beneficiary;
use Exception;
use Tests\BMSServiceTestCase;
use Entity\Smartcard;
use Entity\Vendor;

class AnalyticsControllerTest extends BMSServiceTestCase
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

    public function testBeneficiaryAnalytics()
    {
        $beneficiaryId = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id'=>'asc'])->getId();

        $this->request('GET', '/api/basic/web-app/v1/smartcard/analytics/beneficiary/'.$beneficiaryId);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSmartcardAnalytics()
    {
        $smartcardId = $this->em->getRepository(Smartcard::class)->findOneBy([], ['id'=>'asc'])->getId();

        $this->request('GET', '/api/basic/web-app/v1/smartcard/analytics/smartcard/'.$smartcardId);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSmartcardsAnalytics()
    {
        $smartcardSerialNumber = $this->em->getRepository(Smartcard::class)->findOneBy([], ['id'=>'asc'])->getSerialNumber();

        $this->request('GET', '/api/basic/web-app/v1/smartcard/analytics/smartcards/'.$smartcardSerialNumber);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testVendorAnalytics()
    {
        $vendorId = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc'])->getId();

        $this->request('GET', '/api/basic/web-app/v1/smartcard/analytics/vendor/'.$vendorId);

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
