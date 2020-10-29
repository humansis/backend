<?php


namespace Tests\ProjectBundle\Controller;

use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\DBAL\SubSectorEnum;
use ProjectBundle\DTO\Sector;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class SectorControllerTest extends BMSServiceTestCase
{

    /** @var string $name */
    private $name = "TEST_DONOR_NAME_PHPUNIT";

    private $body = [
        "name" => "TEST_DONOR_NAME_PHPUNIT"
    ];


    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testGetSectors()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/sectors');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $sectors = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(count(SectorEnum::all()), $sectors, "Some sectors missing.");

        foreach ($sectors as $sector) {
            $this->assertArrayHasKey('id', $sector);
            $this->assertContains($sector['id'], SectorEnum::all());
            $this->assertArrayHasKey('name', $sector, "Name missing in sector ".$sector['id']);
            $this->assertArrayHasKey('subSectors', $sector, "SubSectors missing in sector ".$sector['id']);
        }
        return true;
    }

    public function testProjectSectors()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $projectId = 3;
        $project = $this->em->getRepository(Project::class)->find($projectId);

        if (!$project) {
            $this->markTestIncomplete("Warning: there is no project $projectId to complete test ".__METHOD__);
        }

        $project->setSectors([new Sector(SectorEnum::EDUCATION, null)]);
        $this->em->persist($project);
        $this->em->flush();

        $crawler = $this->request('GET', '/api/wsse/project/'.$projectId.'/sectors');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $sectors = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(count($project->getSectors()), $sectors, "Wrong sector count");

        foreach ($sectors as $sector) {
            $this->assertArrayHasKey('id', $sector);
            $this->assertContains($sector['id'], SectorEnum::all());
            $this->assertArrayHasKey('name', $sector, "Name missing in sector ".$sector['id']);
            $this->assertArrayHasKey('subSectors', $sector, "SubSectors missing in sector ".$sector['id']);

            $subSector = $sector['subSectors'][0];
            $where = "in sector ".$sector['id']." and subsector ".$subSector['id'];
            $this->assertArrayHasKey('id', $subSector);
            $this->assertContains($subSector['id'], SubSectorEnum::all());
            $this->assertArrayHasKey('name', $subSector, "Name missing $where");
            $this->assertArrayHasKey('availableTargets', $subSector, "Available targets missing $where");
            $this->assertArrayHasKey('assistanceType', $subSector, "Assistance type missing $where");
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    public function testGetSubSectors()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/sectors');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $sectors = json_decode($this->client->getResponse()->getContent(), true);

        foreach ($sectors as $sector) {
            foreach ($sector['subSectors'] as $subSector) {
                $where = "in sector ".$sector['id']." and subsector ".$subSector['id'];
                $this->assertArrayHasKey('id', $subSector);
                $this->assertContains($subSector['id'], SubSectorEnum::all());
                $this->assertArrayHasKey('name', $subSector, "Name missing $where");
                $this->assertArrayHasKey('availableTargets', $subSector, "Available targets missing $where");
                $this->assertArrayHasKey('assistanceType', $subSector, "Assistance type missing $where");

                $availableTargets = $subSector['availableTargets'];
                $this->assertIsArray($availableTargets);
                foreach ($availableTargets as $target) {
                    $this->assertTrue(in_array($target, ['household', 'individual', 'institution', 'community']), "Wrong target type");
                }

                $assistanceType = $subSector['assistanceType'];
                $this->assertIsNotArray($assistanceType);
                $this->assertTrue(in_array($assistanceType, ['distribution', 'activity']), "Wrong assistance type");

            }
        }

        return true;
    }
}
