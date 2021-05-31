<?php

namespace Tests\NewApiBundle\Controller;

use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class LocationControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testGetCountries()
    {
        $this->request('GET', '/api/basic/countries');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('iso3', $result['data'][0]);
        $this->assertArrayHasKey('currency', $result['data'][0]);

        return $result['data'][0]['iso3'];
    }

    public function testGetUserCountries()
    {
        $this->request('GET', '/api/basic/web-app/v1/users/'.$this->getTestUser(self::USER_TESTER)->getId().'/countries');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertSame($this->getTestUser(self::USER_TESTER)->getCountries()->count(), $result['totalCount']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('iso3', $result['data'][0]);
        $this->assertArrayHasKey('currency', $result['data'][0]);
    }

    /**
     * @depends testGetCountries
     */
    public function testGetCountry($iso3)
    {
        $this->request('GET', '/api/basic/countries/'.$iso3);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('currency', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfAdm1()
    {
        $this->request('GET', '/api/basic/adm1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('countryIso3', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    /**
     * @throws Exception
     */
    public function testGetListOfAdm1Filtered()
    {
        $this->request('GET', '/api/basic/adm1?filter[id][]=1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('countryIso3', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm1
     */
    public function testGetDetailOfAdm1($id)
    {
        $this->request('GET', '/api/basic/adm1/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('countryIso3', $result);
        $this->assertArrayHasKey('locationId', $result);
    }

    /**
     * @depends testGetListOfAdm1
     */
    public function testGetListOfAdm2($id)
    {
        $this->request('GET', '/api/basic/adm1/'.$id.'/adm2');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm1Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    public function testGetListOfAdm2Filtered()
    {
        $this->request('GET', '/api/basic/adm2?filter[id][]=1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm1Id', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm2
     */
    public function testGetDetailOfAdm2($id)
    {
        $this->request('GET', '/api/basic/adm2/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
    }

    /**
     * @depends testGetListOfAdm2
     */
    public function testGetListOfAdm3($id)
    {
        $this->request('GET', '/api/basic/adm2/'.$id.'/adm3');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm2Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    public function testGetListOfAdm3Filtered()
    {
        $this->request('GET', '/api/basic/adm3?filter[id][]=1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm2Id', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm3
     */
    public function testGetDetailOfAdm3($id)
    {
        $this->request('GET', '/api/basic/adm3/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm2Id', $result);
    }

    /**
     * @depends testGetListOfAdm3
     */
    public function testGetListOfAdm4($id)
    {
        $this->request('GET', '/api/basic/adm3/'.$id.'/adm4');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm3Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    public function testGetListOfAdm4Filtered()
    {
        $this->request('GET', '/api/basic/adm4?filter[id][]=1');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('code', $result['data'][0]);
        $this->assertArrayHasKey('locationId', $result['data'][0]);
        $this->assertArrayHasKey('adm3Id', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm4
     */
    public function testGetDetailOfAdm4($id)
    {
        $this->request('GET', '/api/basic/adm4/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm3Id', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLocations()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $location = $em->getRepository(Location::class)->findBy([])[0];

        $this->request('GET', '/api/basic/locations?filter[id][]='.$location->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
    }
}
