<?php


namespace NewApiBundle\Utils;

use NewApiBundle\Entity\Address;
use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Camp;
use NewApiBundle\Entity\CampAddress;
use NewApiBundle\Entity\CountrySpecific;
use NewApiBundle\Entity\CountrySpecificAnswer;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdLocation;
use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Person;
use NewApiBundle\Entity\Phone;
use NewApiBundle\Entity\Profile;
use NewApiBundle\Form\HouseholdConstraints;
use NewApiBundle\Repository\BeneficiaryRepository;
use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\Beneficiary\Address\CampAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\TemporarySettlementAddressInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\CountrySpecificsAnswerInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\Helper\EnumsBuilder;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

/**
 * Class HouseholdService
 * @package NewApiBundle\Utils
 */
class HouseholdService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var LocationService $locationService */
    private $locationService;

    /**
     * HouseholdService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param BeneficiaryService     $beneficiaryService
     * @param RequestValidator       $requestValidator
     * @param LocationService        $locationService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        BeneficiaryService $beneficiaryService,
        RequestValidator $requestValidator,
        LocationService $locationService
    )
    {
        $this->em = $entityManager;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
        $this->locationService = $locationService;
    }

    /**
     * @param HouseholdCreateInputType $inputType
     * @param string                   $countryCode
     *
     * @return Household
     * @throws Exception
     */
    public function create(HouseholdCreateInputType $inputType, string $countryCode): Household
    {
        $headCount = $inputType->getBeneficiaryHeadCount();
        if ($headCount < 1) {
            throw new \InvalidArgumentException('Household has less than one Head');
        }
        if ($headCount > 1) {
            throw new \InvalidArgumentException('Household has more than one Head');
        }

        $household = new Household();
        $this->fillHousehold($inputType, $household, $countryCode);

        foreach ($inputType->getBeneficiaries() as $beneficiaryInputType) {
            $beneficiary = $this->beneficiaryService->create($beneficiaryInputType);
            $beneficiary->setHousehold($household);
            $household->addBeneficiary($beneficiary);
            $this->em->persist($beneficiary);
        }

        $this->em->persist($household);
        return $household;
    }

    private function createResidenceAddress(ResidenceAddressInputType $inputType, string $countryCode): HouseholdLocation
    {
        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT);
        $householdLocation->setType(HouseholdLocation::LOCATION_TYPE_RESIDENCE);

        $location = $this->locationService->getLocationByIdAndCountryCode($inputType->getLocationId(), $countryCode);
        $householdLocation->setAddress(Address::create(
            $inputType->getStreet(),
            $inputType->getNumber(),
            $inputType->getPostcode(),
            $location
        ));

        return $householdLocation;
    }

    private function createTemporarySettlementAddress(TemporarySettlementAddressInputType $inputType, string $countryCode): HouseholdLocation
    {
        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT);
        $householdLocation->setType(HouseholdLocation::LOCATION_TYPE_SETTLEMENT);

        $location = $this->locationService->getLocationByIdAndCountryCode($inputType->getLocationId(), $countryCode);
        $householdLocation->setAddress(Address::create(
            $inputType->getStreet(),
            $inputType->getNumber(),
            $inputType->getPostcode(),
            $location
        ));

        return $householdLocation;
    }

    private function createCampAddress(CampAddressInputType $inputType, string $countryCode): HouseholdLocation
    {
        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT);
        $householdLocation->setType(HouseholdLocation::LOCATION_TYPE_CAMP);

        // Try to find the camp with the name in the request
        if ($inputType->getCampId()) {
            $camp = $this->em->getRepository(Camp::class)->find($inputType->getCampId());
        } else {
            $camp = $this->em->getRepository(Camp::class)
                ->findOneBy(['name' => $inputType->getCamp()->getName(), 'location' => $inputType->getCamp()->getLocationId()]);
        }

        // Or create a camp with the name in the request
        if (!$camp) {
            $location = $this->locationService->getLocationByIdAndCountryCode($inputType->getCamp()->getLocationId(), $countryCode);
            $camp = new Camp();
            $camp->setName($inputType->getCamp()->getName());
            $camp->setLocation($location);
        }
        $campAddress = new CampAddress();
        $campAddress->setTentNumber($inputType->getTentNumber())
            ->setCamp($camp);
        $householdLocation->setCampAddress($campAddress);
        return $householdLocation;
    }

    /**
     * @param Household                $household
     * @param HouseholdUpdateInputType $inputType
     * @param string                   $countryCode
     *
     * @return Household
     * @throws EntityNotFoundException
     * @throws \NewApiBundle\Enum\EnumValueNoFoundException
     */
    public function update(Household $household, HouseholdUpdateInputType $inputType, string $countryCode): Household
    {
        foreach ($household->getHouseholdLocations() as $initialHouseholdLocation) {
            $this->em->remove($initialHouseholdLocation);
        }
        $household->getHouseholdLocations()->clear();
        $household->getProjects()->clear();

        $this->fillHousehold($inputType, $household, $countryCode);

        $currentIds = [];
        foreach ($household->getBeneficiaries() as $beneficiary) {
            $currentIds[$beneficiary->getId()] = $beneficiary;
        }
        if ($household->getHouseholdHead()) {
            $head = $this->beneficiaryService->update($household->getHouseholdHead(), $inputType->getHouseholdHead());
            unset($currentIds[$head->getId()]);
        }
        foreach ($inputType->getBeneficiaries() as $beneficiaryInputType) {
            if ($beneficiaryInputType->isHead()) continue;

            $existingBeneficiary = $this->tryToPairBeneficiaryInHousehold($household, $beneficiaryInputType);

            if (is_null($existingBeneficiary)) {
                $beneficiary = $this->beneficiaryService->create($beneficiaryInputType);
                $beneficiary->setHousehold($household);
                $household->addBeneficiary($beneficiary);
            } else {
                $beneficiary = $this->beneficiaryService->update($existingBeneficiary, $beneficiaryInputType);
                unset($currentIds[$beneficiary->getId()]);
            }
        }
        foreach ($currentIds as $beneficiaryId => $beneficiary) {
            $this->beneficiaryService->remove($beneficiary);
        }

        $this->em->persist($household);
        return $household;
    }


    /**
     * @param Household            $household
     * @param BeneficiaryInputType $beneficiaryInputType
     *
     * @return Beneficiary|null
     * @throws Exception
     */
    private function tryToPairBeneficiaryInHousehold(Household $household, BeneficiaryInputType $beneficiaryInputType): ?Beneficiary
    {
        if (!is_null($beneficiaryInputType->getId())) {
            /** @var Beneficiary|null $beneficiary */
            $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy([
                'id' => $beneficiaryInputType->getId(),
                'household' => $household,
            ]);

            return $beneficiary;
        }

        /** @var BeneficiaryRepository $beneficiaryRepository */
        $beneficiaryRepository = $this->em->getRepository(Beneficiary::class);

        $existingBeneficiariesByNationalId = [];
        foreach ($beneficiaryInputType->getNationalIdCards() as $nationalIdCard) {
            $existingBeneficiariesByNationalId[] = $beneficiaryRepository->findIdentity($nationalIdCard->getType(), $nationalIdCard->getNumber(), null, $household);
        }

        if (!empty($existingBeneficiariesByNationalId)) {
            $existingBeneficiariesByNationalId = array_merge(...$existingBeneficiariesByNationalId);
        }

        if (count($existingBeneficiariesByNationalId)>1) throw new Exception("too much duplicities (found ".count($existingBeneficiariesByNationalId).")");

        if (!empty($existingBeneficiariesByNationalId)) {
            return $existingBeneficiariesByNationalId[0];
        }

        return null;
    }

    /**
     * @param array $householdArray
     * @param $projectsArray
     * @param bool $flush
     * @return Household
     * @throws ValidationException
     * @throws Exception
     * @deprecated dont use at all
     */
    public function createOrEdit(array $householdArray, array $projectsArray, $household = null, bool $flush = true)
    {
        if (!empty($projectsArray) && (gettype($projectsArray[0]) === 'string' || gettype($projectsArray[0]) === 'integer')) {
            $projectsArray = $this->em->getRepository(Project::class)->findBy(["id" => $projectsArray]);
        }
        $actualAction = 'update';
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

        /** @var Household $household */
        if (!$household) {
            $actualAction = 'create';
            $household = new Household();
        }

        if ($household->getHouseholdLocations()) {
            foreach ($household->getHouseholdLocations() as $initialHouseholdLocation) {
                $this->em->remove($initialHouseholdLocation);
            }
        }
        $this->em->flush();

        foreach ($householdArray['household_locations'] as $householdLocation) {
            $newHouseholdLocation = new HouseholdLocation();
            $newHouseholdLocation
                ->setLocationGroup($householdLocation['location_group'])
                ->setType($householdLocation['type']);

            if ($householdLocation['type'] === HouseholdLocation::LOCATION_TYPE_CAMP) {
                // Try to find the camp with the name in the request
                $camp = $this->em->getRepository(Camp::class)->findOneBy(['name' => $householdLocation['camp_address']['camp']['name']]);
                // Or create a camp with the name in the request
                if (!$camp instanceof Camp) {
                    $location = $this->locationService->getLocation($householdArray['__country'], $householdLocation['camp_address']['camp']['location']);
                    if (null === $location) {
                        throw new Exception("Location was not found.");
                    }
                    $camp = new Camp();
                    $camp->setName($householdLocation['camp_address']['camp']['name']);
                    $camp->setLocation($location);
                }
                $campAddress = new CampAddress();
                $campAddress->setTentNumber($householdLocation['camp_address']['tent_number'])
                    ->setCamp($camp);
                $newHouseholdLocation->setCampAddress($campAddress);
            } else {
                $location = $this->locationService->getLocation($householdArray['__country'], $householdLocation['address']["location"]);
                if (null === $location) {
                    throw new Exception("Location was not found.");
                }
                $newHouseholdLocation->setAddress(Address::create(
                    $householdLocation['address']['street'] ?? null,
                    $householdLocation['address']['number'] ?? null,
                    $householdLocation['address']['postcode'] ?? null,
                    $location
                    ));
            }
            $household->addHouseholdLocation($newHouseholdLocation);
            $this->em->persist($newHouseholdLocation);
        }

        $shelter = isset($householdArray["shelter_status"]) ? HouseholdShelterStatus::valueFromAPI($householdArray["shelter_status"]) : null;

        $enumBuilder = new EnumsBuilder(HouseholdAssets::class);
        $assets = $enumBuilder->buildInputValues($householdArray["assets"] ?? []);

        $household->setNotes($householdArray["notes"])
            ->setLivelihood($householdArray["livelihood"])
            ->setLongitude($householdArray["longitude"])
            ->setLatitude($householdArray["latitude"])
            ->setIncome($householdArray["income"] ?? null)
            ->setCopingStrategiesIndex($householdArray["coping_strategies_index"])
            ->setFoodConsumptionScore($householdArray["food_consumption_score"])
            ->setAssets($assets)
            ->setShelterStatus($shelter)
            ->setDebtLevel($householdArray["debt_level"] ?? null)
            ->setSupportReceivedTypes($householdArray["support_received_types"] ?? [])
            ->setSupportOrganizationName($householdArray["support_organization_name"] ?? null)
            ->setIncomeSpentOnFood($householdArray["income_spent_on_food"] ?? null)
            ->setHouseholdIncome($householdArray["household_income"] ?? null)
            ->setEnumeratorName($householdArray["enumerator_name"] ?? null);

        $dateReceived = null;
        if (isset($householdArray["support_date_received"]) && $householdArray["support_date_received"]) {
            if (is_string($householdArray['support_date_received'])) {
                $dateReceived = \DateTime::createFromFormat('d-m-Y', $householdArray['support_date_received']);
            } else {
                $dateReceived = $householdArray['support_date_received'];
            }

            if (!$dateReceived instanceof \DateTimeInterface) {
                throw new Exception("Value of support_date_received is invalid");
            }
        }
        $household->setSupportDateReceived($dateReceived);

        // Remove projects if the household is not part of them anymore
        if ($actualAction === "update") {
            $oldProjects = $household->getProjects()->toArray();
            $toRemove = array_udiff(
                $oldProjects,
                $projectsArray,
                function ($oldProject, $newProject) {
                    if ($oldProject->getId() === $newProject->getId()) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            foreach ($toRemove as $projectToRemove) {
                $household->removeProject($projectToRemove);
            }
        }

        // Add projects
        foreach ($projectsArray as $project) {
            if (!$project instanceof Project) {
                throw new Exception("The project could not be found.");
            }
            if ($actualAction !== 'update' || !$household->getProjects()->contains($project)) {
                $household->addProject($project);
            }
        }

        $this->em->persist($household);

        if (!empty($householdArray["beneficiaries"])) {
            $hasHead = false;
            $beneficiariesPersisted = [];
            if ($actualAction === "update") {
                $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findBy(["household" => $household]);
            }
            foreach ($householdArray["beneficiaries"] as $beneficiaryToSave) {
                try {
                    if (!is_numeric($beneficiaryToSave['gender'])) {
                        $beneficiaryToSave['gender'] = PersonGender::valueToAPI(PersonGender::valueFromAPI($beneficiaryToSave['gender']));
                    }

                    $beneficiary = $this->beneficiaryService->updateOrCreate($household, $beneficiaryToSave, false);
                    if (!array_key_exists("id", $beneficiaryToSave)) {
                        $household->addBeneficiary($beneficiary);
                    }
                    $beneficiariesPersisted[] = $beneficiary;
                } catch (Exception $exception) {
                    throw $exception;
                }
                if ($beneficiary->isHead()) {
                    if ($hasHead) {
                        throw new Exception("You have defined more than 1 head of household.");
                    }
                    $hasHead = true;
                }
                $this->em->persist($beneficiary);
            }

            // Remove beneficiaries that are not in the household anymore
            if ($actualAction === 'update') {
                $toRemove = array_udiff(
                    $oldBeneficiaries,
                    $beneficiariesPersisted,
                    function ($oldB, $newB) {
                        if ($oldB->getId() === $newB->getId()) {
                            return 0;
                        } else {
                            return -1;
                        }
                    }
                );
                foreach ($toRemove as $beneficiaryToRemove) {
                    $household->removeBeneficiary($beneficiaryToRemove);
                    $this->beneficiaryService->remove($beneficiaryToRemove);
                }
            }
        }

        if (!empty($householdArray["country_specific_answers"])) {
            foreach ($householdArray["country_specific_answers"] as $country_specific_answer) {
                $this->addOrUpdateCountrySpecific($household, $country_specific_answer, false);
            }
        }

        $proxy = $household->getProxy();

        if (array_key_exists('proxy', $householdArray) && (null !== $householdArray['proxy']['localGivenName'] || null !== $householdArray['proxy']['localFamilyName']) ) {
            if (null === $proxy) {
                $proxy = new Person();
                $this->em->persist($proxy);
                $household->setProxy($proxy);
            }

            $proxyArray = $householdArray['proxy'];

            $proxy->setEnGivenName($proxyArray['enGivenName']);
            $proxy->setEnFamilyName($proxyArray['enFamilyName']);
            $proxy->setEnParentsName($proxyArray['enParentsName']);
            $proxy->setLocalGivenName($proxyArray['localGivenName']);
            $proxy->setLocalFamilyName($proxyArray['localFamilyName']);
            $proxy->setLocalParentsName($proxyArray['localParentsName']);

            /** @var PhoneInputType $phoneInputType */
            $phoneInputType = $proxyArray['phone'];

            $proxy->getPhones()->clear();

            $phone = new Phone();
            $phone->setType($phoneInputType->getType());
            $phone->setPrefix($phoneInputType->getPrefix());
            $phone->setNumber($phoneInputType->getNumber());
            $phone->setProxy($phoneInputType->getProxy());
            $phone->setPerson($proxy);

            $this->em->persist($phone);

            /** @var NationalIdCardInputType $nationalIdInputType */
            $nationalIdInputType = $proxyArray['nationalIdCard'];

            $proxy->getNationalIds()->clear();

            $nationalId = new NationalId();
            $nationalId->setIdType($nationalIdInputType->getType());
            $nationalId->setIdNumber($nationalIdInputType->getNumber());
            $nationalId->setPerson($proxy);
            $proxy->addNationalId($nationalId);

            $this->em->persist($nationalId);

        } else {
            if (null !== $proxy) {
                $this->em->remove($proxy);
            }

            $household->setProxy(null);
        }

        if ($flush) {
            $this->em->flush();
            $household = $this->em->getRepository(Household::class)->find($household->getId());
            $country_specific_answers = $this->em->getRepository(CountrySpecificAnswer::class)->findByHousehold($household);
            foreach ($country_specific_answers as $country_specific_answer) {
                $household->addCountrySpecificAnswer($country_specific_answer);
            }
        }


        return $household;
    }

    /**
     * @param array $householdArray
     * @return array
     */
    public function removeBeneficiaries(array $householdArray)
    {
        $household = $this->em->getRepository(Household::class)->find($householdArray['id']);
        $beneficiaryIds = array_values(array_map(function ($beneficiary) {
            return $beneficiary['id'];
        }, $householdArray['beneficiaries']));

        // Remove beneficiaries that are not in the array
        foreach ($household->getBeneficiaries() as $beneficiary) {
            if (!in_array($beneficiary->getId(), $beneficiaryIds)) {
                $this->em->remove($beneficiary);
            }
        }

        return $householdArray;
    }

    /**
     * @param Household $household
     * @param Project $project
     */
    public function addToProject(Household &$household, Project $project)
    {
        if (!$household->getProjects()->contains($project)) {
            $household->addProject($project);
            $this->em->persist($household);
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    public function createOrUpdateCountrySpecificAnswers(Household $household, CountrySpecificsAnswerInputType $inputType): ?CountrySpecificAnswer
    {
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->find($inputType->getCountrySpecificId());

        if (!$countrySpecific instanceof CountrySpecific) {
            throw new EntityNotFoundException('Country specific with id '.$inputType->getCountrySpecificId().' not found.');
        }

        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)
            ->findOneBy([
                "countrySpecific" => $countrySpecific,
                "household" => $household
            ]);

        if (!is_null($inputType->getAnswer())) {
            if (!$countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $countrySpecificAnswer = new CountrySpecificAnswer();

                $countrySpecificAnswer->setCountrySpecific($countrySpecific)
                    ->setHousehold($household);
            }

            $countrySpecificAnswer->setAnswer($inputType->getAnswer());

            $this->em->persist($countrySpecificAnswer);
        } else {
            if ($countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $this->em->remove($countrySpecificAnswer);
            }
        }

        $this->em->flush();

        return $countrySpecificAnswer;
    }

    /**
     * @param Household $household
     * @param $countrySpecificAnswerArray
     * @return array|CountrySpecificAnswer
     * @throws Exception
     *
     * @deprecated use createOrUpdateCountrySpecificAnswers instead
     */
    public function addOrUpdateCountrySpecific(Household $household, $countrySpecificAnswerArray, bool $flush)
    {
        $this->requestValidator->validate(
            "country_specific_answer",
            HouseholdConstraints::class,
            $countrySpecificAnswerArray,
            'any'
        );
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->find($countrySpecificAnswerArray["country_specific"]["id"]);

        if (!$countrySpecific instanceof CountrySpecific) {
            throw new Exception("This country specific is unknown");
        }

        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)
            ->findOneBy([
                "countrySpecific" => $countrySpecific,
                "household" => $household
            ]);

        if ($countrySpecificAnswerArray["answer"]) {
            if (!$countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $countrySpecificAnswer = new CountrySpecificAnswer();
                $countrySpecificAnswer->setCountrySpecific($countrySpecific)
                    ->setHousehold($household);
            }

            $countrySpecificAnswer->setAnswer($countrySpecificAnswerArray["answer"]);

            $this->em->persist($countrySpecificAnswer);
        } else {
            if ($countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $this->em->remove($countrySpecificAnswer);
            }
        }

        if ($flush) {
            $this->em->flush();
        }

        return $countrySpecificAnswer;
    }

    public function remove(Household $household)
    {
        $household->setArchived(true);
        $this->em->persist($household);
        $this->em->flush();

        return $household;
    }

    /**
     * @param HouseholdUpdateInputType $inputType
     * @param Household                $household
     * @param string                   $countryCode
     *
     * @throws EntityNotFoundException
     * @throws \NewApiBundle\Enum\EnumValueNoFoundException
     */
    private function fillHousehold(HouseholdUpdateInputType $inputType, Household $household, string $countryCode): void
    {
        if ($inputType->getResidenceAddress()) {
            $household->addHouseholdLocation($this->createResidenceAddress($inputType->getResidenceAddress(), $countryCode));
        }

        if ($inputType->getTemporarySettlementAddress()) {
            $household->addHouseholdLocation($this->createTemporarySettlementAddress($inputType->getTemporarySettlementAddress(), $countryCode));
        }

        if ($inputType->getCampAddress()) {
            $household->addHouseholdLocation($this->createCampAddress($inputType->getCampAddress(), $countryCode));
        }

        $household->setNotes($inputType->getNotes())
            ->setLivelihood($inputType->getLivelihood())
            ->setLongitude($inputType->getLongitude())
            ->setLatitude($inputType->getLatitude())
            ->setIncome($inputType->getIncome())
            ->setCopingStrategiesIndex($inputType->getCopingStrategiesIndex())
            ->setFoodConsumptionScore($inputType->getFoodConsumptionScore())
            ->setAssets($inputType->getAssets())
            ->setShelterStatus($inputType->getShelterStatus())
            ->setDebtLevel($inputType->getDebtLevel())
            ->setSupportReceivedTypes($inputType->getSupportReceivedTypes())
            ->setSupportOrganizationName($inputType->getSupportOrganizationName())
            ->setIncomeSpentOnFood($inputType->getIncomeSpentOnFood())
            ->setHouseholdIncome($inputType->getHouseIncome())
            ->setEnumeratorName($inputType->getEnumeratorName())
            ->setSupportDateReceived($inputType->getSupportDateReceived());

        $this->em->persist($household);

        // Add projects
        $projects = $this->em->getRepository(Project::class)->findBy(["id" => $inputType->getProjectIds()]);
        foreach ($projects as $project) {
            $household->addProject($project);
        }

        foreach ($inputType->getCountrySpecificAnswers() as $countrySpecificAnswer) {
            $this->createOrUpdateCountrySpecificAnswers($household, $countrySpecificAnswer);
        }

        if ($inputType->hasProxy()) {
            $proxy = new Person();
            $proxy->setEnGivenName($inputType->getProxyEnGivenName());
            $proxy->setEnFamilyName($inputType->getProxyEnFamilyName());
            $proxy->setEnParentsName($inputType->getProxyEnParentsName());
            $proxy->setLocalGivenName($inputType->getProxyLocalGivenName());
            $proxy->setLocalFamilyName($inputType->getProxyLocalFamilyName());
            $proxy->setLocalParentsName($inputType->getProxyLocalParentsName());
            $proxy->setProfile(new Profile());
            $proxy->getProfile()->setPhoto('');

            /** @var PhoneInputType $phoneInputType */
            $phoneInputType = $inputType->getProxyPhone();

            $proxy->getPhones()->clear();

            $phone = new Phone();
            $phone->setType($phoneInputType->getType());
            $phone->setPrefix($phoneInputType->getPrefix());
            $phone->setNumber($phoneInputType->getNumber());
            $phone->setProxy($phoneInputType->getProxy());
            $phone->setPerson($proxy);

            $this->em->persist($phone);

            /** @var NationalIdCardInputType $nationalIdInputType */
            $nationalIdInputType = $inputType->getProxyNationalIdCard();
            $proxy->getNationalIds()->clear();

            $nationalId = new NationalId();
            $nationalId->setIdType($nationalIdInputType->getType());
            $nationalId->setIdNumber($nationalIdInputType->getNumber());
            $nationalId->setPerson($proxy);
            $proxy->addNationalId($nationalId);

            $this->em->persist($proxy);
            $this->em->persist($nationalId);
            $household->setProxy($proxy);
        }
    }
}
