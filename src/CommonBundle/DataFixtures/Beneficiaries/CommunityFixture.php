<?php
namespace CommonBundle\DataFixtures\Beneficiaries;

use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\InputType\LocationType;
use BeneficiaryBundle\InputType\NewInstitutionType;
use BeneficiaryBundle\InputType\UpdateCommunityType;
use BeneficiaryBundle\Utils\CommunityService;
use BeneficiaryBundle\Utils\InstitutionService;
use CommonBundle\DataFixtures\LocationFixtures;
use CommonBundle\InputType\Country;
use CommonBundle\InputType\RequestConverter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;

class CommunityFixture extends Fixture implements DependentFixtureInterface
{
    const COUNTRIES = ['KHM', 'SYR', 'UKR'];
    const COMMUNITIES = [
        [
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
                'id_type' => NationalId::TYPE_NATIONAL_ID,
                'id_number' => 'ID: 000-1234-5895-21',
            ],
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
                'location' => [
                    'adm1' => 1,
                    'adm2' => 1,
                    'adm3' => 1,
                    'adm4' => 1,
                    'country_iso3' => 'KHM',
                ],
            ],
            'national_id' => [
                'id_type' => NationalId::TYPE_FAMILY,
                'id_number' => 'FML: 000-1234-5895-21',
            ],
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
                'location' => [
                    'adm1' => 1,
                    'adm2' => 1,
                    'adm3' => 1,
                    'adm4' => 1,
                    'country_iso3' => 'KHM',
                ],
            ],
            'national_id' => [
                'id_type' => NationalId::TYPE_CAMP_ID,
                'id_number' => 'CMP: 000-1234-5895-21',
            ],
            'phone_prefix' => '+4234',
            'phone_number' => '123 456 789',
            'contact_name' => 'Schoolman',
            'contact_family_name' => 'Camper',
        ],
    ];

    /** @var string */
    private $environment;
    /** @var CommunityService */
    private $communityService;

    /**
     * CommunityFixture constructor.
     * @param string $environment
     * @param CommunityService $communityService
     */
    public function __construct(string $environment, CommunityService $communityService)
    {
        $this->environment = $environment;
        $this->communityService = $communityService;
    }


    public function load(ObjectManager $manager)
    {
        if ($this->environment == "prod") {
            echo "Cannot run on production environment";
            return;
        }
        foreach (self::COMMUNITIES as $communityArray) {
            /** @var UpdateCommunityType $communityType */
            $communityType = RequestConverter::normalizeInputType($communityArray, UpdateCommunityType::class);
            foreach (self::COUNTRIES as $COUNTRY) {
                $institution = $this->communityService->create(new Country($COUNTRY), $communityType);
                $manager->persist($institution);

                $manager->flush();
            }
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LocationFixtures::class,
        ];
    }
}
