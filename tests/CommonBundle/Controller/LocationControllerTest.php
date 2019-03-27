<?php

namespace Tests\CommonBundle\Controller;

use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\UserProject;

class LocationControllerTest extends BMSServiceTestCase
{
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

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAllAdm1()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/location/adm1');
        $adm1 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertTrue(gettype($adm1) == "array");
        $this->assertArrayHasKey('id', $adm1[0]);
        $this->assertArrayHasKey('name', $adm1[0]);
        $this->assertArrayHasKey('country_i_s_o3', $adm1[0]);
        $this->assertArrayHasKey('code', $adm1[0]);

        return $adm1[0];
    }

    /**
     * @depends testGetAllAdm1
     * @param $adm1
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAllAdm2($adm1)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'adm1' => $adm1['id'],
        );

        $crawler = $this->request('POST', '/api/wsse/location/adm2', $body);
        $adm2 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertTrue(gettype($adm2) == "array");
        $this->assertArrayHasKey('id', $adm2[0]);
        $this->assertArrayHasKey('name', $adm2[0]);
        $this->assertArrayHasKey('adm1', $adm2[0]);
        $this->assertArrayHasKey('code', $adm2[0]);

        return $adm2[0];
    }

    /**
     * @depends testGetAllAdm2
     * @param $adm2
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAllAdm3($adm2)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'adm2' => $adm2['id'],
        );

        $crawler = $this->request('POST', '/api/wsse/location/adm3', $body);
        $adm3 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertTrue(gettype($adm3) == "array");
        $this->assertArrayHasKey('id', $adm3[0]);
        $this->assertArrayHasKey('name', $adm3[0]);
        $this->assertArrayHasKey('adm2', $adm3[0]);
        $this->assertArrayHasKey('code', $adm3[0]);

        return $adm3[0];
    }

    /**
     * @depends testGetAllAdm3
     * @param $adm3
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAllAdm4($adm3)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'adm3' => $adm3['id'],
        );

        $crawler = $this->request('POST', '/api/wsse/location/adm4', $body);
        $adm4 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertTrue(gettype($adm4) == "array");

        if (count($adm4) > 0) {
            $this->assertArrayHasKey('id', $adm4[0]);
            $this->assertArrayHasKey('name', $adm4[0]);
            $this->assertArrayHasKey('adm3', $adm4[0]);
            $this->assertArrayHasKey('code', $adm4[0]);
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetCodeUpcomingDistribution()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/location/upcoming_distribution');
        $upcoming = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue(gettype($upcoming) == "array");
    }
}
