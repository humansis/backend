<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\Entity\Project;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class SectorsCodelistControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetSectors()
    {
        /** @var Project $project */
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v2/projects/'.$project->getId().'/sectors', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals($project->getSectors()->count(), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetSubSectors()
    {
        $testSector = SectorEnum::all()[0];

        $this->client->request('GET', '/api/basic/web-app/v1/sectors/'.$testSector.'/subsectors', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }
}
