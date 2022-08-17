<?php


namespace Tests;

use Entity\Beneficiary;
use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Entity\Household;
use Entity\NationalId;
use Entity\Phone;
use Entity\Profile;
use Entity\VulnerabilityCriterion;
use Utils\HouseholdService;
use Utils\CommodityService;
use Utils\CriteriaAssistanceService;
use Doctrine\ORM\EntityManager;

use Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Entity\User;
use Security\Authentication\Token\WsseUserToken;

class BMSServiceTestCase extends KernelTestCase
{
    /** @var HttpKernelBrowser $client */
    protected $client;
    const USER_PHPUNIT = 'phpunit';
    const USER_TESTER = 'test@example.org';
    const USER_TESTER_VENDOR = 'vendor.eth@example.org';

    // SERVICES

    /** @var EntityManager $em */
    protected $em;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var TokenStorage $tokenStorage */
    protected $tokenStorage;

    /** @var ValidatorInterface $validator */
    protected $validator;

    /** @var HouseholdService $householdService */
    protected $householdService;

    /** @var CommodityService $commodityService */
    protected $commodityService;

    /** @var CriteriaAssistanceService $criteriaAssistanceService */
    protected $criteriaAssistanceService;

    // VARIABLES

    /** @var string $iso3 */
    protected $iso3 = "KHM";

    protected $namefullnameHousehold = "NOTES_TEST";

    protected $bodyHousehold = [
        "livelihood" => \Enum\Livelihood::FARMING_LIVESTOCK,
        "notes" => "NOTES_TEST",
        "latitude" => "1.1544",
        "longitude" => "120.12",
        "coping_strategies_index" => "6",
        "food_consumption_score" => "7",
        "income_spent_on_food" => 1000,
        "household_income" => 100000,
        "household_locations" => array(
            [
                "location_group" => "current",
                "type" => "residence",
                "address" => [
                    "street" => "STREET_TEST",
                    "number" => "NUMBER_TEST",
                    "postcode" => "POSTCODE_TEST",
                    "location" => [
                        "adm1" => 1,
                        "adm2" => 1,
                        "adm3" => 1,
                        "adm4" => 1,
                        "country_iso3" => "KHM",
                    ],
                ]
            ]
        ),
        "country_specific_answers" => [
            [
                "answer" => "MY_ANSWER_TEST1",
                "country_specific" => [
                    "id" => 1
                ]
            ]
        ],
        "beneficiaries" => [
            [
                "en_given_name" => "FIRSTNAME_TEST",
                "en_family_name" => "NAME_TEST",
                "local_given_name" => "FIRSTNAME_TEST",
                "local_family_name" => "NAME_TEST",
                "en_parents_name" => "PARENTSNAME_TEST_EN",
                "local_parents_name" => "PARENTSNAME_TEST_LOCAL",
                "gender" => 1,
                "status" => "1",
                "residency_status" => "IDP",
                "date_of_birth" => "10-06-1999",
                "profile" => [
                    "photo" => "PHOTO_TEST"
                ],
                "vulnerability_criteria" => [
                    [
                        "id" => 2
                    ]
                ],
                "phones" => [
                    [
                        "prefix" => "+855",
                        "number" => "0000_TEST",
                        "type" => "TYPE_TEST",
                        "proxy" => false
                    ]
                ],
                "national_ids" => [
                    [
                        "id_number" => "0000_TEST",
                        "id_type" => "National ID"
                    ]
                ]
            ],
            [
                "en_given_name" => "GIVENNAME_TEST",
                "en_family_name" => "FAMILYNAME_TEST",
                "local_given_name" => "GIVENNAME_TEST",
                "local_family_name" => "FAMILYNAME_TEST",
                "en_parents_name" => "PARENTSNAME_TEST_EN",
                "local_parents_name" => "PARENTSNAME_TEST_LOCAL",
                "gender" => 1,
                "status" => 0,
                "residency_status" => "resident",
                "date_of_birth" => "10-06-1976",
                "profile" => [
                    "photo" => "PHOTO_TEST"
                ],
                "vulnerability_criteria" => [
                    [
                        "id" => 2
                    ]
                ],
                "phones" => [
                    [
                        "prefix" => "+855",
                        "number" => "1111_TEST",
                        "type" => "TYPE_TEST",
                        "proxy" => true
                    ]
                ],
                "national_ids" => [
                    [
                        "id_number" => "1111_TEST",
                        "id_type" => "National ID"
                    ]
                ]
            ],
            [
                "en_given_name" => "GIVENNAME_TEST",
                "en_family_name" => "FAMILYNAME_TEST",
                "local_given_name" => "GIVENNAME_TEST",
                "local_family_name" => "FAMILYNAME_TEST",
                "en_parents_name" => "PARENTSNAME_TEST_EN",
                "local_parents_name" => "PARENTSNAME_TEST_LOCAL",
                "gender" => 1,
                "status" => 0,
                "residency_status" => "returnee",
                "date_of_birth" => "10-06-1976",
                "profile" => [
                    "photo" => "PHOTO_TEST"
                ],
                "vulnerability_criteria" => [
                    [
                        "id" => 2
                    ]
                ],
                "phones" => [
                    [
                        "prefix" => "+855",
                        "number" => "1111_TEST",
                        "type" => "TYPE_TEST",
                        "proxy" => true
                    ]
                ],
                "national_ids" => [
                    [
                        "id_number" => "1111_TEST",
                        "id_type" => "National ID"
                    ]
                ]
            ]
        ],
        "assets" => [1, 2],
        "debt_level" => 2,
        "support_received_types" => null,
        "support_organization_name" => "Abcd Ltd.",
        "support_date_received" => "22-02-2020",
        "enumerator_name" => "ENUMERATOR_NAME_TEST",
    ];

