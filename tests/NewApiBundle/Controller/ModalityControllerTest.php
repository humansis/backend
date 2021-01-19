<?php

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\Modality;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class ModalityControllerTest extends BMSServiceTestCase
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
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetModalities()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/modalities');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": "*", "data": [{"code": "*", "value": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModalityTypes()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $modality = $em->getRepository(Modality::class)->findBy([])[0];

        $this->request('GET', '/api/basic/modalities/'.$modality->getId().'/types');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": "*", "data": [{"code": "*", "value": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }
}
