<?php


namespace Tests\DistributionBundle\Controller;


use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\Location;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Entity\SelectionCriteria;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class DistributionControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;

    /** @var string $namefullname */
    private $namefullname = "TEST_DISTRIBUTION_NAME_PHPUNIT";

    private $body = [
        "name" => "TEST_DISTRIBUTION_NAME_PHPUNIT",
        "type" => 0,
        "location" => [
            "country_iso3" => "KHM",
            "adm1" => "ADMIN FAKED",
            "adm2" => "ADMIN FAKED",
            "adm3" => "ADMIN FAKED",
            "adm4" => "ADMIN FAKED"
        ],
        "selection_criteria" => [[
            "table_string" => "default",
            "field_string" => "gender",
            "value_string" => "0",
            "condition_string" => "=",
            "kind_beneficiary" => "beneficiary",
            "field_id" => null
        ]],
        "commodities" => [[
            "unit" => "PHPUNIT TEST",
            "value" => 999999999,
            "modality_type" => []
        ]]
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
    public function testCreateDistribution()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("\nThere is no project inside the database\n");
            return false;
        }
        $this->body['project']['id'] = current($projects)->getId();

        $modalityTypes = $this->em->getRepository(ModalityType::class)->findAll();
        if (empty($modalityTypes))
        {
            print_r("\nThere is no modality type inside the database\n");
            return false;
        }
        $this->body['commodities'][0]['modality_type']['id'] = current($modalityTypes)->getId();

        $crawler = $this->client->request('PUT', '/api/wsse/distributions', $this->body, [], ['HTTP_COUNTRY' => 'KHM']);
        $return = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertArrayHasKey('distribution', $return);
        $this->assertArrayHasKey('data', $return);

        $distribution = $return['distribution'];

        $this->assertArrayHasKey('id', $distribution);
        $this->assertArrayHasKey('name', $distribution);
        $this->assertSame($distribution['name'], $this->namefullname);
        $this->assertArrayHasKey('updated_on', $distribution);
        $this->assertArrayHasKey('location', $distribution);
        $this->assertArrayHasKey('project', $distribution);
        $this->assertArrayHasKey('selection_criteria', $distribution);
        $this->assertArrayHasKey('validated', $distribution);

        $location = $this->em->getRepository(Location::class)->findOneByAdm1("ADMIN FAKED");
        if ($location instanceof Location)
        {
            $this->em->remove($location);
        }

        $commodity = $this->em->getRepository(Commodity::class)->findOneByUnit("PHPUNIT TEST");
        if ($commodity instanceof Commodity)
        {
            $this->em->remove($commodity);
        }

        $distribution = $this->em->getRepository(DistributionData::class)->find($distribution['id']);
        if ($distribution instanceof DistributionData)
        {

            $selectionCriteria = $this->em->getRepository(SelectionCriteria::class)->findByDistributionData($distribution);
            foreach ($selectionCriteria as $selectionCriterion)
            {
                $this->em->remove($selectionCriterion);

            }
            $this->em->remove($distribution);
        }

        $this->em->flush();
        return true;
    }
}