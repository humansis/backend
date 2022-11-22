<?php

namespace Tests\Controller;

use Entity\Address;
use Entity\HouseholdLocation;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class AddressControllerTest extends BMSServiceTestCase
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
    public function testGetAddress()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $address = $em->getRepository(Address::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/addresses/' . $address->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": ' . $address->getId() . ',
            "postcode": "*",
            "street": "*",
            "number": "*",
            "locationId": ' . $address->getLocation()->getId() . '
        }',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAddresses()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $address = $em->getRepository(Address::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/addresses?filter[id][]=' . $address->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": 1, "data": [{"id": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCamp()
    {
        $this->markTestSkipped('There is no camp');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $camp = $em->getRepository(HouseholdLocation::class)->findBy(
            ['type' => HouseholdLocation::LOCATION_TYPE_CAMP],
            ['id' => 'asc']
        )[0];

        $this->request('GET', '/api/basic/web-app/v1/addresses/camps/' . $camp->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('locationGroup', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('tentNumber', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
        $this->assertArrayHasKey('adm2Id', $result);
        $this->assertArrayHasKey('adm3Id', $result);
        $this->assertArrayHasKey('adm4Id', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCamps()
    {
        $this->markTestSkipped('There is no camp');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $campAddress = $em->getRepository(HouseholdLocation::class)->findBy(
            ['type' => HouseholdLocation::LOCATION_TYPE_CAMP],
            ['id' => 'asc']
        )[0];

        $this->request('GET', '/api/basic/web-app/v1/addresses/camps?filter[id][]=' . $campAddress->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetResidence()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $residence = $em->getRepository(HouseholdLocation::class)->findBy(
            ['type' => HouseholdLocation::LOCATION_TYPE_RESIDENCE],
            ['id' => 'asc']
        )[0];

        $this->request('GET', '/api/basic/web-app/v1/addresses/residencies/' . $residence->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('locationGroup', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('street', $result);
        $this->assertArrayHasKey('postcode', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
        $this->assertArrayHasKey('adm2Id', $result);
        $this->assertArrayHasKey('adm3Id', $result);
        $this->assertArrayHasKey('adm4Id', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetResidences()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $residency = $em->getRepository(HouseholdLocation::class)->findBy(
            ['type' => HouseholdLocation::LOCATION_TYPE_RESIDENCE],
            ['id' => 'asc']
        )[0];

        $this->request('GET', '/api/basic/web-app/v1/addresses/residencies?filter[id][]=' . $residency->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": 1, "data": [{"id": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetTemporarySettlement()
    {
        $this->markTestSkipped('There is no temporary settlement');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $settlement = $em->getRepository(HouseholdLocation::class)->findBy(
            ['type' => HouseholdLocation::LOCATION_TYPE_SETTLEMENT],
            ['id' => 'asc']
        );

        $this->request('GET', '/api/basic/web-app/v1/addresses/temporary-settlements/' . $settlement->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('locationGroup', $result);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('street', $result);
        $this->assertArrayHasKey('postcode', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
        $this->assertArrayHasKey('adm2Id', $result);
        $this->assertArrayHasKey('adm3Id', $result);
        $this->assertArrayHasKey('adm4Id', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetTemporarySettlements()
    {
        $this->markTestSkipped('There is no temporary settlement');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $settlement = $em->getRepository(HouseholdLocation::class)->findBy(
            ['type' => HouseholdLocation::LOCATION_TYPE_SETTLEMENT],
            ['id' => 'asc']
        )[0];

        $this->request(
            'GET',
            '/api/basic/web-app/v1/addresses/temporary-settlements?filter[id][]=' . $settlement->getId()
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": 1, "data": [{"id": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }
}
