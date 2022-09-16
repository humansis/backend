<?php
namespace DataFixtures\Beneficiaries;

use InputType\Deprecated\NewCommunityType;
use Utils\CommunityService;
use DataFixtures\LocationFixtures;
use DataFixtures\ProjectFixtures;
use InputType\Country;
use InputType\RequestConverter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Country\Countries;
use Enum\NationalIdType;
use Entity\Project;
use Repository\ProjectRepository;

class CommunityFixture extends Fixture implements DependentFixtureInterface
{
    const COMMUNITIES = [
        [
            'projects' => [1],
            'longitude' => '20,254871',
            'latitude' => '45,47854425',
            'address' => [
                'street' => 'Street name',
                'number' => '1234',
                'postcode' => '147 58',
                'locationId' => 1,
                'location' => [
                    'id' => 1,
                    'country_iso3' => 'KHM',
                ],
            ],
            'national_id' => [
                'type' => NationalIdType::NATIONAL_ID,
                'number' => 'ID: 000-1234-5895-21',
            ],
            'phone_type' => 'Mobile',
            'phone_prefix' => '+4234',
            'phone_number' => '123 456 789',
            'contact_name' => 'Abdul Mohammad',
            'contact_family_name' => 'Qousad',
         ],
        [
            'projects' => [1],
            'longitude' => '120,254871',
            'latitude' => '145,47854425',
            'address' => [
                'street' => 'Street name',
                'number' => '1234',
                'postcode' => '147 58',
                'locationId' => 1,
                'location' => [
                    'id' => 1,
                    'country_iso3' => 'KHM',
                ],
            ],
            'national_id' => [
                'type' => NationalIdType::FAMILY,
                'number' => 'FML: 000-1234-5895-21',
            ],
            'phone_type' => 'Mobile',
            'phone_prefix' => '+4234',
            'phone_number' => '123 456 789',
            'contact_name' => 'Mohammad',
            'contact_family_name' => 'Roubin',
        ],
        [
            'projects' => [3],
            'longitude' => '10,254871',
            'latitude' => '15,47854425',
            'address' => [
                'street' => 'Street name',
                'number' => '1234',
                'postcode' => '147 58',
                'locationId' => 1,
                'location' => [
                    'id' => 1,
                    'country_iso3' => 'SYR',
                ],
            ],
            'national_id' => [
                'type' => NationalIdType::CAMP_ID,
                'number' => 'CMP: 000-1234-5895-21',
            ],
            'phone_type' => 'Mobile',
            'phone_prefix' => '+4234',
            'phone_number' => '123 456 789',
            'contact_name' => 'Schoolman',
            'contact_family_name' => 'Camper',
        ],
    ];

    /** @var string */
    private $environment;

    /** @var Countries */
    private $countries;

    /** @var CommunityService */
    private $communityService;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * CommunityFixture constructor.
     *
     * @param string            $environment
     * @param Countries         $countries
     * @param CommunityService  $communityService
     * @param ProjectRepository $projectRepository
     */
    public function __construct(
        string            $environment,
        Countries         $countries,
        CommunityService  $communityService,
        ProjectRepository $projectRepository
    ) {
        $this->countries = $countries;
        $this->environment = $environment;
        $this->communityService = $communityService;
        $this->projectRepository = $projectRepository;
    }


    public function load(ObjectManager $manager)
    {
        if ($this->environment == "prod") {
            echo "Cannot run on production environment";
            return;
        }
        foreach ($this->countries->getAll() as $COUNTRY) {
            $projects = $this->projectRepository->findBy(['countryIso3' => $COUNTRY->getIso3()], ['id' => 'asc']);
            $projectIds = array_map(function (Project $project) {
                return $project->getId();
            }, $projects);
            foreach (self::COMMUNITIES as $communityTypeData) {
                $communityTypeData['projects'] = $projectIds;
                $communityType = RequestConverter::normalizeInputType($communityTypeData, NewCommunityType::class);

                $institution = $this->communityService->createDeprecated(new Country($COUNTRY->getIso3()), $communityType);
                $manager->persist($institution);
            }
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LocationFixtures::class,
            ProjectFixtures::class,
        ];
    }
}