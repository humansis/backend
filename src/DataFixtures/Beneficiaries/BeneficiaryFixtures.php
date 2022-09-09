<?php


namespace DataFixtures\Beneficiaries;

use Utils\HouseholdService;
use DataFixtures\ProjectFixtures;
use DataFixtures\VulnerabilityCriterionFixtures;
use BeneficiaryBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Utils\HouseholdService;
use CommonBundle\DataFixtures\LocationFixtures;
use CommonBundle\DataFixtures\ProjectFixtures;
use CommonBundle\DataFixtures\VulnerabilityCriterionFixtures;
use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\Enum\VulnerabilityCriteria;
use NewApiBundle\InputType\Beneficiary\Address\CampAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\CampInputType;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\TemporarySettlementAddressInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\Utils\ValueGenerator\ValueGenerator;
use Entity\Project;
use Enum\Livelihood;
use ProjectBundle\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Kernel;

class BeneficiaryFixtures extends Fixture implements DependentFixtureInterface
{
    private const HOUSEHOLDS_PER_COUNTRY = 5;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var Countries
     */
    private $countries;

    /**
     * @var HouseholdService
     */
    private $householdService;

    private $kernel;

    public function __construct(
        Kernel             $kernel,
        HouseholdService   $householdService,
        ProjectRepository  $projectRepository,
        LocationRepository $locationRepository,
        Countries          $countries
    ) {
        $this->householdService = $householdService;
        $this->kernel = $kernel;
        $this->projectRepository = $projectRepository;
        $this->locationRepository = $locationRepository;
        $this->countries = $countries;
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

        foreach ($this->countries->getAll() as $country) {

            $i = 1;
            while ($i <= self::HOUSEHOLDS_PER_COUNTRY) {
                $householdInputType = $this->generateHouseholdInputType($country->getIso3());
                $this->householdService->create($householdInputType, $country->getIso3());
                $i++;
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixtures::class,
            VulnerabilityCriterionFixtures::class,
            ProjectFixtures::class,
        ];
    }

    private function generateHouseholdInputType(string $iso3): HouseholdCreateInputType
    {
        /**
         * @var Project[] $possibleProjects
         */
        $possibleProjects = $this->projectRepository->findByCountries([$iso3]);
        $projects = [];
        foreach ($possibleProjects as $possibleProject) {
            $projects[] = $possibleProject->getId();
        }

        $inputType = new HouseholdCreateInputType();
        $inputType->setLivelihood(ValueGenerator::fromEnum(Livelihood::class));
        $inputType->setShelterStatus(ValueGenerator::fromEnum(HouseholdShelterStatus::class));
        $inputType->setProjectIds(array_unique([ValueGenerator::fromArray($projects), ValueGenerator::fromArray($projects), ValueGenerator::fromArray($projects)]));
        $inputType->setNotes(ValueGenerator::fromArray([null, 'Fixture note '.ValueGenerator::int(1, 1000), 'Fixture note '.ValueGenerator::int(1,
                1000)]));
        $inputType->setLongitude(null);
        $inputType->setLatitude(null);
        $countOfBeneficiaries = ValueGenerator::int(1, 8);
        for ($x = 0; $x <= $countOfBeneficiaries; $x++) {
            $inputType->addBeneficiary($this->generateBeneficiaryInputType($x));
        }
        $inputType->setIncome(ValueGenerator::int(1, 10));
        $inputType->setFoodConsumptionScore(ValueGenerator::int(1, 10));
        $inputType->setCopingStrategiesIndex(ValueGenerator::int(0, 5));
        $inputType->setDebtLevel(ValueGenerator::int(0, 5));
        $inputType->setIncomeSpentOnFood(ValueGenerator::int(0, 5));
        $inputType->setHouseIncome(ValueGenerator::int(0, 5));

        $addressRandom = ValueGenerator::int(0, 2);
        switch ($addressRandom) {
            case 0:
                $inputType->setResidenceAddress($this->generateResidencyAddress($iso3));
                break;
            case 1:
                $inputType->setTemporarySettlementAddress($this->generateTemporarySettlement($iso3));
                break;
            case 2:
                $inputType->setCampAddress($this->generateCampAddress($iso3));
                break;
        }

        return $inputType;
    }

    private function generateBeneficiaryInputType(int $i): BeneficiaryInputType
    {
        $bnfInputType = new BeneficiaryInputType();
        $bnfInputType->setDateOfBirth(ValueGenerator::date(0, 70)->format('Y-m-d'));
        $bnfInputType->setLocalFamilyName('Local Family '.$i);
        $bnfInputType->setLocalGivenName('Local Given '.$i);
        $bnfInputType->setLocalFamilyName('Local Family '.$i);
        $bnfInputType->setLocalGivenName('Local Given '.$i);
        $bnfInputType->setLocalParentsName('Local Parents '.$i);
        $bnfInputType->setEnFamilyName('EN Family '.$i);
        $bnfInputType->setEnGivenName('EN Given '.$i);
        $bnfInputType->setEnParentsName('EN Parents '.$i);
        $bnfInputType->setGender(ValueGenerator::fromEnum(PersonGender::class));
        $bnfInputType->addNationalIdCard($this->generateNationalIdCard());
        $bnfInputType->addPhone($this->generatePhone());
        $bnfInputType->setResidencyStatus(ValueGenerator::fromEnum(ResidencyStatus::class));
        $bnfInputType->setIsHead($i === 1);
        if (ValueGenerator::bool()) {
            $bnfInputType->addVulnerabilityCriteria(ValueGenerator::fromEnum(VulnerabilityCriteria::class));
        }

        return $bnfInputType;
    }

    private function generateNationalIdCard(): NationalIdCardInputType
    {
        $nationalInputType = new NationalIdCardInputType();
        $nationalInputType->setNumber(ValueGenerator::string(10));
        $nationalInputType->setType(ValueGenerator::fromEnum(NationalIdType::class));

        return $nationalInputType;
    }

    private function generatePhone(): PhoneInputType
    {
        $phoneInputType = new PhoneInputType();
        $phoneInputType->setType(ValueGenerator::fromEnum(PhoneTypes::class));
        $phoneInputType->setNumber(ValueGenerator::int(100000000, 999999999));
        $phoneInputType->setPrefix((string) ValueGenerator::int(400, 500));

        return $phoneInputType;
    }

    private function generateResidencyAddress(string $iso3): ResidenceAddressInputType
    {
        $residencyInputType = new ResidenceAddressInputType();
        $residencyInputType->setLocationId($this->getLocation($iso3)->getId());
        $residencyInputType->setNumber((string) ValueGenerator::int(1, 1000));
        $residencyInputType->setPostcode((string) ValueGenerator::int(1000, 3000));
        $residencyInputType->setStreet('Street Residency '.ValueGenerator::int(1, 100));

        return $residencyInputType;
    }

    private function generateTemporarySettlement(string $iso3): TemporarySettlementAddressInputType
    {
        $settlementInputType = new TemporarySettlementAddressInputType();
        $settlementInputType->setLocationId($this->getLocation($iso3)->getId());
        $settlementInputType->setNumber((string) ValueGenerator::int(1, 1000));
        $settlementInputType->setPostcode((string) ValueGenerator::int(1000, 3000));
        $settlementInputType->setStreet('Street Temporary '.ValueGenerator::int(1, 100));

        return $settlementInputType;
    }

    private function generateCampAddress(string $iso3): CampAddressInputType
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
