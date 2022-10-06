<?php

namespace DataFixtures;

use DateTime;
use Entity\Address;
use Entity\Beneficiary;
use Entity\Household;
use Entity\HouseholdLocation;
use Exception;
use Utils\HouseholdService;
use Entity\Adm1;
use Entity\Adm2;
use Entity\Adm3;
use Entity\Adm4;
use Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Enum\PersonGender;
use Entity\Project;
use Enum\Livelihood;
use Symfony\Component\HttpKernel\Kernel;

class BeneficiaryTestFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private $householdTypes = [
        'single male family' => ['M-25'],
        'single female family' => ['F-25'],
        'mother with kids' => ['F-20', 'F-1', 'F-5', 'M-15'],
        'father with kids' => ['M-20', 'F-1', 'F-5', 'M-15'],
        'old couple' => ['M-60', 'F-55'],
        'grandparents with kids' => ['M-60', 'F-55', 'F-2', 'F-10'],
    ];

    private $beneficiaryTemplate = [
        'en_given_name' => '{gender} {age}',
        'en_family_name' => '[{householdType} found by {project}]',
        'local_given_name' => '{gender} {age}',
        'local_family_name' => '{householdType} from {country}',
        'gender' => 0,
        'status' => '1',
        'residency_status' => 'resident',
        'vulnerability_criteria' => [
            [
                'id' => 3,
            ],
        ],
        'profile' => [
            'photo' => '',
        ],
    ];

    private $householdTemplate = [
        'livelihood' => Livelihood::REGULAR_SALARY_PUBLIC,
        'income' => 3,
        'notes' => null,
        'latitude' => null,
        'longitude' => null,
        'coping_strategies_index' => '2',
        'food_consumption_score' => '3',
        'household_locations' => [],
        'debt_level' => 1,
        'country_specific_answers' => [
            [
                'answer' => '2',
                'country_specific' => [
                    'id' => 1,
                ],
            ],
            [
                'answer' => null,
                'country_specific' => [
                    'id' => 2,
                ],
            ],
        ],
        'beneficiaries' => [],
    ];

    private $householdService;

    private $kernel;

    public function __construct(Kernel $kernel, HouseholdService $householdService)
    {
        $this->householdService = $householdService;
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            echo "Can't run on production environment.";

            return;
        }

        srand(42);

        $projects = $manager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            echo "Project {$project->getId()}# {$project->getName()}/{$project->getCountryIso3()}";
            $location = $this->randomLocation($manager, $project->getCountryIso3());
            $this->createHouseholds($manager, $location, $project);
            $this->createIndividuals($manager, $location, $project);

            echo "\n";
            $manager->flush();
        }
    }

    private function randomLocation(ObjectManager $manager, string $countryIso3): ?Location
    {
        $entities = $manager->getRepository(Location::class)->getByCountry($countryIso3);
        if (0 === count($entities)) {
            return null;
        }

        $i = rand(0, count($entities) - 1);

        return $entities[$i];
    }

    /**
     * @param ObjectManager $manager
     * @param Location $location
     * @param Project $project
     *
     * @throws Exception
     */
    private function createHouseholds(ObjectManager $manager, Location $location, Project $project)
    {
        foreach ($this->householdTypes as $typeName => $members) {
            $this->createHousehold($manager, $location, $project, $typeName, $members);
            $manager->flush();
        }
    }

    private function createIndividuals(ObjectManager $manager, Location $location, Project $project)
    {
        $singles = [
            $this->householdTypes['single male family'],
            $this->householdTypes['single female family'],
        ];
        foreach ($singles as $singleFamily) {
            $this->createHousehold($manager, $location, $project, "Individual", $singleFamily);
        }
        $manager->flush();
    }

    private function createHousehold(ObjectManager $manager, Location $location, Project $project, string $typeName, array $members)
    {
        $household = new Household();

        $household->setLongitude($this->householdTemplate['longitude']);
        $household->setLatitude($this->householdTemplate['latitude']);
        $household->setCopingStrategiesIndex($this->householdTemplate['coping_strategies_index']);
        $household->setDebtLevel($this->householdTemplate['debt_level']);
        $household->setFoodConsumptionScore($this->householdTemplate['food_consumption_score']);
        $household->setIncome($this->householdTemplate['income']);
        $household->setCountryIso3($project->getCountryIso3());

        foreach ($members as $member) {
            [$gender, $age] = explode('-', $member);
            $bnfData = $this->replacePlaceholders($this->beneficiaryTemplate, [
                '{age}' => $age,
                '{project}' => $project->getName(),
                '{gender}' => PersonGender::valueFromAPI($gender),
                '{householdType}' => $typeName,
                '{country}' => $project->getCountryIso3(),
            ]);

            $bnf = new Beneficiary();
            $bnf->setHousehold($household);
            $birthDate = new DateTime();
            $birthDate->modify("-$age year");
            $bnf->setDateOfBirth($birthDate);
            $bnf->setEnFamilyName($bnfData['en_family_name']);
            $bnf->setEnGivenName($bnfData['en_given_name']);
            $bnf->setLocalFamilyName($bnfData['local_family_name']);
            $bnf->setLocalGivenName($bnfData['local_given_name']);
            $bnf->setGender(PersonGender::valueFromAPI($gender));
            $bnf->setStatus(0 == $household->getBeneficiaries()->count());
            $bnf->setResidencyStatus($bnfData['residency_status']);

            $household->addBeneficiary($bnf);
            $bnf->addProject($project);
            $manager->persist($bnf);
        }

        $householdLocation = $this->getHouseholdLocation($location);
        $householdLocation->setHousehold($household);
        $manager->persist($householdLocation);
        $household->addHouseholdLocation($householdLocation);

        $household->addProject($project);

        $manager->persist($household);
        echo '.';
    }

    private function replacePlaceholders(array $data, array $replaces)
    {
        foreach ($data as $key => $value) {
            $newValue = $value;
            foreach ($replaces as $placeholder => $replace) {
                $newValue = str_replace($placeholder, $replace, $newValue);
            }
            $data[$key] = $newValue;
        }

        return $data;
    }

    private function getHouseholdLocation(Location $location): HouseholdLocation
    {
        $hhLocation = new HouseholdLocation();
        $hhLocation->setType('residence');
        $hhLocation->setLocationGroup('current');

        $address = new Address();
        $address->setStreet(md5($location->getId() . $location->getCode()));
        $address->setNumber($location->getId());
        $address->setPostcode(rand(10001, 99999));
        $address->setLocation($location);
        $hhLocation->setAddress($address);

        return $hhLocation;
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
        ];
    }
}
