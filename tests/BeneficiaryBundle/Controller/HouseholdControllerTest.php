<?php


namespace Tests\BeneficiaryBundle\Controller;


use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;

class HouseholdControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;

    private $body = [
        "address_street" => "addr",
        "address_number" => "12",
        "address_postcode" => "73460",
        "livelihood" => 10,
        "notes" => "this is just some notes",
        "latitude" => "1.1544",
        "longitude" => "120.12",
        "location" => [
            "country_iso3" => "FRA",
            "adm1" => "Auvergne Rhone-Alpes",
            "adm2" => "Savoie",
            "adm3" => "Chambery",
            "adm4" => "Ste Hélène sur Isère"
        ],
        "country_specific_answers" => [
            [
                "answer" => "my answer",
                "country_specific" => [
                    "id" => 1
                ]
            ]
        ],
        "beneficiaries" => [
            [
                "given_name" => "name",
                "family_name" => "family",
                "gender" => "h",
                "status" => 0,
                "date_of_birth" => "1976-10-06",
                "updated_on" => "2018-06-13 12:12:12",
                "profile" => [
                    "photo" => "gkjghjk"
                ],
                "vulnerability_criterion" => [
                    [
                        "id" => 1
                    ]
                ],
                "phones" => [
                    [
                        "number" => "020254512",
                        "type" => "type1"
                    ]
                ],
                "national_ids" => [
                    [
                        "id_number" => "1212",
                        "id_type" => "type1"
                    ]
                ]
            ]
        ]
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
    public function testCreateHousehold()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('PUT', '/api/wsse/households', $this->body, [], ['HTTP_COUNTRY' => 'KHM']);
        dump($this->client->getResponse()->getContent());
        $household = json_decode($this->client->getResponse()->getContent(), true);
        dump($household);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        try
        {
            $this->assertArrayHasKey('id', $household);
            $this->assertArrayHasKey('address_street', $household);
            $this->assertArrayHasKey('address_number', $household);
            $this->assertArrayHasKey('address_postcode', $household);
            $this->assertArrayHasKey('livelihood', $household);
            $this->assertArrayHasKey('notes', $household);
            $this->assertArrayHasKey('latitpude', $household);
            $this->assertArrayHasKey('longitude', $household);
            $this->assertArrayHasKey('location', $household);
            $this->assertArrayHasKey('country_specific_answers', $household);
            $this->assertArrayHasKey('beneficiaries', $household);
            $location = $household["location"];
            $this->assertArrayHasKey('country_iso3', $location);
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
            $this->assertArrayHasKey('vulnerability_criterion', $beneficiary);
            $this->assertArrayHasKey('phones', $beneficiary);
            $this->assertArrayHasKey('national_ids', $beneficiary);
            $profile = $household["profile"];
            $this->assertArrayHasKey('photo', $profile);
            $vulnerability_criterion = current($household["vulnerability_criterion"]);
            $this->assertArrayHasKey('id', $vulnerability_criterion);
            $phone = current($household["phones"]);
            $this->assertArrayHasKey('number', $phone);
            $this->assertArrayHasKey('type', $phone);
            $national_ids = current($household["national_ids"]);
            $this->assertArrayHasKey('id_number', $national_ids);
            $this->assertArrayHasKey('id_type', $national_ids);
        }
        catch (\Exception $exception)
        {
            print_r("\nThe mapping of fields of Household entity is not correct.\n");
//            $this->remove($this->namefullname);
            return false;
        }

        return false;
    }

    /**
     * @depends testCreateHousehold
     * @throws \Exception
     */
    public function testEditHousehold($isSuccess = true)
    {
        if (!$isSuccess)
        {
            print_r("\nThe creation of household failed. We can't test the update.\n");
            $this->markTestIncomplete("The creation of household failed. We can't test the update.");
        }


        $this->em->clear();
        $household = $this->em->getRepository(Household::class)->findOneByFullname($this->namefullname);
        if (!$household instanceof Household)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->em->clear();

        $this->body['fullname'] .= '(u)';
        $crawler = $this->client->request('POST', '/api/wsse/households/' . $household->getId(), $this->body);
        $this->body['fullname'] = $this->namefullname;

        $household = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();
        try
        {
            $this->assertArrayHasKey('id', $household);
            $this->assertArrayHasKey('fullname', $household);
            $this->assertArrayHasKey('shortname', $household);
            $this->assertArrayHasKey('date_added', $household);
            $this->assertArrayHasKey('notes', $household);
            $this->assertSame($household['fullname'], $this->namefullname . '(u)');
        }
        catch (\Exception $exception)
        {
            $this->remove($this->namefullname);
            return false;
        }

        return true;
    }

    /**
     * @depends testEditHousehold
     * @throws \Exception
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

        $crawler = $this->client->request('GET', '/api/wsse/households');
        $households = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($households))
        {
            $household = $households[0];

            $this->assertArrayHasKey('id', $household);
            $this->assertArrayHasKey('fullname', $household);
            $this->assertArrayHasKey('shortname', $household);
            $this->assertArrayHasKey('date_added', $household);
            $this->assertArrayHasKey('notes', $household);
        }
        else
        {
            $this->markTestIncomplete("You currently don't have any household in your database.");
        }

        return $this->remove($this->namefullname . '(u)');
    }

    /**
     * @depends testGetHouseholds
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove($name)
    {
        $this->em->clear();
        $household = $this->em->getRepository(Household::class)->findOneByFullname($name);
        if ($household instanceof Household)
        {
            $this->em->remove($household);
            $this->em->flush();
        }
    }
}