    /**
     * @var $defaultSeralizerName
     * If you plan to use another serializer, use the setter before calling this setUp Method in the child class setUp method.
     * Ex :
     * function setUp(){
     *      $this->setDefaultSerializerName("serializer");
     *      parent::setUp();
     * }
     */
    private $defaultSerializerName = "serializer";

    protected function request($method, $uri, $body = [], $files = [], $headers = null)
    {
        $headers = array_merge([
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW'   => 'pin1234'
        ], (array) $headers);
        $this->client->request($method, $uri, $body, $files, $headers);
    }


    public function setDefaultSerializerName($serializerName)
    {
        $this->defaultSerializerName = $serializerName;
        return $this;
    }


    public function setUpFunctionnal()
    {
        self::bootKernel();


        //Preparing the EntityManager
        $this->em = self::$container
            ->get('doctrine')
            ->getManager();

        //Mocking Serializer, Container
        $this->serializer = self::$container
            ->get($this->defaultSerializerName);

        //Symdfony Validator
        $this->validator = self::$container
            ->get('validator');

        //setting the token_storage
        $this->tokenStorage = self::$container->get('security.token_storage');
        $this->householdService = self::$container->get('beneficiary.household_service');
        $this->commodityService = self::$container->get('distribution.commodity_service');
    }


    public function setUpUnitTest()
    {
        //EntityManager mocking
        $this->mockEntityManager(['getRepository']);
        //Serializer mocking
        $this->mockSerializer();
        //Container mocking
        $this->mockContainer();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        //parent::tearDown();
        if (!empty($this->em)) {
            //$this->em->close();
            unset($this->em);
            $this->em = null; // avoid memory leaks
        }
    }

    /**
     * Mock the EntityManager with the given functions
     * @param array $requiredFunctions [names of functions to setup on the mock]
     * @return EntityManager {[MockClass]       [a mock instance of EntityManager]
     */
    protected function mockEntityManager(array $requiredFunctions)
    {
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->setMethods($requiredFunctions)
            ->disableOriginalConstructor()
            ->getMock();
        return $this->em;
    }

    protected function mockSerializer()
    {
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(['serialize', 'deserialize'])
            ->disableOriginalConstructor()
            ->getMock();
        return $this->serializer;
    }

    protected function mockRepository($repositoryClass, array $requiredFunctions)
    {
        return $this->getMockBuilder($repositoryClass)
            ->disableOriginalConstructor()
            ->setMethods($requiredFunctions)
            ->getMock();
    }

    protected function mockContainer()
    {
        self::$container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        self::$container->method('get')
            ->with($this->defaultSerializerName)
            ->will($this->returnValue($this->serializer));
        return self::$container;
    }

