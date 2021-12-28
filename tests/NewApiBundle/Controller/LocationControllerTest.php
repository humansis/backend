<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use CommonBundle\DataFixtures\UserFixtures;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use UserBundle\Entity\User;
use UserBundle\Entity\UserProject;

class LocationControllerTest extends AbstractFunctionalApiTest
{
    public function testGetCountries()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/countries', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $testUserId = 1;
        $this->client->request('GET', '/api/basic/web-app/v1/users/'.$testUserId.'/countries', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $allCountries = self::$container->getParameter('app.countries');

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
        $testUser = self::$container->get('doctrine')->getRepository(User::class)->findOneBy(['username'=>UserFixtures::REF_VENDOR_KHM], ['id' => 'asc']);
        $testUserVendorId = $testUser->getId();
        $this->client->request('GET', '/api/basic/web-app/v1/users/'.$testUserVendorId.'/countries', [], [], $this->addAuth());
        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $numberOfCountries = 0;
        $projects = [];
        $allCountries = self::$container->getParameter('app.countries');
        $user = $this->getTestUser(self::USER_TESTER_VENDOR);

        /** @var UserProject $userProject */
        foreach ($user->getProjects() as $userProject) {
            $projects[] = $userProject->getProject()->getIso3();
        }

        foreach($allCountries as $country){
            if(in_array($country['iso3'], $projects)){
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
        $this->client->request('GET', '/api/basic/web-app/v1/countries/'.$iso3, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm1?filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm1/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $this->client->request('GET', '/api/basic/web-app/v1/adm1/'.$id.'/adm2', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm2?filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm2/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $this->client->request('GET', '/api/basic/web-app/v1/adm2/'.$id.'/adm3', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm3?filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm3/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $this->client->request('GET', '/api/basic/web-app/v1/adm3/'.$id.'/adm4', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm4?filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/adm4/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $location = $em->getRepository(Location::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/locations?filter[id][]='.$location->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
    }
}
