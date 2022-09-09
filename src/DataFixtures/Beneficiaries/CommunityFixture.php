<?php
namespace DataFixtures\Beneficiaries;

use Utils\CommunityService;
use DataFixtures\LocationFixtures;
use DataFixtures\ProjectFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Country\Countries;
use Enum\NationalIdType;
use InputType\Beneficiary\AddressInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\CommunityCreateInputType;
use Entity\Project;
use Repository\ProjectRepository;

class CommunityFixture extends Fixture implements DependentFixtureInterface
{
    const COMMUNITIES = [
        [
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
        foreach ($this->countries->getAll() as $country) {
            foreach (self::COMMUNITIES as $communityTypeData) {
                $this->communityService->create($this->buildCommunityInputType($communityTypeData, $country->getIso3()));
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

    private function buildCommunityInputType(array $community, string $iso3): CommunityCreateInputType
    {
        $communityInputType = new CommunityCreateInputType();
        $communityInputType->setProjectIds($this->getProjectsIds($iso3));
        $communityInputType->setLongitude($community['longitude']);
        $communityInputType->setLatitude('latitude');
        $communityInputType->setAddress($this->buildAddress($community['address']));
        $communityInputType->setNationalIdCard($this->buildNationIdCard($community['national_id']));
        $communityInputType->setPhone($this->buildPhone($community));
        $communityInputType->setContactFamilyName($community['contact_family_name']);
        $communityInputType->setContactGivenName($community['contact_name']);

        return $communityInputType;
    }

    private function buildPhone($community): PhoneInputType
    {
        $phoneInputType = new PhoneInputType();
        $phoneInputType->setType($community['phone_type']);
        $phoneInputType->setNumber($community['phone_number']);
        $phoneInputType->setPrefix($community['phone_prefix']);

        return $phoneInputType;
    }

    private function buildNationIdCard(array $nationalId): NationalIdCardInputType
    {
        $nationalInputType = new NationalIdCardInputType();
        $nationalInputType->setNumber($nationalId['number']);
        $nationalInputType->setType($nationalId['type']);

        return $nationalInputType;
    }

    private function buildAddress(array $address): AddressInputType
    {
        $addressInputType = new AddressInputType();
        $addressInputType->setLocationId($address['locationId']);
        $addressInputType->setStreet($address['street']);
        $addressInputType->setPostcode($address['postcode']);
        $addressInputType->setNumber($address['number']);

        return $addressInputType;
    }

    /**
     * @param string $iso3
     *
     * @return int[]
     */
    private function getProjectsIds(string $iso3): array
    {
        $projects = $this->projectRepository->findBy(['iso3' => $iso3], ['id' => 'asc']);

        return array_map(function (Project $project) {
            return $project->getId();
        }, $projects);
    }
}
