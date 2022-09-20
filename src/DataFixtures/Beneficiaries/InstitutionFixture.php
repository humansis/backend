<?php
namespace DataFixtures\Beneficiaries;

use Utils\InstitutionService;
use DataFixtures\LocationFixtures;
use DataFixtures\ProjectFixtures;
use Entity\Institution;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectManager;
use InputType\Beneficiary\AddressInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\InstitutionCreateInputType;
use Component\Country\Countries;
use Enum\NationalIdType;
use Entity\Project;
use Repository\ProjectRepository;

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
                'locationId' => 1,
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
                'locationId' => 1,
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
                'locationId' => 1,
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

    /**
     * @param ObjectManager $manager
     *
     * @return void
     * @throws EntityNotFoundException
     */
    public function load(ObjectManager $manager)
    {
        if ($this->environment == "prod") {
            echo "Cannot run on production environment";

            return;
        }
        foreach ($this->countries->getAll() as $country) {
            foreach (self::INSTITUTIONS as $institutionTypeData) {
                $inputType = $this->buildInstitutionInputType($institutionTypeData, $country->getIso3());
                $this->institutionService->create($inputType);
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            LocationFixtures::class,
            ProjectFixtures::class,
        ];
    }

    private function buildInstitutionInputType(array $institution, string $iso3): InstitutionCreateInputType
    {
        $institutionInputType = new InstitutionCreateInputType();
        $institutionInputType->setName($institution['name']);
        $institutionInputType->setType($institution['type']);
        $institutionInputType->setProjectIds($this->getProjectsIds($iso3));
        $institutionInputType->setLongitude($institution['longitude']);
        $institutionInputType->setLatitude($institution['latitude']);
        $institutionInputType->setAddress(AddressInputType::create($institution['address']['locationId'], $institution['address']['street'],
            $institution['address']['postcode'], $institution['address']['number']));
        $institutionInputType->setNationalIdCard(NationalIdCardInputType::create($institution['national_id']['type'],
            $institution['national_id']['number']));
        $institutionInputType->setPhone(PhoneInputType::create($institution['phone_prefix'], $institution['phone_number'],
            $institution['phone_type']));
        $institutionInputType->setContactGivenName($institution['contact_name']);
        $institutionInputType->setContactFamilyName($institution['contact_family_name']);

        return $institutionInputType;
    }

    /**
     * @return int[]
     */
    private function getProjectsIds(string $iso3): array
    {
        $projects = $this->projectRepository->findBy(['countryIso3' => $iso3], ['id' => 'asc']);

        return array_map(function (Project $project) {
            return $project->getId();
        }, $projects);
    }
}
