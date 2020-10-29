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
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class BeneficiaryTestFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private $householdTypes = [
        "single male family" => ['M-25'],
        "single female family" => ['F-25'],
        "mother with kids" => ['F-20', 'F-1', 'F-5', 'M-15'],
        "father with kids" => ['M-20', 'F-1', 'F-5', 'M-15'],
        "old couple" => ['M-60', 'F-55'],
        "grandparents with kids" => ['M-60', 'F-55', 'F-2', 'F-10'],
        "big family" => ['M-20', 'F-18', 'F-1', 'F-5', 'M-15', 'M-60', 'F-55'],
    ];
    private $beneficiaryTemplate = [
        "en_given_name" => "{gender} {age}",
        "en_family_name" => "[{householdType} found by {project}]",
        "local_given_name" => "{gender} {age}",
        "local_family_name" => "{householdType} from {country}",
        "gender" => "0",
        "status" => "1",
        "residency_status" => "resident",
        "vulnerability_criteria" => [
            [
                "id" => 3
            ]
        ],
        "profile" => [
            "photo" => ""
        ],
    ];
    private $householdTemplate = [
        "livelihood" => \ProjectBundle\Enum\Livelihood::GOVERNMENT,
        "income_level" => 3,
        "notes" => null,
        "latitude" => null,
        "longitude" => null,
        "coping_strategies_index" => "2",
        "food_consumption_score" => "3",
        "household_locations" => [],
        "debt_level" => 1,
        "country_specific_answers" => [
          [
            "answer" => "2",
            "country_specific" => [
                "id" => 1
            ],
          ],
          [
              "answer" => null,
              "country_specific" => [
                  "id" => 2
              ],
          ]
        ],
        "beneficiaries" => [],
    ];

    private $householdService;

    private $kernel;

    public function __construct(Kernel $kernel, HouseholdService $householdService)
    {
        $this->householdService = $householdService;
        $this->kernel = $kernel;
    }


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo "Can't run on production environment.";
            return;
        }
        $projects = $manager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            echo "Project {$project->getName()}/{$project->getIso3()}";
            $locationIndex = 0;
            foreach ($this->getTestingLocations($manager, (string)$project->getIso3()) as $location) {
                $locationIndex++;
                $this->createHousehold($manager, $location, $project);
                if (($locationIndex % 5) == 0) {
                    $manager->flush();break;
                }
            }
            echo "\n";
            $manager->flush();
            $manager->clear();
        }
    }

    private function getTestingLocations(ObjectManager $manager, string $iso3)
    {
        $adm1s = $manager->getRepository(Adm1::class)->findBy([
            'countryISO3' => $iso3,
            'name' => [LocationTestFixtures::ADM1_1.$iso3, LocationTestFixtures::ADM1_2.$iso3]
        ]);
        foreach ($adm1s as $adm1) {
            yield $adm1->getLocation();
        }
        $adm2s = $manager->getRepository(Adm2::class)->findBy([
            'adm1' => $adm1s,
            'name' => [LocationTestFixtures::ADM2_1.$iso3, LocationTestFixtures::ADM2_2.$iso3]
        ]);
        foreach ($adm2s as $adm2) {
            yield $adm2->getLocation();
        }
        $adm3s = $manager->getRepository(Adm3::class)->findBy([
            'adm2' => $adm2s,
            'name' => [LocationTestFixtures::ADM3_1.$iso3, LocationTestFixtures::ADM3_2.$iso3]
        ]);
        foreach ($adm3s as $adm3) {
            yield $adm3->getLocation();
        }
        $adm4s = $manager->getRepository(Adm4::class)->findBy([
            'adm3' => $adm3s,
            'name' => [LocationTestFixtures::ADM4_1.$iso3, LocationTestFixtures::ADM4_2.$iso3]
        ]);
        foreach ($adm4s as $adm4) {
            yield $adm4->getLocation();
        }
    }

    /**
     * @param ObjectManager $manager
     * @param Location $location
     * @param Project $project
     * @throws \Exception
     */
    private function createHousehold(ObjectManager $manager, Location $location, Project $project) {
        foreach ($this->householdTypes as $typeName => $members) {
            $household = new Household();

            $household->setLongitude($this->householdTemplate['longitude']);
            $household->setLatitude($this->householdTemplate['latitude']);
            $household->setCopingStrategiesIndex($this->householdTemplate['coping_strategies_index']);
            $household->setDebtLevel($this->householdTemplate['debt_level']);
            $household->setFoodConsumptionScore($this->householdTemplate['food_consumption_score']);
            $household->setIncomeLevel($this->householdTemplate['income_level']);

            foreach ($members as $member) {
                [$gender, $age] = explode('-', $member);
                $bnfData = $this->replacePlaceholders($this->beneficiaryTemplate, [
                    '{age}' => $age,
                    '{project}' => $project->getName(),
                    '{gender}' => $gender === 'F'? 'Female' : 'Male',
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
                $bnf->setGender($gender === 'F'? 0 : 1);
                $bnf->setStatus($household->getBeneficiaries()->count() == 0);
                $bnf->setResidencyStatus($bnfData['residency_status']);

                $household->addBeneficiary($bnf);
                $manager->persist($bnf);
            }

            $householdLocation = $this->getHouseholdLocation($location);
            $householdLocation->setHousehold($household);
            $manager->persist($householdLocation);
            $household->addHouseholdLocation($householdLocation);

            $project->addHousehold($household);
            $household->addProject($project);

            $manager->persist($household);
            echo ".";
        }
        $manager->persist($project);
    }

    private function replacePlaceholders(array $data, array $replaces) {
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
        $hhLocation->setType("residence");
        $hhLocation->setLocationGroup("current");

        $address = new Address();
        $address->setStreet(md5($location->getId().$location->getCode()));
        $address->setNumber($location->getId());
        $address->setPostcode(($location->getId())*1024%10000);
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
