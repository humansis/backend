<?php

namespace DataFixtures\Beneficiaries;

use Utils\HouseholdService;
use DataFixtures\ProjectFixtures;
use DataFixtures\VulnerabilityCriterionFixtures;
use Enum\ResidencyStatus;
use Utils\HouseholdService;
use DataFixtures\CountrySpecificFixtures;
use DataFixtures\InputTypesGenerator\NationalIdCardGenerator;
use DataFixtures\LocationFixtures;
use DataFixtures\ProjectFixtures;
use DataFixtures\VulnerabilityCriterionFixtures;
use Entity\Location;
use Repository\LocationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Enum\HouseholdShelterStatus;
use Enum\PhoneTypes;
use InputType\Beneficiary\Address\CampAddressInputType;
use InputType\Beneficiary\Address\CampInputType;
use InputType\Beneficiary\Address\ResidenceAddressInputType;
use InputType\Beneficiary\Address\TemporarySettlementAddressInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\CountrySpecificsAnswerInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\HouseholdCreateInputType;
use Utils\ValueGenerator\ValueGenerator;
use Entity\Project;
use Enum\Livelihood;
use Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Kernel;

class BeneficiaryFixtures extends Fixture implements DependentFixtureInterface
{

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var HouseholdService
     */
    private $householdService;

    private $kernel;

