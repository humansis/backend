<?php

namespace DataFixtures\Beneficiaries;

use Doctrine\ORM\EntityNotFoundException;
use Enum\EnumValueNoFoundException;
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
    final public const COMMUNITIES = [
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
                'number' => 'ID: 000-1234-5895-211',
                'priority' => 1,
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
                'number' => 'FML: 000-1234-5895-211',
                'priority' => 1,
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
                'number' => 'CMP: 000-1234-5895-211',
                'priority' => 1,
            ],
            'phone_type' => 'Mobile',
            'phone_prefix' => '+4234',
            'phone_number' => '123 456 789',
            'contact_name' => 'Schoolman',
            'contact_family_name' => 'Camper',
        ],
    ];

    /**
     * CommunityFixture constructor.
     */
    public function __construct(private readonly string $environment, private readonly Countries $countries, private readonly CommunityService $communityService, private readonly ProjectRepository $projectRepository)
    {
    }

    /**
     *
     * @throws EntityNotFoundException
     * @throws EnumValueNoFoundException
     */
    public function load(ObjectManager $manager)
    {
        if ($this->environment == "prod") {
            echo "Cannot run on production environment";

            return;
        }
        foreach ($this->countries->getAll() as $country) {
            foreach (self::COMMUNITIES as $communityTypeData) {
                $this->communityService->create(
                    $this->buildCommunityInputType($communityTypeData, $country->getIso3())
                );
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
        $communityInputType->setAddress(
            AddressInputType::create(
                $community['address']['locationId'],
                $community['address']['street'],
                $community['address']['postcode'],
                $community['address']['number']
            )
        );
        $communityInputType->setNationalIdCard(
            NationalIdCardInputType::create(
                $community['national_id']['type'],
                $community['national_id']['number'] . - $this->getUniqueNumber()
            )
        );
        $communityInputType->setPhone(
            PhoneInputType::create($community['phone_prefix'], $community['phone_number'], $community['phone_type'])
        );
        $communityInputType->setContactFamilyName($community['contact_family_name']);
        $communityInputType->setContactGivenName($community['contact_name']);

        return $communityInputType;
    }

    /**
     * @return int[]
     */
    private function getProjectsIds(string $iso3): array
    {
        $projects = $this->projectRepository->findBy(['countryIso3' => $iso3], ['id' => 'asc']);

        return array_map(fn(Project $project) => $project->getId(), $projects);
    }

    private function getUniqueNumber()
    {
        $temp = (float)microtime() * 10;
        $number = str_replace('.', '', strval($temp));
        return $number;
    }
}
