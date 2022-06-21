<?php

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Utils\HouseholdService;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Enum\PersonGender;
use ProjectBundle\Entity\Project;
use ProjectBundle\Enum\Livelihood;
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
            echo "Project {$project->getId()}# {$project->getName()}/{$project->getIso3()}";
            $location = $this->randomLocation($manager, $project->getIso3());
            $this->createHouseholds($manager, $location, $project);
            $this->createIndividuals($manager, $location, $project);

            echo "\n";
            $manager->flush();
        }
    }

    private function getTestingLocations(ObjectManager $manager, string $iso3)
    {
        $adm1s = $manager->getRepository(Adm1::class)->findBy([
            'countryISO3' => $iso3,
            'name' => [LocationTestFixtures::ADM1_1.$iso3, LocationTestFixtures::ADM1_2.$iso3],
        ], ['id' => 'asc']);
        foreach ($adm1s as $adm1) {
            yield $adm1->getLocation();
        }
        $adm2s = $manager->getRepository(Adm2::class)->findBy([
            'adm1' => $adm1s,
            'name' => [LocationTestFixtures::ADM2_1.$iso3, LocationTestFixtures::ADM2_2.$iso3],
        ], ['id' => 'asc']);
        foreach ($adm2s as $adm2) {
            yield $adm2->getLocation();
        }
        $adm3s = $manager->getRepository(Adm3::class)->findBy([
            'adm2' => $adm2s,
            'name' => [LocationTestFixtures::ADM3_1.$iso3, LocationTestFixtures::ADM3_2.$iso3],
        ], ['id' => 'asc']);
        foreach ($adm3s as $adm3) {
            yield $adm3->getLocation();
        }
        $adm4s = $manager->getRepository(Adm4::class)->findBy([
            'adm3' => $adm3s,
            'name' => [LocationTestFixtures::ADM4_1.$iso3, LocationTestFixtures::ADM4_2.$iso3],
        ], ['id' => 'asc']);
        foreach ($adm4s as $adm4) {
            yield $adm4->getLocation();
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
     * @param Location      $location
     * @param Project       $project
     *
     * @throws \Exception
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

        foreach ($members as $member) {
            [$gender, $age] = explode('-', $member);
            $bnfData = $this->replacePlaceholders($this->beneficiaryTemplate, [
                '{age}' => $age,
                '{project}' => $project->getName(),
                '{gender}' => PersonGender::valueFromAPI($gender),
                '{householdType}' => $typeName,
                '{country}' => $project->getIso3(),
            ]);

            $bnf = new Beneficiary();
            $bnf->setHousehold($household);
            $birthDate = new \DateTime();
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
        $address->setStreet(md5($location->getId().$location->getCode()));
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
            LocationTestFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
