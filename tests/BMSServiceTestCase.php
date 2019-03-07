<?php


namespace Tests;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Utils\HouseholdService;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Security\Authentication\Token\WsseUserToken;


class BMSServiceTestCase extends KernelTestCase
{
    /** @var Client $client */
    protected $client;
    const USER_PHPUNIT = 'phpunit';
    const USER_TESTER = 'tester';

    // SERVICES

    /** @var EntityManager $em */
    protected $em;

    /** @var Container $container */
    protected $container;

    /** @var $serializer */
    protected $serializer;

    /** @var TokenStorage $tokenStorage */
    protected $tokenStorage;

    /** @var ValidatorInterface $validator */
    protected $validator;

    /** @var HouseholdService $householdService */
    protected $householdService;

    /** @var CommodityService $commodityService */
    protected $commodityService;

    /** @var ConfigurationLoader $configurationLoader */
    protected $configurationLoader;

    /** @var CriteriaDistributionService $criteriaDistributionService */
    protected $criteriaDistributionService;

    // VARIABLES

    /** @var string $iso3 */
    protected $iso3 = "KHM";

    protected $namefullnameHousehold = "STREET_TEST";

    protected $bodyHousehold = [
        "address_street" => "STREET_TEST",
        "address_number" => "NUMBER_TEST",
        "address_postcode" => "POSTCODE_TEST",
        "livelihood" => 10,
        "notes" => "NOTES_TEST",
        "latitude" => "1.1544",
        "longitude" => "120.12",
        "location" => [
            "adm1" => "Rhone-Alpes",
            "adm2" => "Savoie",
            "adm3" => "Chambery",
            "adm4" => "Sainte Hélène sur Isère"
        ],
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
                "given_name" => "FIRSTNAME_TEST",
                "family_name" => "NAME_TEST",
                "gender" => 1,
                "status" => 1,
                "residency_status" => "idp",
                "date_of_birth" => "1976-10-06",
                "profile" => [
                    "photo" => "PHOTO_TEST"
                ],
                "vulnerability_criteria" => [
                    [
                        "id" => 1
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
                        "id_type" => "ID_TYPE_TEST"
                    ]
                ]
            ],
            [
                "given_name" => "GIVENNAME_TEST",
                "family_name" => "FAMILYNAME_TEST",
                "gender" => 1,
                "status" => 0,
                "residency_status" => "resident",
                "date_of_birth" => "1976-10-06",
                "profile" => [
                    "photo" => "PHOTO_TEST"
                ],
                "vulnerability_criteria" => [
                    [
                        "id" => 1
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
                        "id_type" => "ID_TYPE_TEST"
                    ]
                ]
            ]
        ]
    ];

    /**
     * @var $defaultSeralizerName
     * If you plan to use another serializer, use the setter before calling this setUp Method in the child class setUp method.
     * Ex :
     * function setUp(){
     *      $this->setDefaultSerializerName("jms_serializer");
     *      parent::setUp();
     * }
     */
    private $defaultSerializerName = "serializer";

    protected function request($method, $uri, $body = [], $files = [], $headers = null)
    {
        $this->client->request($method, $uri, $body, $files, (null === $headers) ? ['HTTP_COUNTRY' => 'KHM'] : $headers);
    }


    public function setDefaultSerializerName($serializerName)
    {
        $this->defaultSerializerName = $serializerName;
        return $this;
    }


    public function setUpFunctionnal()
    {

        self::bootKernel();

        $this->container = static::$kernel->getContainer();

        //Preparing the EntityManager
        $this->em = $this->container
            ->get('doctrine')
            ->getManager();

        //Mocking Serializer, Container
        $this->serializer = $this->container
            ->get($this->defaultSerializerName);

        //Symdfony Validator 
        $this->validator = $this->container
            ->get('validator');

        //setting the token_storage
        $this->tokenStorage = $this->container->get('security.token_storage');
        $this->householdService = $this->container->get('beneficiary.household_service');

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
        if (!empty($this->em))
        {
            //$this->em->close();
            unset($this->em);
            $this->em = null; // avoid memory leaks
        }
    }

    /**
     * Mock the EntityManager with the given functions
     * @param  array $requiredFunctions [names of functions to setup on the mock]
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
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->method('get')
            ->with($this->defaultSerializerName)
            ->will($this->returnValue($this->serializer));
        return $this->container;
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

        if ($user instanceOf User)
        {
            return $user;
        }

        $user = new User();
        $user->setUsername($username)
            ->setEmail($username)
            ->setPassword("");
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
        if (empty($projects))
        {
            print_r("There is no project inside your database");
            return false;
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
            "fieldString" => 'IDPoor',
            "type" => 'number',
            "countryIso3" => $this->iso3
        ]);
        $country_specific_answers = $this->bodyHousehold["country_specific_answers"];
        $countrySpecificId = $countrySpecific->getId();
        foreach ($country_specific_answers as $index => $c)
        {
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
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );

        return json_decode($json, true);
    }

    /**
     * @depends testGetHouseholds
     *
     * @param $addressStreet
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeHousehold($addressStreet)
    {
        $this->em->clear();
        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)->findOneByAddressStreet($addressStreet);
        if ($household instanceof Household)
        {
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
            if (!empty($beneficiaries))
            {
                /** @var Beneficiary $beneficiary */
                foreach ($beneficiaries as $beneficiary)
                {
                    $phones = $this->em->getRepository(Phone::class)->findByBeneficiary($beneficiary);
                    $nationalIds = $this->em->getRepository(NationalId::class)->findByBeneficiary($beneficiary);
                    $profile = $this->em->getRepository(Profile::class)->find($beneficiary->getProfile());
                    if ($profile instanceof Profile)
                        $this->em->remove($profile);
                    foreach ($phones as $phone)
                    {
                        $this->em->remove($phone);
                    }
                    foreach ($nationalIds as $nationalId)
                    {
                        $this->em->remove($nationalId);
                    }
                    $this->em->remove($beneficiary->getProfile());
                    $this->em->remove($beneficiary);
                }
            }

            $countrySpecificAnswers = $this->em->getRepository(CountrySpecificAnswer::class)
                ->findByHousehold($household);
            foreach ($countrySpecificAnswers as $countrySpecificAnswer)
            {
                $this->em->remove($countrySpecificAnswer);
            }

            $this->em->remove($household);
            $this->em->flush();
        }

        return true;
    }
}