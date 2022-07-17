<?php
namespace VoucherBundle\Tests\Controller;

use NewApiBundle\Entity\Beneficiary;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Assistance;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;

class BookletControllerTest extends BMSServiceTestCase
{
    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();
        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testDeactivateBooklets()
    {
        $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets('KHM');

        $body = ['bookletCodes' => [$booklets[0]->getCode()]];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        $crawler = $this->request('POST', '/api/wsse/deactivate-booklets', $body);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return $reponse;
    }

    /**
     * @throws \Exception
     */
    public function testDeactivateBooklet()
    {
        $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets('KHM');

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        $crawler = $this->request('DELETE', '/api/wsse/deactivate-booklets/'.$booklets[0]->getId());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return $response;
    }

    /**
     * @throws \Exception
     */
    public function testGetDeactivatedBooklets()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/deactivated-booklets');
        $booklets = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($booklets)) {
            $booklet = $booklets[0];

            $this->assertArrayHasKey('currency', $booklet);
            $this->assertArrayHasKey('vouchers', $booklet);
            $this->assertArrayHasKey('distribution_beneficiary', $booklet);
            $this->assertArrayHasKey('number_vouchers', $booklet);
        } else {
            $this->markTestIncomplete("You currently don't have any deactivated booklets in your database.");
        }

        return $booklets[0];
    }

    /**
     * @throws \Exception
     */
    public function testGetProtectedBooklets()
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy(['password' => 'secret-password'], ['id' => 'asc']);

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/protected-booklets');
        $booklets = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($booklets)) {
            $this->assertEquals($booklets[0][$booklet->getCode()], 'secret-password');
        } else {
            $this->markTestIncomplete("You currently don't have any deactivated booklets in your database.");
        }

        return $booklets;
    }

    /**
     * @throws \Exception
     */
    public function testAssignBooklet()
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy(['status' => Booklet::UNASSIGNED], ['id' => 'desc']);
        $distribution = $this->em->getRepository(Assistance::class)->findOneBy([
            'project'=>$booklet->getProject(),
        ], ['name' => 'desc']);
        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findAssignable($distribution)[0];
        $beneficiary = $assistanceBeneficiary->getBeneficiary();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = [
            'code' => $booklet->getCode(),
        ];

        // Second step
        $crawler = $this->request('POST', '/api/wsse/booklets/assign/'.$distribution->getId().'/'.$beneficiary->getId(), $body);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return $response;
    }
}
