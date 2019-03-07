<?php


namespace Tests\ProjectBundle\Controller;


use BeneficiaryBundle\Entity\Household;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\UserProject;

class ProjectControllerTest extends BMSServiceTestCase
{
    /** @var string $name */
    private $name = "TEST_PROJECT_NAME";

    private $body = [
        "name" => "TEST_PROJECT_NAME",
        "start_date" => "2018-02-01",
        "end_date" => "2018-03-03",
        "value" => 5,
        "notes" => "This is a note"
    ];


    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testCreateProject()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('PUT', '/api/wsse/projects', $this->body);

        $project = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        try
        {
            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('iso3', $project);
            $this->assertArrayHasKey('name', $project);
            $this->assertArrayHasKey('value', $project);
            $this->assertArrayHasKey('notes', $project);
            $this->assertArrayHasKey('end_date', $project);
            $this->assertArrayHasKey('start_date', $project);
            $this->assertArrayHasKey('number_of_households', $project);
            $this->assertSame($project['name'], $this->name);
        }
        catch (\Exception $exception)
        {
            print_r("\nThe mapping of fields of Project entity is not correct.\n");
            $this->remove($this->name);
            return false;
        }

        return $project;
    }

    /**
     * @depends testCreateProject
     * @param $project
     * @return bool
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEditProject($project)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['name'] .= '(u)';
        $crawler = $this->request('POST', '/api/wsse/projects/' . $project['id'], $this->body);
        $this->body['name'] = $this->name;
        $newproject = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();
        try
        {
            $this->assertArrayHasKey('id', $newproject);
            $this->assertArrayHasKey('iso3', $newproject);
            $this->assertArrayHasKey('name', $newproject);
            $this->assertArrayHasKey('value', $newproject);
            $this->assertArrayHasKey('notes', $newproject);
            $this->assertArrayHasKey('end_date', $newproject);
            $this->assertArrayHasKey('start_date', $newproject);
            $this->assertArrayHasKey('number_of_households', $newproject);
            $this->assertSame($newproject['name'], $this->name . "(u)");
        }
        catch (\Exception $exception)
        {
            print_r("\n{$exception->getMessage()}\n");
            $this->remove($this->name);
            return false;
        }

        return true;
    }

    /**
     * @depends testEditProject
     * @throws \Exception
     */
    public function testGetProjects()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/projects');
        $projects = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($projects))
        {
            $project = $projects[0];

            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('iso3', $project);
            $this->assertArrayHasKey('name', $project);
            $this->assertArrayHasKey('notes', $project);
            $this->assertArrayHasKey('value', $project);
            $this->assertArrayHasKey('donors', $project);
            $this->assertArrayHasKey('end_date', $project);
            $this->assertArrayHasKey('start_date', $project);
            $this->assertArrayHasKey('number_of_households', $project);
            $this->assertArrayHasKey('sectors', $project);
        }
        else
        {
            $this->markTestIncomplete("You currently don't have any project in your database.");
        }
    }

    /**
     * @depends testCreateProject
     * @param $project
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddHouseholds($project)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $household = $this->em->getRepository(Household::class)->findBy([], null, 3);

        if (empty($household)) {
            $this->markTestIncomplete("You have no households in your database");
        }

        $body = array(
            'beneficiaries' => $household
        );

        $crawler = $this->request('POST', '/api/wsse/projects/' . $project['id'] . '/beneficiaries/add', $body);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * @depends testCreateProject
     * @param $project
     * @return void
     * @throws \Exception
     */
    public function testArchiveProject($project) {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/wsse/projects/' . $project['id']);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->remove($this->name . '(u)');
    }

    /**
     * @depends testAddHouseholds
     *
     * @param string
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove($projectName)
    {
        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneByName($projectName);
        if ($project instanceof Project)
        {
            $userProject = $this->em->getRepository(UserProject::class)->findOneByProject($project);
            $this->em->remove($userProject);
            $this->em->flush();
            $this->em->remove($project);
            $this->em->flush();
        }
    }

}