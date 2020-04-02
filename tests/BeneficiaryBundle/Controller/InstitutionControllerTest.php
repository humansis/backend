<?php


namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use ProjectBundle\Entity\Project;
use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class InstitutionControllerTest extends BMSServiceTestCase
{

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
    public function testCreateInstitution()
    {
        $this->assertTrue(true);
        return new Institution();
    }

    /**
     * @depends testCreateInstitution
     * @throws \Exception
     */
    public function testGetAllInstitutions($isSuccess = true)
    {
        if (!$isSuccess) {
            print_r("\nThe creation of institution failed. We can't test the get all.\n");
            $this->markTestIncomplete("The creation of institution failed. We can't test the get all.");
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

        $crawler = $this->request('POST', '/api/wsse/institutions/get/all', $body);
        $listInstitution = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return true;
    }

    /**
     * @depends testCreateInstitution
     * @return bool|void
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetInstitutions()
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
        $crawler = $this->request('POST', '/api/wsse/institutions/get/all', $body);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);
        $institutions = $institutionsArray[1];
        if (!empty($institutions)) {
            $institution = current($institutions);
            try {
                $this->assertArrayHasKey('id', $institution);
                $this->assertArrayHasKey('institution_locations', $institution);
                $this->assertArrayHasKey('beneficiaries', $institution);
                $institutionLocation = $institution["institution_locations"][0];
                $this->assertArrayHasKey('type', $institutionLocation);
                $this->assertArrayHasKey('location_group', $institutionLocation);
                $beneficiary = current($institution["beneficiaries"]);
                $this->assertArrayHasKey('local_given_name', $beneficiary);
                $this->assertArrayHasKey('local_family_name', $beneficiary);
                $this->assertArrayHasKey('vulnerability_criteria', $beneficiary);
                $vulnerability_criterion = current($beneficiary["vulnerability_criteria"]);
                $this->assertArrayHasKey('id', $vulnerability_criterion);
                $this->assertArrayHasKey('field_string', $vulnerability_criterion);
            } catch (\Exception $exception) {
                $this->removeInstitution($this->namefullnameInstitution . '(u)');
                $this->fail("\nThe mapping of fields of Institution entity is not correct (3).\n");
                return false;
            }
        } else {
//            $this->removeInstitution($this->namefullnameInstitution);
            $this->markTestIncomplete("You currently don't have any institution in your database.");
        }

//        return $this->removeInstitution($this->namefullnameInstitution . '(u)');
    }

    /**
     * @depends testCreateInstitution
     *
     * @param $hh
     * @return void
     */
    public function testAddInstitution($hh)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        /*
        $body['institution'] = $this->bodyInstitution;
        $body['projects'] = [1];

        $crawler = $this->request('PUT', '/api/wsse/institutions', $body);
        $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $institutionsArray);
        $this->assertArrayHasKey('institution_locations', $institutionsArray);
        $institutionLocation = $institutionsArray["institution_locations"][0];
        $this->assertArrayHasKey('type', $institutionLocation);
        $this->assertArrayHasKey('location_group', $institutionLocation);
        $this->assertArrayHasKey('latitude', $institutionsArray);
        $this->assertArrayHasKey('longitude', $institutionsArray);
        $this->assertArrayHasKey('livelihood', $institutionsArray);
        $this->assertArrayHasKey('income_level', $institutionsArray);
        $this->assertArrayHasKey('notes', $institutionsArray);
        $this->assertArrayHasKey('beneficiaries', $institutionsArray);
        $this->assertArrayHasKey('country_specific_answers', $institutionsArray);
        $this->assertArrayHasKey('projects', $institutionsArray);
*/
        return true;
    }

    /**
     * @depends testAddInstitution
     * @param $hh
     * @return void
     */
    public function testEditInstitution($hh)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body['institution'] = $this->bodyInstitution;
        $body['projects'] = [1];
    
        // $crawler = $this->request('POST', '/api/wsse/institutions/' . $hh['id'], $body);
        // $institutionsArray = json_decode($this->client->getResponse()->getContent(), true);

        // $this->assertArrayHasKey('id', $institutionsArray);
        // $this->assertArrayHasKey('address_postcode', $institutionsArray);
        // $this->assertArrayHasKey('address_street', $institutionsArray);
        // $this->assertArrayHasKey('address_number', $institutionsArray);
        // $this->assertArrayHasKey('latitude', $institutionsArray);
        // $this->assertArrayHasKey('longitude', $institutionsArray);
        // $this->assertArrayHasKey('livelihood', $institutionsArray);
        // $this->assertArrayHasKey('notes', $institutionsArray);
        // $this->assertArrayHasKey('beneficiaries', $institutionsArray);
        // $this->assertArrayHasKey('country_specific_answers', $institutionsArray);
        // $this->assertArrayHasKey('location', $institutionsArray);
        // $this->assertArrayHasKey('projects', $institutionsArray);

        return true;
    }
}
