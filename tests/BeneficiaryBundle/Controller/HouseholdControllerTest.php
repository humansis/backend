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
        $this->client = self::$container->get('test.client');
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
            $this->assertArrayHasKey('income', $household);
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
            $this->assertArrayHasKey('income_spent_on_food', $household);
            $this->assertArrayHasKey('household_income', $household);
            $this->assertArrayHasKey('support_organization_name', $household);

            $this->assertEquals(1000, $household['income_spent_on_food']);
            $this->assertEquals(100000, $household['household_income']);

            $this->assertArrayHasKey('enumerator_name', $household);
            $this->assertEquals('ENUMERATOR_NAME_TEST', $household['enumerator_name']);

            $beneficiary = current($household["beneficiaries"]);

            $this->assertArrayHasKey('local_parents_name', $beneficiary);
            $this->assertEquals('PARENTSNAME_TEST_LOCAL', $beneficiary['local_parents_name']);

            $this->assertArrayHasKey('en_parents_name', $beneficiary);
            $this->assertEquals('PARENTSNAME_TEST_EN', $beneficiary['en_parents_name']);

        } catch (\Exception $exception) {
            $this->removeHousehold($this->namefullnameHousehold);
            $this->fail("\nThe mapping of fields of Household entity is not correct (1).\n");
            return false;
        }

        $this->assertEquals($this->bodyHousehold['support_organization_name'], $household['support_organization_name'], "'support_organization_name' wasn't saved");

        return true;
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

        $body = [];
        $body['household'] = $this->bodyHousehold;
        $body['projects'] = [1];
        $body['household']['support_organization_name'] = "__TEST_ADD_support_organization_name__";

        $crawler = $this->request('PUT', '/api/wsse/households', $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $householdsArray = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $householdsArray);
        $this->assertArrayHasKey('household_locations', $householdsArray);
        $householdLocation = $householdsArray["household_locations"][0];
        $this->assertArrayHasKey('type', $householdLocation);
        $this->assertArrayHasKey('location_group', $householdLocation);
        $this->assertArrayHasKey('latitude', $householdsArray);
        $this->assertArrayHasKey('longitude', $householdsArray);
        $this->assertArrayHasKey('livelihood', $householdsArray);
        $this->assertArrayHasKey('income', $householdsArray);
        $this->assertArrayHasKey('notes', $householdsArray);
        $this->assertArrayHasKey('beneficiaries', $householdsArray);
        $this->assertArrayHasKey('support_organization_name', $householdsArray);
        $this->assertArrayHasKey('country_specific_answers', $householdsArray);
        $this->assertArrayHasKey('projects', $householdsArray);
        $this->assertArrayHasKey('enumerator_name', $householdsArray);
        $this->assertArrayHasKey('income_spent_on_food', $householdsArray);
        $this->assertArrayHasKey('household_income', $householdsArray);

        $beneficiary = current($householdsArray["beneficiaries"]);
        $this->assertArrayHasKey('local_parents_name', $beneficiary);
        $this->assertArrayHasKey('en_parents_name', $beneficiary);

        $this->assertEquals($body['household']['support_organization_name'], $householdsArray['support_organization_name'], "'support_organization_name' wasn't changed");

        return $householdsArray;
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

        $body = [];
        $body['household'] = $this->bodyHousehold;
        $body['projects'] = [1];
        $body['household']['support_organization_name'] = "__TEST_EDIT_support_organization_name__";

        $crawler = $this->request('POST', '/api/wsse/households/' . $hh['id'], $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $householdsArray = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $householdsArray);
        $this->assertArrayHasKey('household_locations', $householdsArray);
        $householdLocation = array_pop($householdsArray["household_locations"]);
        $this->assertArrayHasKey('type', $householdLocation);
        $this->assertArrayHasKey('location_group', $householdLocation);
        $this->assertArrayHasKey('latitude', $householdsArray);
        $this->assertArrayHasKey('longitude', $householdsArray);
        $this->assertArrayHasKey('livelihood', $householdsArray);
        $this->assertArrayHasKey('income', $householdsArray);
        $this->assertArrayHasKey('notes', $householdsArray);
        $this->assertArrayHasKey('beneficiaries', $householdsArray);
        $this->assertArrayHasKey('support_organization_name', $householdsArray);
        $this->assertArrayHasKey('country_specific_answers', $householdsArray);
        $this->assertArrayHasKey('projects', $householdsArray);
        $this->assertArrayHasKey('income_spent_on_food', $householdsArray);
        $this->assertArrayHasKey('household_income', $householdsArray);
        $this->assertArrayHasKey('enumerator_name', $householdsArray);

        $beneficiary = current($householdsArray["beneficiaries"]);
        $this->assertArrayHasKey('local_parents_name', $beneficiary);
        $this->assertArrayHasKey('en_parents_name', $beneficiary);

        $this->assertEquals($body['household']['support_organization_name'], $householdsArray['support_organization_name'], "'support_organization_name' wasn't changed");

        return true;
    }
}