    protected function getUserToken(User $user)
    {
        $token = new WsseUserToken($user->getRoles());
        $token->setUser($user);

        return $token;
    }

    /**
     * Require Functional tests and real Entity Manager
     * @param string $username
     * @return null|object|User {[type] [description]
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getTestUser(string $username)
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user instanceof User) {
            return $user;
        }

        $user = new User();
        $user->setUsername($username)
            ->setEmail($username)
            ->setPassword("");
        $user->setPhoneNumber("")
            ->setPhonePrefix("")
            ->setTwoFactorAuthentication(0);
        $user->setChangePassword(0);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @return bool|mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function createHousehold()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);


        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects)) {
            print_r("There is no project inside your database");
            return false;
        }

        $vulnerabilityCriterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneBy([
            "fieldString" => "disabled"
        ], ['id' => 'asc']);
        $beneficiaries = $this->bodyHousehold["beneficiaries"];
        $vulnerabilityId = $vulnerabilityCriterion->getId();
        foreach ($beneficiaries as $index => $b) {
            $this->bodyHousehold["beneficiaries"][$index]["vulnerability_criteria"] = [["id" => $vulnerabilityId]];
        }

        $countrySpecific = $this->em->getRepository(CountrySpecific::class)->findOneBy([
            "fieldString" => 'IDPoor',
            "type" => 'number',
            "countryIso3" => $this->iso3
        ], ['id' => 'asc']);
        $country_specific_answers = $this->bodyHousehold["country_specific_answers"];
        $countrySpecificId = $countrySpecific->getId();
        foreach ($country_specific_answers as $index => $c) {
            $this->bodyHousehold["country_specific_answers"][$index]["country_specific"] = ["id" => $countrySpecificId];
        }

        $this->bodyHousehold["__country"] = $this->iso3;
        $household = $this->householdService->createOrEdit(
            $this->bodyHousehold,
            [current($projects)]
        );

        $json = $this->serializer
            ->serialize(
                $household,
                'json',
                ['groups' => ["FullHousehold"], 'datetime_format' => 'd-m-Y']
            );

        return json_decode($json, true);
    }

    /**
     * @depends testGetHouseholds
     *
     * @param $notes
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeHousehold($notes)
    {
        $this->em->clear();
        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)->findOneByNotes($notes);
        if ($household instanceof Household) {
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
            if (!empty($beneficiaries)) {
                /** @var Beneficiary $beneficiary */
                foreach ($beneficiaries as $beneficiary) {
                    $phones = $this->em->getRepository(Phone::class)->findByPerson($beneficiary->getPerson());
                    $nationalIds = $this->em->getRepository(NationalId::class)->findByPerson($beneficiary->getPerson());
                    $profile = $this->em->getRepository(Profile::class)->find($beneficiary->getProfile());
                    if ($profile instanceof Profile) {
                        $this->em->remove($profile);
                    }
                    $this->em->remove($beneficiary->getPerson());
                    foreach ($phones as $phone) {
                        $this->em->remove($phone);
                    }
                    foreach ($nationalIds as $nationalId) {
                        $this->em->remove($nationalId);
                    }
                    $this->em->remove($beneficiary->getProfile());
                    $this->em->remove($beneficiary);
                }
            }

            $countrySpecificAnswers = $this->em->getRepository(CountrySpecificAnswer::class)
                ->findByHousehold($household);
            foreach ($countrySpecificAnswers as $countrySpecificAnswer) {
                $this->em->remove($countrySpecificAnswer);
            }

            $this->em->remove($household);
            $this->em->flush();
        }

        return true;
    }

    /**
     * Checks whether $expected array is fully contained in $actual array.
     *
     * @param        $expected
     * @param        $actual
     * @param string $message
     */
    public static function assertArrayFragment($expected, $actual, $message = '')
    {
        $constraint = new \Utils\Test\Contraint\MatchArrayFragment($expected);

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Checks whether $expected json string is fully contained in $actual json string.
     *
     * @param        $expected
     * @param        $actual
     * @param string $message
     */
    public static function assertJsonFragment($expected, $actual, $message = '')
    {
        static::assertJson($expected);
        static::assertJson($actual);
        static::assertArrayFragment(json_decode($expected, true), json_decode($actual, true), $message);
    }
}
