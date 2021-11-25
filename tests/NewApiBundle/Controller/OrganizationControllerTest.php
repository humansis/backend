<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class OrganizationControllerTest extends AbstractFunctionalApiTest
{
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

        $this->client->request('GET', '/api/basic/web-app/v1/organizations/'.$organization->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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

        $this->client->request('PUT', '/api/basic/web-app/v1/organizations/'.$organization->getId(), [
            'logo' => 'http://www.example.org/image.jpg',
            'name' => 'Test organisation',
            'primaryColor' => '#000000',
            'secondaryColor' => '#000000',
            'font' => 'Arial',
            'footerContent' => 'Some text.',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/organizations', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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

        $this->client->request('GET', '/api/basic/web-app/v1/organizations/'.$services[0]->getOrganization()->getId().'/services', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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

        $this->client->request('PATCH', '/api/basic/web-app/v1/organizations/services/'.$services[0]->getOrganization()->getId(), $data, [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('parameters', $result);

        $this->assertEquals($data['enabled'], $result['enabled']);
    }


}
