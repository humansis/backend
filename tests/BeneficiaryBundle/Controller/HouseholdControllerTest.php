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
        $this->setDefaultSerializerName("jms_serializer");
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
        try
        {
            $this->assertArrayHasKey('id', $household);
            $this->assertArrayHasKey('address_street', $household);
            $this->assertArrayHasKey('address_number', $household);
            $this->assertArrayHasKey('address_postcode', $household);
            $this->assertArrayHasKey('livelihood', $household);
            $this->assertArrayHasKey('notes', $household);
            $this->assertArrayHasKey('latitude', $household);
            $this->assertArrayHasKey('longitude', $household);
            $this->assertArrayHasKey('location', $household);
            $this->assertArrayHasKey('country_specific_answers', $household);
            $this->assertArrayHasKey('beneficiaries', $household);
            $location = $household["location"];
            $this->assertArrayHasKey('adm1', $location);
            $this->assertArrayHasKey('adm2', $location);
            $this->assertArrayHasKey('adm3', $location);
            $this->assertArrayHasKey('adm4', $location);
            $country_specific_answer = current($household["country_specific_answers"]);
            $this->assertArrayHasKey('answer', $country_specific_answer);
            $this->assertArrayHasKey('country_specific', $country_specific_answer);
        }
        catch (\Exception $exception)
        {
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
        if (!$isSuccess)
        {
            print_r("\nThe creation of household failed. We can't test the get all.\n");
            $this->markTestIncomplete("The creation of household failed. We can't test the get all.");
        }


        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            "offset" => 0,
            "limit" => 1
        ];

        $crawler = $this->request('POST', '/api/wsse/households/get/all', $body);
        $listHousehold = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return true;
    }

    /**
     * @depends testGetAllHouseholds
     * @throws \Exception
     */
    public function testEditHousehold($isSuccess = true)
    {
        if (!$isSuccess)
        {
            print_r("\nThe get all of household failed. We can't test the update.\n");
            $this->markTestIncomplete("The get all of household failed. We can't test the update.");
        }

        $this->em->clear();
        $household = $this->em->getRepository(Household::class)->findOneBy([
            "addressStreet" => $this->bodyHousehold["address_street"],
            "addressNumber" => $this->bodyHousehold["address_number"],
            "addressPostcode" => $this->bodyHousehold["address_postcode"],
        ]);
        if (!$household instanceof Household)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("There is no project inside your database");
            return false;
        }

        $this->bodyHousehold['address_street'] .= '(u)';

        foreach ($this->bodyHousehold['beneficiaries'] as $index => $beneficiaryArray)
        {
            $beneficiary = $this->em->getRepository(Beneficiary::class)
                ->findOneByGivenName($beneficiaryArray['given_name']);
            $this->bodyHousehold['beneficiaries'][$index]['id'] = $beneficiary->getId();

            foreach ($beneficiaryArray['phones'] as $index2 => $phoneArray)
            {
                $phone = $this->em->getRepository(Phone::class)
                    ->findOneByNumber($phoneArray['number']);
                $this->bodyHousehold['beneficiaries'][$index]['phones'][$index2]['id'] = $phone->getId();
            }

            foreach ($beneficiaryArray['national_ids'] as $index2 => $national_idArray)
            {
                $national_id = $this->em->getRepository(NationalId::class)
                    ->findOneByIdNumber($national_idArray['id_number']);
                $this->bodyHousehold['beneficiaries'][$index]['national_ids'][$index2]['id'] = $national_id->getId();
            }
        }

        $vulnerabilityCriterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneBy([
            "fieldString" => "disabled"
        ]);
        $beneficiaries = $this->bodyHousehold["beneficiaries"];
        $vulnerabilityId = $vulnerabilityCriterion->getId();
        foreach ($beneficiaries as $index => $b)
        {
            $this->bodyHousehold["beneficiaries"][$index]["vulnerability_criteria"] = [["id" => $vulnerabilityId]];
        }

        $countrySpecific = $this->em->getRepository(CountrySpecific::class)->findOneBy([
            "fieldString" => 'ID Poor',
            "type" => 'Number',
            "countryIso3" => $this->iso3
        ]);
        $country_specific_answers = $this->bodyHousehold["country_specific_answers"];
        $countrySpecificId = $countrySpecific->getId();
        foreach ($country_specific_answers as $index => $c)
        {
            $this->bodyHousehold["country_specific_answers"][$index]["country_specific"] = ["id" => $countrySpecificId];
        }

        $crawler = $this->request(
            'POST',
            '/api/wsse/households/' . $household->getId() . '/project/' . current($projects)->getId(),
            $this->bodyHousehold
        );
        $this->bodyHousehold['fullname'] = $this->namefullnameHousehold;

        $household = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();
        try
        {
            $this->assertArrayHasKey('id', $household);
            $this->assertArrayHasKey('address_street', $household);
            $this->assertArrayHasKey('address_number', $household);
            $this->assertArrayHasKey('address_postcode', $household);
            $this->assertArrayHasKey('livelihood', $household);
            $this->assertArrayHasKey('notes', $household);
            $this->assertArrayHasKey('latitude', $household);
            $this->assertArrayHasKey('longitude', $household);
            $this->assertArrayHasKey('location', $household);
            $this->assertArrayHasKey('country_specific_answers', $household);
            $this->assertArrayHasKey('beneficiaries', $household);
            $location = $household["location"];
            $this->assertArrayHasKey('adm1', $location);
            $this->assertArrayHasKey('adm2', $location);
            $this->assertArrayHasKey('adm3', $location);
            $this->assertArrayHasKey('adm4', $location);
            $country_specific_answer = current($household["country_specific_answers"]);
            $this->assertArrayHasKey('answer', $country_specific_answer);
            $this->assertArrayHasKey('country_specific', $country_specific_answer);
            $beneficiary = current($household["beneficiaries"]);
            $this->assertArrayHasKey('given_name', $beneficiary);
            $this->assertArrayHasKey('family_name', $beneficiary);
            $this->assertArrayHasKey('gender', $beneficiary);
            $this->assertArrayHasKey('status', $beneficiary);
            $this->assertArrayHasKey('date_of_birth', $beneficiary);
            $this->assertArrayHasKey('updated_on', $beneficiary);
            $this->assertArrayHasKey('profile', $beneficiary);
            $this->assertArrayHasKey('vulnerability_criteria', $beneficiary);
            $this->assertArrayHasKey('phones', $beneficiary);
            $this->assertArrayHasKey('national_ids', $beneficiary);
            $profile = $beneficiary["profile"];
            $this->assertArrayHasKey('photo', $profile);
            $vulnerability_criterion = current($beneficiary["vulnerability_criteria"]);
            $this->assertArrayHasKey('id', $vulnerability_criterion);
            $phone = current($beneficiary["phones"]);
            $this->assertArrayHasKey('number', $phone);
            $this->assertArrayHasKey('type', $phone);
            $national_ids = current($beneficiary["national_ids"]);
            $this->assertArrayHasKey('id_number', $national_ids);
            $this->assertArrayHasKey('id_type', $national_ids);

            $this->assertSame($household['address_street'], $this->namefullnameHousehold . '(u)');
        }
        catch (\Exception $exception)
        {
            $this->removeHousehold($this->namefullnameHousehold . "(u)");
            $this->fail("\nThe mapping of fields of Household entity is not correct (2).\n");
            return false;
        }

        return true;
    }

    /**
     * @depends testEditHousehold
     * @param $isSuccess
     * @return bool|void
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetHouseholds($isSuccess)
    {
        if (!$isSuccess)
        {
            print_r("\nThe edition of household failed. We can't test the update.\n");
            $this->markTestIncomplete("The edition of household failed. We can't test the update.");
        }

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/households/get/all');
        $households = json_decode($this->client->getResponse()->getContent(), true);
        if (!empty($households))
        {
            $household = current($households);
            try
            {
                $this->assertArrayHasKey('id', $household);
                $this->assertArrayHasKey('location', $household);
                $this->assertArrayHasKey('beneficiaries', $household);
                $location = $household["location"];
                $this->assertArrayHasKey('adm1', $location);
                $this->assertArrayHasKey('adm2', $location);
                $this->assertArrayHasKey('adm3', $location);
                $this->assertArrayHasKey('adm4', $location);
                $beneficiary = current($household["beneficiaries"]);
                $this->assertArrayHasKey('given_name', $beneficiary);
                $this->assertArrayHasKey('family_name', $beneficiary);
                $this->assertArrayHasKey('vulnerability_criteria', $beneficiary);
                $vulnerability_criterion = current($beneficiary["vulnerability_criteria"]);
                $this->assertArrayHasKey('id', $vulnerability_criterion);
                $this->assertArrayHasKey('field_string', $vulnerability_criterion);
            }
            catch (\Exception $exception)
            {
                $this->removeHousehold($this->namefullnameHousehold . '(u)');
                $this->fail("\nThe mapping of fields of Household entity is not correct (3).\n");
                return false;
            }
        }
        else
        {
            $this->removeHousehold($this->namefullnameHousehold);
            $this->markTestIncomplete("You currently don't have any household in your database.");
        }

        return $this->removeHousehold($this->namefullnameHousehold . '(u)');
    }
}