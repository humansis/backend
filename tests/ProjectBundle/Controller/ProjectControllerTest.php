<?php


namespace Tests\ProjectBundle\Controller;


use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\UserProject;

class ProjectControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;
    /** @var string $name */
    private $name = "TEST_PROJECT_NAME";

    private $body = [
        "name" => "TEST_PROJECT_NAME",
        "start_date" => "2018-02-01",
        "end_date" => "2018-03-03",
        "number_of_households" => 2,
        "value" => 5,
        "notes" => "This is a note",
        "iso3" => "FR"
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

        $crawler = $this->client->request('PUT', '/api/wsse/projects', $this->body);
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
            print_r("\nThe mapping of fields of Donor entity is not correct.\n");
            $this->remove($this->name);
            return false;
        }

        return true;
    }

    /**
     * @depends testCreateProject
     * @throws \Exception
     */
    public function testEditProject($isSuccess = true)
    {
        if (!$isSuccess)
        {
            print_r("\nThe creation of project failed. We can't test the update.\n");
            $this->markTestIncomplete("The creation of project failed. We can't test the update.");
        }
        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneByName($this->name);
        if (!$project instanceof Project)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['name'] .= '(u)';
        $crawler = $this->client->request('POST', '/api/wsse/projects/' . $project->getId(), $this->body);
        $this->body['name'] = $this->name;
        $project = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();
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
            $this->assertSame($project['name'], $this->name . "(u)");
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
    public function testGetProjects($isSuccess)
    {
        if (!$isSuccess)
        {
            print_r("\nThe edition of project failed. We can't test the update.\n");
            $this->markTestIncomplete("The edition of project failed. We can't test the update.");
        }
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('GET', '/api/wsse/projects');
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


        return $this->remove($this->name . '(u)');
    }

    /**
     * @depends testEditProject
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove($name)
    {
        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->findOneByName($name);
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