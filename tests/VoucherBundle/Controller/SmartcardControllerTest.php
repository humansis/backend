<?php

namespace VoucherBundle\Tests\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;

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
            $smartcard = new Smartcard('1234ABC', new \DateTime('now'));
            $smartcard->setBeneficiary($this->em->getRepository(Beneficiary::class)->findOneBy([]));
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

        $bnfId = $this->em->getRepository(Beneficiary::class)->findOneBy([])->getId();

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1111111',
            'beneficiaryId' => $bnfId, // @todo replace for fixture
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
        $bnfId = $this->em->getRepository(Beneficiary::class)->findOneBy([])->getId();

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1234ABC',
            'beneficiaryId' => $bnfId, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request should failed: '.$this->client->getResponse()->getContent());
    }

    public function testDepositToSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 255.25,
            'distributionId' => 1, // todo change to fixtures
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('value', $smartcard);
        $this->assertEquals(255.25, $smartcard['value'], 0.0001);
    }

    public function testDepositToInactiveSmartcard()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([]);
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy([]);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_INACTIVE);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $distributionBeneficiary, 1000, new \DateTime('now')));

        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0200',
        ]);

        $this->assertTrue($this->client->getResponse()->isClientError(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchase()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([]);
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy([]);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $distributionBeneficiary, 600, new \DateTime('now')));

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 300.25,
                    'quantity' => 1.2,
                ],
            ],
            'vendorId' => 1,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

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
        $depositor = $this->em->getRepository(User::class)->findOneBy([]);
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy([]);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_INACTIVE);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $distributionBeneficiary, 100, new \DateTime('now')));

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 400,
                    'quantity' => 1.2,
                ],
            ],
            'vendorId' => 1,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchaseShouldBeAllowedForNonexistentSmartcard()
    {
        $nonexistentSmarcard = '0123456789';

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 400,
                    'quantity' => 1.2,
                ],
            ],
            'vendorId' => 1,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber($nonexistentSmarcard);

        $this->assertNotNull($smartcard, 'Smartcard must be registered to system');
        $this->assertTrue($smartcard->isSuspicious(), 'Smartcard registered by purchase must be suspected');
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
