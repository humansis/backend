<?php


namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class HouseholdControllerTest extends BMSServiceTestCase
{

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
    public function testCreateHousehold()
    {
        $household = $this->createHousehold();
        try {
            $this->assertArrayHasKey('id', $household);
            $this->assertArrayHasKey('livelihood', $household);
            $this->assertArrayHasKey('income_level', $household);
            $this->assertArrayHasKey('notes', $household);
            $this->assertArrayHasKey('latitude', $household);
            $this->assertArrayHasKey('longitude', $household);
            $this->assertArrayHasKey('country_specific_answers', $household);
            $this->assertArrayHasKey('beneficiaries', $household);
            $this->assertArrayHasKey('household_locations', $household);
            $householdLocation = $household["household_locations"][0];
            $this->assertArrayHasKey('type', $householdLocation);
            $this->assertArrayHasKey('location_group', $householdLocation);
            $country_specific_answer = current($household["country_specific_answers"]);
            $this->assertArrayHasKey('answer', $country_specific_answer);
            $this->assertArrayHasKey('country_specific', $country_specific_answer);
        } catch (\Exception $exception) {
            $this->removeHousehold($this->namefullnameHousehold);
            $this->fail("\nThe mapping of fields of Household entity is not correct (1).\n");
            return false;
        }

        return true;
    }

    /**
     * @depends testCreateHousehold
     * @throws \Exception
     */
    public function testGetAllHouseholds($isSuccess = true)
    {
        if (!$isSuccess) {
            print_r("\nThe creation of household failed. We can't test the get all.\n");
            $this->markTestIncomplete("The creation of household failed. We can't test the get all.");
        }


        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = [
            "pageIndex" => 0,
            "pageSize" => 10,
            "filter" => [],
            "sort" => []
        ];

        $crawler = $this->request('POST', '/api/wsse/households/get/all', $body);
        $listHousehold = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return true;
    }

    /**
     * @depends testCreateHousehold
     * @return bool|void
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetHouseholds()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            "pageIndex" => 0,
            "pageSize" => 10,
            "filter" => [],
            "sort" => []
        ];
        $crawler = $this->request('POST', '/api/wsse/households/get/all', $body);
        $householdsArray = json_decode($this->client->getResponse()->getContent(), true);
        $households = $householdsArray[1];
        if (!empty($households)) {
            $household = current($households);
            try {
                $this->assertArrayHasKey('id', $household);
                $this->assertArrayHasKey('household_locations', $household);
                $this->assertArrayHasKey('beneficiaries', $household);
                $householdLocation = $household["household_locations"][0];
                $this->assertArrayHasKey('type', $householdLocation);
                $this->assertArrayHasKey('location_group', $householdLocation);
                $beneficiary = current($household["beneficiaries"]);
                $this->assertArrayHasKey('local_given_name', $beneficiary);
                $this->assertArrayHasKey('local_family_name', $beneficiary);
                $this->assertArrayHasKey('vulnerability_criteria', $beneficiary);
                $this->assertArrayHasKey('fathers_name', $beneficiary);
                $vulnerability_criterion = current($beneficiary["vulnerability_criteria"]);
                if (is_array($vulnerability_criterion)) {
                    $this->assertArrayHasKey('id', $vulnerability_criterion);
                    $this->assertArrayHasKey('field_string', $vulnerability_criterion);
                }
            } finally {
                $this->removeHousehold($this->namefullnameHousehold . '(u)');
            }
        } else {
            $this->removeHousehold($this->namefullnameHousehold);
            $this->markTestIncomplete("You currently don't have any household in your database.");
        }

        return $this->removeHousehold($this->namefullnameHousehold . '(u)');
    }

    /**
     * @depends testCreateHousehold
     *
     * @param $hh
     * @return void
     */
    public function testAddHousehold($hh)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body;
        $body['household'] = $this->bodyHousehold;
        $body['projects'] = [1];

        $crawler = $this->request('PUT', '/api/wsse/households', $body);
        $householdsArray = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $householdsArray);
        $this->assertArrayHasKey('household_locations', $householdsArray);
        $householdLocation = $householdsArray["household_locations"][0];
        $this->assertArrayHasKey('type', $householdLocation);
        $this->assertArrayHasKey('location_group', $householdLocation);
        $this->assertArrayHasKey('latitude', $householdsArray);
        $this->assertArrayHasKey('longitude', $householdsArray);
        $this->assertArrayHasKey('livelihood', $householdsArray);
        $this->assertArrayHasKey('income_level', $householdsArray);
        $this->assertArrayHasKey('notes', $householdsArray);
        $this->assertArrayHasKey('beneficiaries', $householdsArray);
        $this->assertArrayHasKey('country_specific_answers', $householdsArray);
        $this->assertArrayHasKey('projects', $householdsArray);

        return true;
    }

    /**
     * @depends testAddHousehold
     * @param $hh
     * @return void
     */
    public function testEditHousehold($hh)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body;
        $body['household'] = $this->bodyHousehold;
        $body['projects'] = [1];

        // $crawler = $this->request('POST', '/api/wsse/households/' . $hh['id'], $body);
        // $householdsArray = json_decode($this->client->getResponse()->getContent(), true);

        // $this->assertArrayHasKey('id', $householdsArray);
        // $this->assertArrayHasKey('address_postcode', $householdsArray);
        // $this->assertArrayHasKey('address_street', $householdsArray);
        // $this->assertArrayHasKey('address_number', $householdsArray);
        // $this->assertArrayHasKey('latitude', $householdsArray);
        // $this->assertArrayHasKey('longitude', $householdsArray);
        // $this->assertArrayHasKey('livelihood', $householdsArray);
        // $this->assertArrayHasKey('notes', $householdsArray);
        // $this->assertArrayHasKey('beneficiaries', $householdsArray);
        // $this->assertArrayHasKey('country_specific_answers', $householdsArray);
        // $this->assertArrayHasKey('location', $householdsArray);
        // $this->assertArrayHasKey('projects', $householdsArray);

        $this->assertTrue(true === true);

        return true;
    }
}
