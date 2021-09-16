<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class InstitutionControllerTest extends BMSServiceTestCase
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
        $this->client = self::$container->get('test.client');
    }

    /**
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testCreate()
    {
        /** @var Location|null $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];

        if (null === $location) {
            $this->markTestSkipped('There needs to be at least one location in system to complete this test');
        }

        $this->request('POST', '/api/basic/web-app/v1/institutions', [
            'longitude' => 'test longitude',
            'latitude' => 'test latitude',
            'name' => 'test name',
            'contactGivenName' => 'test contactGivenName',
            'contactFamilyName' => 'test contactFamilyName',
            'type' => 'test type',
            'projectIds' => [],
            'address' => [
                'type' => 'test type',
                'locationGroup' => 'test locationGroup',
                'number' => 'test number',
                'street' => 'test street',
                'postcode' => 'test postcode',
                'locationId' => $location->getId(),
            ],
            'nationalIdCard' => [
                'number' => '022-33-1547',
                'type' => 'Passport',
            ],
            'phone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     *
     * @param int $id
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testUpdate(int $id)
    {
        /** @var Location|null $location */
        $location = self::$container->get('doctrine')->getRepository(Location::class)->findBy([])[0];
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        $data = [
            'longitude' => 'test CHANGED',
            'latitude' => 'test latitude',
            'name' => 'test name',
            'contactGivenName' => 'test contactGivenName',
            'contactFamilyName' => 'test contactFamilyName',
            'type' => 'test type',
            'projectIds' => [$project->getId()],
            'address' => [
                'type' => 'test type',
                'locationGroup' => 'test locationGroup',
                'number' => 'test number',
                'street' => 'test street',
                'postcode' => 'test postcode',
                'locationId' => $location->getId(),
            ],
            'nationalIdCard' => [
                'number' => '022-33-1547',
                'type' => 'Passport',
            ],
            'phone' => [
                'prefix' => '420',
                'number' => '123456789',
                'type' => 'Landline',
                'proxy' => true,
            ],
        ];

        $this->request('PUT', '/api/basic/web-app/v1/institutions/'.$id, $data);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);
        $this->assertArrayHasKey('projectIds', $result);

        $this->assertEquals($data['longitude'], $result['longitude']);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/institutions/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('contactGivenName', $result);
        $this->assertArrayHasKey('contactFamilyName', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('addressId', $result);
        $this->assertArrayHasKey('nationalId', $result);
        $this->assertArrayHasKey('phoneId', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/institutions?sort[]=name.asc&filter[projects][]=1&filter[fulltext]=a');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testGet
     *
     * @param int $id
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(int $id)
    {
        $this->request('DELETE', '/api/basic/web-app/v1/institutions/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     *
     * @param int $id
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetNotexists(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/institutions/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function testGetInstitutionsByProject()
    {
        try {
            /** @var Institution $institution */
            $institution = $this->em->getRepository(Institution::class)->createQueryBuilder('i')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $exception) {
            $this->markTestSkipped('There is no institution to be tested');
        }

        $this->request('GET', '/api/basic/web-app/v1/projects/'.$institution->getProjects()[0]->getId().'/institutions');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }
}
