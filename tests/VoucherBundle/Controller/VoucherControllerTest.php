<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\Entity\Vendor;

class VoucherControllerTest extends BMSServiceTestCase
{
    /** @var Booklet */
    protected $booklet;

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

        // We create a new booklet if we have not already created one
        $randomBookletCode = join('-', [rand(0, 999), rand(0, 999), rand(0, 999)]);
        $this->booklet = new Booklet();
        $this->booklet->setCode('test*' . $randomBookletCode)
            ->setNumberVouchers(0)
            ->setStatus(0)
            ->setCurrency('USD');

        $this->em->persist($this->booklet);
        $this->em->flush();
    }

    /**
     * @throws \Exception
     */
    public function testCreateVoucher()
    {
        $body = [
            'number_vouchers' => 3,
            'bookletCode' => 'test*' . $this->booklet->getCode(),
            'currency' => 'USD',
            'bookletID' => NULL,
            'values' => [1, 2, 3],
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);


        $body['bookletID'] = $this->booklet->getId();

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/vouchers', $body);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        // Delete the booklet that was created for the test
        // $this->em->remove($booklet);
        // $this->em->flush();

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $voucher);
        $this->assertArrayHasKey('booklet', $voucher);
        return $voucher;
    }

    /**
     * @throws \Exception
     */
    public function testGetAllVouchers()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/vouchers');
        $vouchers = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($vouchers)) {
            $voucher = $vouchers[0];

            $this->assertArrayHasKey('code', $voucher);
            $this->assertArrayHasKey('booklet', $voucher);
            $this->assertArrayHasKey('id', $voucher);
        } else {
            $this->markTestIncomplete("You currently don't have any vouchers in your database.");
        }

        return $vouchers;
    }


    /**
     * @depends testCreateVoucher
     * @param $newVoucher
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetVoucher($newVoucher)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);


        $crawler = $this->request('GET', '/api/wsse/vouchers/' . $newVoucher['id']);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $voucher);
        $this->assertArrayHasKey('code', $voucher);
        $this->assertArrayHasKey('booklet', $voucher);

        return $voucher;
    }

    /**
     * @depends testCreateVoucher
     * @param $newVoucher
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUseVoucher($newVoucher)
    {
        $vendor = $this->em->getRepository(Vendor::class)->findOneByName('vendor');
        $vendorId = $vendor->getId();
        $body = [
            [
                "id" => $newVoucher['id'],
                "vendorId" => $vendorId,
                "productIds" => [1, 2],
                "used_at" => "2019-02-20 09:00:00"
            ]
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Using a fake header or else a country is gonna be put in the body
        $crawler = $this->request('POST', '/api/wsse/vouchers/scanned', $body, [], ['fakeHeader']);
        $newVoucherReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $voucherSearch = $this->em->getRepository(Voucher::class)->find($newVoucherReceived[0]['id']);
        $this->assertTrue($voucherSearch->getUsedAt() !== null);

        return $newVoucherReceived;
    }

    /**
     * @depends testGetVoucher
     *
     * @param $voucherToDelete
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteFromDatabase($voucherToDelete)
    {
        // Get the previous voucher because the last one was set as used so deletion won't work
        $voucherToDelete['id']--;

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/vouchers/' . $voucherToDelete['id']);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue($success);
    }


    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteBatchVouchers()
    {
        $bookletId = $this->booklet->getId();
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/vouchers/delete_batch/' . $bookletId);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue($success);

        $this->em->remove($this->booklet);
        $this->em->flush();
    }


}
