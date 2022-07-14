<?php
namespace CommonBundle\DataFixtures\Beneficiaries;

use NewApiBundle\Entity\Institution;
use BeneficiaryBundle\InputType\NewInstitutionType;
use BeneficiaryBundle\Utils\InstitutionService;
use CommonBundle\DataFixtures\LocationFixtures;
use CommonBundle\DataFixtures\ProjectFixtures;
use CommonBundle\InputType\Country;
use CommonBundle\InputType\RequestConverter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Enum\NationalIdType;
use ProjectBundle\Entity\Project;
use ProjectBundle\Repository\ProjectRepository;

class InstitutionFixture extends Fixture implements DependentFixtureInterface
{
    const INSTITUTIONS = [
        [
            'name' => 'Local mayor office',
            'type' => Institution::TYPE_GOVERNMENT,
            'projects' => [1],
            'longitude' => '20,254871',
            'latitude' => '45,47854425',
            'address' => [
                'street' => 'Street name',
                'number' => '1234',
                'postcode' => '147 58',
                'location' => [
                    'adm1' => 1,
                    'adm2' => 1,
                    'adm3' => 1,
                    'adm4' => 1,
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
            'name' => 'Food stores inc.',
            'type' => Institution::TYPE_COMMERCE,
            'projects' => [1],
            'longitude' => '120,254871',
            'latitude' => '145,47854425',
            'address' => [
                'street' => 'Street name',
                'number' => '1234',
                'postcode' => '147 58',
                'location' => [
                    'adm1' => 1,
                    'adm2' => 1,
                    'adm3' => 1,
                    'adm4' => 1,
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
            'name' => 'Best CISCO school LTD.',
            'type' => Institution::TYPE_SCHOOL,
            'projects' => [3],
            'longitude' => '10,254871',
            'latitude' => '15,47854425',
            'address' => [
                'street' => 'Street name',
                'number' => '1234',
                'postcode' => '147 58',
                'location' => [
                    'adm1' => 1,
                    'adm2' => 1,
                    'adm3' => 1,
                    'adm4' => 1,
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
    /** @var InstitutionService */
    private $institutionService;
    /** @var Countries */
    private $countries;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * InstitutionFixture constructor.
     *
     * @param string             $environment
     * @param Countries          $countries
     * @param InstitutionService $institutionService
     * @param ProjectRepository  $projectRepository
     */
    public function __construct(
        string             $environment,
        Countries          $countries,
        InstitutionService $institutionService,
        ProjectRepository  $projectRepository
    ) {
        $this->environment = $environment;
        $this->institutionService = $institutionService;
        $this->countries = $countries;
        $this->projectRepository = $projectRepository;
    }

    public function load(ObjectManager $manager)
    {
        if ($this->environment == "prod") {
            echo "Cannot run on production environment";
            return;
        }
        foreach ($this->countries->getAll() as $COUNTRY) {
            $projects = $this->projectRepository->findBy(['iso3' => $COUNTRY->getIso3()], ['id' => 'asc']);
            $projectIds = array_map(function (Project $project) {
                return $project->getId();
            }, $projects);
            foreach (self::INSTITUTIONS as $institutionTypeData) {
                $institutionTypeData['projects'] = $projectIds;
                $institutionType = RequestConverter::normalizeInputType($institutionTypeData, NewInstitutionType::class);

                $institution = $this->institutionService->createDeprecated(new Country($COUNTRY->getIso3()), $institutionType);
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
