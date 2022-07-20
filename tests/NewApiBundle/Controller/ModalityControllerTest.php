<?php

namespace Tests\NewApiBundle\Controller;

use NewApiBundle\Entity\Modality;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class ModalityControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetModalities()
    {
        $this->request('GET', '/api/basic/web-app/v1/modalities');

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
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $modality = $em->getRepository(Modality::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/modalities/'.$modality->getName().'/types');

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
