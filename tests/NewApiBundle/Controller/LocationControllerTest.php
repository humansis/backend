<?php

namespace Tests\NewApiBundle\Controller;

use NewApiBundle\Entity\Location;
use NewApiBundle\Repository\LocationRepository;
use Exception;
use NewApiBundle\Component\Country\Countries;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\UserProject;

class LocationControllerTest extends BMSServiceTestCase
{
    /** @var Countries */
    private $countries;

    /** @var LocationRepository */
    private $locationRepository;

    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
        $this->countries = self::$container->get(Countries::class);
        $this->locationRepository = self::$container->get(LocationRepository::class);
    }

    public function testGetCountries()
    {
        $this->request('GET', '/api/basic/web-app/v1/countries');

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

    public function testGetUserCountriesAdmin()
    {
        $this->request('GET', '/api/basic/web-app/v1/users/'.$this->getTestUser(self::USER_TESTER)->getId().'/countries');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $allCountries = $this->countries->getAll();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertSame(count($allCountries), $result['totalCount']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('iso3', $result['data'][0]);
        $this->assertArrayHasKey('currency', $result['data'][0]);
    }

    public function testGetUserCountriesNoAdmin(): void
    {
        $this->request('GET', '/api/basic/web-app/v1/users/'.$this->getTestUser(self::USER_TESTER_VENDOR)->getId().'/countries');
        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $numberOfCountries = 0;
        $projects = [];
        $allCountries = $this->countries->getAll();
        $user = $this->getTestUser(self::USER_TESTER_VENDOR);

        /** @var UserProject $userProject */
        foreach ($user->getProjects() as $userProject) {
            $projects[] = $userProject->getProject()->getIso3();
        }

        foreach($allCountries as $country){
            if(in_array($country->getIso3(), $projects)){
                $numberOfCountries++;
            }
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertSame($numberOfCountries, $result['totalCount']);
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
        $this->request('GET', '/api/basic/web-app/v1/countries/'.$iso3);

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
        $this->request('GET', '/api/basic/web-app/v1/adm1');

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
        /** @var Location $location */
        $location = $this->locationRepository->findOneBy(['countryISO3' => $this->iso3, 'lvl' => 1]);
        if (!$location) {
            $this->markTestSkipped('There is no such location to test');
        }

        $this->request('GET', '/api/basic/web-app/v1/adm1?filter[id][]='.$location->getId());

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
        $this->request('GET', '/api/basic/web-app/v1/adm1/'.$id);

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
        $this->request('GET', '/api/basic/web-app/v1/adm1/'.$id.'/adm2');

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
//        $this->assertArrayHasKey('adm1Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    public function testGetListOfAdm2Filtered()
    {
        $location = $this->locationRepository->findOneBy(['countryISO3' => $this->iso3, 'lvl' => 2]);
        if (!$location) {
            $this->markTestSkipped('There is no such location to test');
        }

        $this->request('GET', '/api/basic/web-app/v1/adm2?filter[id][]='.$location->getId());

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
//        $this->assertArrayHasKey('adm1Id', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm2
     */
    public function testGetDetailOfAdm2($id)
    {
        $this->request('GET', '/api/basic/web-app/v1/adm2/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
//        $this->assertArrayHasKey('adm1Id', $result);
    }

    /**
     * @depends testGetListOfAdm2
     */
    public function testGetListOfAdm3($id)
    {
        $this->request('GET', '/api/basic/web-app/v1/adm2/'.$id.'/adm3');

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
//        $this->assertArrayHasKey('adm2Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    public function testGetListOfAdm3Filtered()
    {
        $location = $this->locationRepository->findOneBy(['countryISO3' => $this->iso3, 'lvl' => 3]);
        if (!$location) {
            $this->markTestSkipped('There is no such location to test');
        }

        $this->request('GET', '/api/basic/web-app/v1/adm3?filter[id][]='.$location->getId());

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
//        $this->assertArrayHasKey('adm2Id', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm3
     */
    public function testGetDetailOfAdm3($id)
    {
        $this->request('GET', '/api/basic/web-app/v1/adm3/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
//        $this->assertArrayHasKey('adm2Id', $result);
    }

    /**
     * @depends testGetListOfAdm3
     */
    public function testGetListOfAdm4($id)
    {
        $this->request('GET', '/api/basic/web-app/v1/adm3/'.$id.'/adm4');

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
//        $this->assertArrayHasKey('adm3Id', $result['data'][0]);

        return $result['data'][0]['id'];
    }

    public function testGetListOfAdm4Filtered()
    {
        $location = $this->locationRepository->findOneBy(['countryISO3' => $this->iso3, 'lvl' => 4]);
        if (!$location) {
            $this->markTestSkipped('There is no such location to test');
        }

        $this->request('GET', '/api/basic/web-app/v1/adm4?filter[id][]='.$location->getId());

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
//        $this->assertArrayHasKey('adm3Id', $result['data'][0]);
    }

    /**
     * @depends testGetListOfAdm4
     */
    public function testGetDetailOfAdm4($id)
    {
        $this->request('GET', '/api/basic/web-app/v1/adm4/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('locationId', $result);
//        $this->assertArrayHasKey('adm3Id', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetLocations()
    {
        $location = $this->locationRepository->findBy(['countryISO3' => 'KHM'], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/locations?filter[id][]='.$location->getId());

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
