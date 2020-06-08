<?php

namespace VoucherBundle\Tests\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Smartcard;

class SmartcardControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        if (!$smartcard) {
            $beneficiary = $this->em->getRepository(Beneficiary::class)->find(1);
            $smartcard = new Smartcard('1234ABC', $beneficiary, new \DateTime('now'));
            $smartcard->setState(Smartcard::STATE_ACTIVE);
            $this->em->persist($smartcard);
            $this->em->flush();
        }
    }

    protected function tearDown()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $this->em->remove($smartcard);
        $this->em->flush();

        parent::tearDown();
    }

    public function testRegisterSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1111111');
        if ($smartcard) {
            $this->em->remove($smartcard);
            $this->em->flush();
        }

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1111111',
            'beneficiaryId' => 1, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $smartcard);
        $this->assertArrayHasKey('serialNumber', $smartcard);
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertEquals('1111111', $smartcard['serialNumber']);
        $this->assertEquals(Smartcard::STATE_ACTIVE, $smartcard['state']);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1111111');
        $this->em->remove($smartcard);
        $this->em->flush();
    }

    public function testRegisterDuplicateSmartcard()
    {
        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1234ABC',
            'beneficiaryId' => 1, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isClientError(), 'Request should failed: '.$this->client->getResponse()->getContent());
    }

    public function testDepositToSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');

        $this->request('PATCH', '/api/wsse/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 255.25,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('value', $smartcard);
        $this->assertEquals(255.25, $smartcard['value'], 0.0001);
    }

    public function testDepositToFrozenSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_FROZEN);
        $smartcard->addDeposit(1000, new \DateTime('now'));
        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0200',
        ]);

        $this->assertTrue($this->client->getResponse()->isClientError(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testDepositToInactiveSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_INACTIVE);
        $smartcard->addDeposit(1000, new \DateTime('now'));
        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0200',
        ]);

        $this->assertTrue($this->client->getResponse()->isClientError(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchase()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->addDeposit(600, new \DateTime('now'));
        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$smartcard->getSerialNumber().'/purchase', [
            'value' => 300.25,
            'quantity' => 1.2,
            'productId' => 1, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('value', $smartcard);
        $this->assertEquals(299.75, $smartcard['value'], 0.0001);
    }

    /**
     * It must be allow to make payment from blocked or empty smartcard - due to latency between payment and write to system.
     */
    public function testPurchaseFromEmptySmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_INACTIVE);
        $smartcard->addDeposit(100, new \DateTime('now'));
        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$smartcard->getSerialNumber().'/purchase', [
            'value' => 400,
            'quantity' => 1.2,
            'productId' => 1, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testChangeStateToFrozen()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => Smartcard::STATE_FROZEN,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertEquals(Smartcard::STATE_FROZEN, $smartcard['state']);
    }

    public function testChangeStateBackToActive()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_FROZEN);
        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => Smartcard::STATE_ACTIVE,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertEquals(Smartcard::STATE_ACTIVE, $smartcard['state']);
    }

    public function testChangeStateToInactive()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => Smartcard::STATE_INACTIVE,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertEquals(Smartcard::STATE_INACTIVE, $smartcard['state']);
    }
}
