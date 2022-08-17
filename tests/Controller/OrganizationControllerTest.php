<?php

declare(strict_types=1);

namespace Tests\Controller;

use Entity\Organization;
use Entity\OrganizationServices;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Tests\BMSServiceTestCase;

class OrganizationControllerTest extends BMSServiceTestCase
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGet()
    {
        /** @var Organization|null $organization */
        $organization = self::$container->get('doctrine')->getRepository(Organization::class)->findBy([], ['id' => 'asc'])[0];

        if (null === $organization) {
            $this->markTestSkipped('There needs to be at least one organization in system to complete this test');
        }

        $this->request('GET', '/api/basic/web-app/v1/organizations/'.$organization->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('logo', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('primaryColor', $result);
        $this->assertArrayHasKey('secondaryColor', $result);
        $this->assertArrayHasKey('font', $result);
        $this->assertArrayHasKey('footerContent', $result);
    }

    public function testUpdate()
    {
        /** @var Organization|null $organization */
        $organization = self::$container->get('doctrine')->getRepository(Organization::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('PUT', '/api/basic/web-app/v1/organizations/'.$organization->getId(), [
            'logo' => 'http://www.example.org/image.jpg',
            'name' => 'Test organisation',
            'primaryColor' => '#000000',
            'secondaryColor' => '#000000',
            'font' => 'Arial',
            'footerContent' => 'Some text.',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('logo', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('primaryColor', $result);
        $this->assertArrayHasKey('secondaryColor', $result);
        $this->assertArrayHasKey('font', $result);
        $this->assertArrayHasKey('footerContent', $result);
    }

    /**
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/organizations');

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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testListServices()
    {
        /** @var Organization[] $service */
        $services = self::$container->get('doctrine')->getRepository(OrganizationServices::class)->findBy([], ['id' => 'asc']);

        if (empty($services)) {
            $this->markTestSkipped('There needs to be at least one service in system to complete this test');
        }

        $this->request('GET', '/api/basic/web-app/v1/organizations/'.$services[0]->getOrganization()->getId().'/services');

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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testUpdateServices()
    {
        /** @var Organization[] $service */
        $services = self::$container->get('doctrine')->getRepository(OrganizationServices::class)->findBy([], ['id' => 'asc']);

        if (empty($services)) {
            $this->markTestSkipped('There needs to be at least one service in system to complete this test');
        }

        $data = [
            'enabled' => true,
        ];

        $this->request('PATCH', '/api/basic/web-app/v1/organizations/services/'.$services[0]->getOrganization()->getId(), $data);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('parameters', $result);

        $this->assertEquals($data['enabled'], $result['enabled']);
    }


}