    public function __construct(
        Kernel             $kernel,
        HouseholdService   $householdService,
        ProjectRepository  $projectRepository,
        LocationRepository $locationRepository
    ) {
        $this->householdService = $householdService;
        $this->kernel = $kernel;
        $this->projectRepository = $projectRepository;
        $this->locationRepository = $locationRepository;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            return;
        }
        foreach ($this->getHouseholdData() as $householdData) {
            $this->householdService->create($this->generateHouseholdInputType($householdData, 'KHM'), 'KHM');
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            LocationFixtures::class,
            VulnerabilityCriterionFixtures::class,
            ProjectFixtures::class,
            CountrySpecificFixtures::class,
        ];
    }

    private function getHouseholdData(): array
    {
        return [
            [
                "livelihood" => Livelihood::valueFromAPI(Livelihood::REGULAR_SALARY_PUBLIC),
                "income" => 3,
                "notes" => null,
                "latitude" => null,
                "longitude" => null,
                "coping_strategies_index" => "2",
                "food_consumption_score" => "3",
                "household_locations" => array(
                    [
                        "location_group" => "current",
                        "type" => "residence",
                        "address" => [
                            "street" => "azerrt",
                            "number" => "1",
                            "postcode" => "12345",
                            "location" => [
                                "adm1" => 1,
                                "adm2" => 1,
                                "adm3" => 1,
                                "adm4" => null,
                                "country_iso3" => "KHM",
                            ],
                        ],
                    ],
                ),
                "country_specific_answers" => [
                    [
                        "answer" => "2",
                        "id" => 1,
                    ],
                    [
                        "answer" => "111222333",
                        "id" => 2,
                    ],
                    [
                        "answer" => 0.0000000,
                        "id" => 3,
                    ],
                ],
                "beneficiaries" => [
                    [
                        "en_given_name" => "Test",
                        "en_family_name" => "Tester",
                        "local_given_name" => "Test",
                        "local_family_name" => "Tester",
                        "local_parents_name" => "Tester",
                        "gender" => 0,
                        "status" => "1",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "10-10-1996",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_disabled')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                ],
            ],
            [
                "livelihood" => Livelihood::valueFromAPI(Livelihood::IRREGULAR_EARNINGS),
                "income" => 3,
                "notes" => null,
                "latitude" => null,
                "longitude" => null,
                "coping_strategies_index" => "2",
                "food_consumption_score" => "3",
                "household_locations" => array(
                    [
                        "location_group" => "current",
                        "type" => "residence",
                        "address" => [
                            "street" => "azerrt",
                            "number" => "1",
                            "postcode" => "12345",
                            "location" => [
                                "adm1" => 1,
                                "adm2" => 1,
                                "adm3" => 1,
                                "adm4" => null,
                                "country_iso3" => "KHM",
                            ],
                        ],
                    ],
                ),
                "country_specific_answers" => [
                    [
                        "answer" => "2",
                        "id" => 1,
                    ],
                    [
                        "answer" => "111222333",
                        "id" => 2,
                    ],
                    [
                        "answer" => 0.99999999,
                        "id" => 3,
                    ],
                ],
                "beneficiaries" => [
                    [
                        "en_given_name" => "Test",
                        "en_family_name" => "Tester",
                        "local_given_name" => "Test",
                        "local_family_name" => "Tester",
                        "local_parents_name" => "Tester",
                        "gender" => 0,
                        "status" => "1",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "10-10-1996",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_disabled')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                    [
                        "en_given_name" => "Test2",
                        "en_family_name" => "Tester",
                        "local_given_name" => "Test2",
                        "local_family_name" => "Tester",
                        "local_parents_name" => "Tester2",
                        "gender" => 1,
                        "status" => "0",
                        "residency_status" => ResidencyStatus::IDP,
                        "date_of_birth" => "10-11-1996",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_chronicallyIll')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                    [
                        "en_given_name" => "Test4",
                        "en_family_name" => "Tester",
                        "local_given_name" => "Test4",
                        "local_family_name" => "Tester",
                        "local_parents_name" => "Tester4",
                        "gender" => 1,
                        "status" => "0",
                        "residency_status" => ResidencyStatus::REFUGEE,
                        "date_of_birth" => "10-12-1995",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_chronicallyIll')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                    [
                        "en_given_name" => "Test5",
                        "en_family_name" => "Tester",
                        "local_given_name" => "Test5",
                        "local_family_name" => "Tester",
                        "local_parents_name" => "Tester5",
                        "gender" => 1,
                        "status" => "0",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "14-10-2000",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_chronicallyIll')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],

                ],
            ],
            [
                "livelihood" => Livelihood::valueFromAPI(Livelihood::FARMING_LIVESTOCK),
                "income" => 4,
                "notes" => null,
                "latitude" => null,
                "longitude" => null,
                "coping_strategies_index" => "4",
                "food_consumption_score" => "5",
                "household_locations" => array(
                    [
                        "location_group" => "current",
                        "type" => "residence",
                        "address" => [
                            "street" => "azerrt",
                            "number" => "2",
                            "postcode" => "12346",
                            "location" => [
                                "adm1" => 1,
                                "adm2" => 1,
                                "adm3" => 1,
                                "adm4" => null,
                                "country_iso3" => "KHM",
                            ],
                        ],
                    ],
                ),
                "country_specific_answers" => [
                    [
                        "answer" => "3",
                        "id" => 1,
                    ],
                    [
                        "answer" => null,
                        "id" => 2,
                    ],
                    [
                        "answer" => 0.5,
                        "id" => 3,
                    ],
                ],
                "beneficiaries" => [
                    [
                        "en_given_name" => "Test6",
                        "en_family_name" => "Bis",
                        "local_given_name" => "Test6",
                        "local_family_name" => "Bis",
                        "local_parents_name" => "Bis6",
                        "gender" => 1,
                        "status" => "1",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "14-10-1995",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_lactating')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                    [
                        "en_given_name" => "Test7",
                        "en_family_name" => "Bis",
                        "local_given_name" => "Test7",
                        "local_family_name" => "Bis",
                        "local_parents_name" => "Bis7",
                        "gender" => 1,
                        "status" => "0",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "15-10-1989",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_lactating')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                    [
                        "en_given_name" => "Test8",
                        "en_family_name" => "Bis",
                        "local_given_name" => "Test8",
                        "local_family_name" => "Bis",
                        "local_parents_name" => "Bis8",
                        "gender" => 1,
                        "status" => "0",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "15-10-1990",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_disabled')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],
                    [
                        "en_given_name" => "Test9",
                        "en_family_name" => "Bis",
                        "local_given_name" => "Test9",
                        "local_family_name" => "Bis",
                        "local_parents_name" => "Bis9",
                        "gender" => 1,
                        "status" => "0",
                        "residency_status" => ResidencyStatus::RESIDENT,
                        "date_of_birth" => "15-08-1989",
                        "vulnerability_criteria" => [
                            [
                                "id" => $this->getReference('vulnerability_chronicallyIll')->getId(),
                            ],
                        ],
                        "phones" => [],
                        "national_ids" => [],
                        "profile" => [
                            "photo" => "",
                        ],
                    ],

                ],
            ],
        ];
    }

    private function generateHouseholdInputType(array $householdData, string $iso3): HouseholdCreateInputType
    {
        $inputType = new HouseholdCreateInputType();
        $inputType->setLivelihood($householdData['livelihood']);
        $inputType->setShelterStatus(ValueGenerator::fromEnum(HouseholdShelterStatus::class));
        $inputType->setProjectIds($this->projectRepository->findAll());
        $inputType->setNotes(ValueGenerator::fromArray([null, 'Fixture note '.ValueGenerator::int(1, 1000), 'Fixture note '.ValueGenerator::int(1,
                1000)]));
        $inputType->setLongitude(null);
        $inputType->setLatitude(null);

        $i = 1;
        foreach ($householdData['beneficiaries'] as $beneficiary) {
            $inputType->addBeneficiary($this->buildBeneficiaryInputType($beneficiary, $i));
            $i++;
        }

        $inputType->setIncome($householdData['income']);
        $inputType->setFoodConsumptionScore($householdData['food_consumption_score']);
        $inputType->setCopingStrategiesIndex($householdData['coping_strategies_index']);
        $inputType->setDebtLevel(ValueGenerator::int(0, 5));
        $inputType->setIncomeSpentOnFood(ValueGenerator::int(0, 5));
        $inputType->setHouseIncome(ValueGenerator::int(0, 5));

        $addressRandom = ValueGenerator::int(0, 2);
        switch ($addressRandom) {
            case 0:
                $inputType->setResidenceAddress($this->buildResidencyAddressInputType($iso3));
                break;
            case 1:
                $inputType->setTemporarySettlementAddress($this->buildTemporarySettlementInputType($iso3));
                break;
            case 2:
                $inputType->setCampAddress($this->buildCampAddressInputType($iso3));
                break;
        }

        foreach ($householdData['country_specific_answers'] as $csoAnswer) {
            $inputType->addCountrySpecificAnswer($this->buildCsoInputType($csoAnswer));
        }

        return $inputType;
    }

    private function buildCsoInputType(array $csoAnswer): CountrySpecificsAnswerInputType
    {
        $csoInputType = new CountrySpecificsAnswerInputType();
        $csoInputType->setAnswer($csoAnswer['answer']);
        $csoInputType->setCountrySpecificId($csoAnswer['id']);

        return $csoInputType;
    }

    private function buildBeneficiaryInputType(array $beneficiary, int $i): BeneficiaryInputType
    {
        $bnfInputType = new BeneficiaryInputType();
        $bnfInputType->setDateOfBirth($beneficiary['date_of_birth']);
        $bnfInputType->setLocalFamilyName( $beneficiary['local_family_name']);
        $bnfInputType->setLocalGivenName($beneficiary['local_given_name']);
        $bnfInputType->setLocalParentsName($beneficiary['local_parents_name']);
        $bnfInputType->setEnFamilyName($beneficiary['en_family_name']);
        $bnfInputType->setEnGivenName($beneficiary['en_given_name']);
        $bnfInputType->setGender($beneficiary['gender']);
        $bnfInputType->addNationalIdCard(NationalIdCardGenerator::generate());
        $bnfInputType->addPhone($this->buildPhoneInputType());
        $bnfInputType->setResidencyStatus($beneficiary['residency_status']);
        $bnfInputType->setIsHead($i === 1);
        foreach ($beneficiary['vulnerability_criteria'] as $vulnerability) {
            $bnfInputType->addVulnerabilityCriteria($vulnerability['id']);
        }

        return $bnfInputType;
    }

    private function buildPhoneInputType(): PhoneInputType
    {
        $phoneInputType = new PhoneInputType();
        $phoneInputType->setType(ValueGenerator::fromEnum(PhoneTypes::class));
        $phoneInputType->setNumber(ValueGenerator::int(100000000, 999999999));
        $phoneInputType->setPrefix((string) ValueGenerator::int(400, 500));

        return $phoneInputType;
    }

    private function buildResidencyAddressInputType(string $iso3): ResidenceAddressInputType
    {
        $residencyInputType = new ResidenceAddressInputType();
        $residencyInputType->setLocationId($this->getLocation($iso3)->getId());
        $residencyInputType->setNumber((string) ValueGenerator::int(1, 1000));
        $residencyInputType->setPostcode((string) ValueGenerator::int(1000, 3000));
        $residencyInputType->setStreet('Street Residency '.ValueGenerator::int(1, 100));

        return $residencyInputType;
    }

    private function buildTemporarySettlementInputType(string $iso3): TemporarySettlementAddressInputType
    {
        $settlementInputType = new TemporarySettlementAddressInputType();
        $settlementInputType->setLocationId($this->getLocation($iso3)->getId());
        $settlementInputType->setNumber((string) ValueGenerator::int(1, 1000));
        $settlementInputType->setPostcode((string) ValueGenerator::int(1000, 3000));
        $settlementInputType->setStreet('Street Temporary '.ValueGenerator::int(1, 100));

        return $settlementInputType;
    }

    private function buildCampAddressInputType(string $iso3): CampAddressInputType
    {
        $campAddress = new CampAddressInputType();
        $campAddress->setCampId(ValueGenerator::int(1, 1000));
        $campAddress->setTentNumber((string) ValueGenerator::int(1, 1000));
        $camp = new CampInputType();
        $camp->setLocationId($this->getLocation($iso3)->getId());
        $camp->setName('Camp '.ValueGenerator::int(1, 1000));
        $campAddress->setCamp($camp);

        return $campAddress;
    }

    /**
     * @param string $iso3
     * @param int    $level
     *
     * @return Location
     */
    private function getLocation(string $iso3, int $level = 1): Location
    {
        $location = $this->locationRepository->findOneBy(['countryISO3' => $iso3, 'lvl' => $level]);
        if (!$location) {
            throw new \LogicException("There is no location in country $iso3 and in level $level");
        }

        return $location;
    }
}
