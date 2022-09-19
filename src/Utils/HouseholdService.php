<?php

namespace Utils;

use Entity\Address;
use Entity\Beneficiary;
use Entity\Camp;
use Entity\CampAddress;
use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Entity\Household;
use Entity\HouseholdLocation;
use Entity\NationalId;
use Entity\Person;
use Entity\Phone;
use Entity\Profile;
use Enum\EnumValueNoFoundException;
use InvalidArgumentException;
use Repository\BeneficiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use InputType\Beneficiary\Address\CampAddressInputType;
use InputType\Beneficiary\Address\ResidenceAddressInputType;
use InputType\Beneficiary\Address\TemporarySettlementAddressInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\CountrySpecificsAnswerInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\HouseholdCreateInputType;
use InputType\HouseholdUpdateInputType;
use Entity\Project;
use Repository\LocationRepository;

/**
 * Class HouseholdService
 *
 * @package Utils
 */
class HouseholdService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * HouseholdService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param BeneficiaryService $beneficiaryService
     * @param LocationRepository $locationRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        BeneficiaryService $beneficiaryService,
        LocationRepository $locationRepository
    ) {
        $this->em = $entityManager;
        $this->beneficiaryService = $beneficiaryService;
        $this->locationRepository = $locationRepository;
    }

    /**
     * @param HouseholdCreateInputType $inputType
     * @param string $countryCode
     *
     * @return Household
     * @throws Exception
     */
    public function create(HouseholdCreateInputType $inputType, string $countryCode): Household
    {
        $headCount = $inputType->getBeneficiaryHeadCount();
        if ($headCount < 1) {
            throw new InvalidArgumentException('Household has less than one Head');
        }
        if ($headCount > 1) {
            throw new InvalidArgumentException('Household has more than one Head');
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

    /**
     * @param ResidenceAddressInputType $inputType
     * @param string $countryCode
     *
     * @return HouseholdLocation
     * @throws EntityNotFoundException
     */
    private function createResidenceAddress(
        ResidenceAddressInputType $inputType,
        string $countryCode
    ): HouseholdLocation {
        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT);
        $householdLocation->setType(HouseholdLocation::LOCATION_TYPE_RESIDENCE);

        $location = $this->locationRepository->getLocationByIdAndCountryCode($inputType->getLocationId(), $countryCode);
        $householdLocation->setAddress(
            Address::create(
                $inputType->getStreet(),
                $inputType->getNumber(),
                $inputType->getPostcode(),
                $location
            )
        );

        return $householdLocation;
    }

    /**
     * @param TemporarySettlementAddressInputType $inputType
     * @param string $countryCode
     *
     * @return HouseholdLocation
     * @throws EntityNotFoundException
     */
    private function createTemporarySettlementAddress(
        TemporarySettlementAddressInputType $inputType,
        string $countryCode
    ): HouseholdLocation {
        $householdLocation = new HouseholdLocation();
        $householdLocation->setLocationGroup(HouseholdLocation::LOCATION_GROUP_CURRENT);
        $householdLocation->setType(HouseholdLocation::LOCATION_TYPE_SETTLEMENT);

        $location = $this->locationRepository->getLocationByIdAndCountryCode($inputType->getLocationId(), $countryCode);
        $householdLocation->setAddress(
            Address::create(
                $inputType->getStreet(),
                $inputType->getNumber(),
                $inputType->getPostcode(),
                $location
            )
        );

        return $householdLocation;
    }

    /**
     * @param CampAddressInputType $inputType
     * @param string $countryCode
     *
     * @return HouseholdLocation
     * @throws EntityNotFoundException
     */
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
                ->findOneBy(
                    ['name' => $inputType->getCamp()->getName(), 'location' => $inputType->getCamp()->getLocationId()]
                );
        }

        // Or create a camp with the name in the request
        if (!$camp) {
            $location = $this->locationRepository->getLocationByIdAndCountryCode(
                $inputType->getCamp()->getLocationId(),
                $countryCode
            );
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
     * @param Household $household
     * @param HouseholdUpdateInputType $inputType
     * @param string $countryCode
     *
     * @return Household
     * @throws EntityNotFoundException
     * @throws EnumValueNoFoundException
     * @throws Exception
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
            if ($beneficiaryInputType->isHead()) {
                continue;
            }

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
     * @param Household $household
     * @param BeneficiaryInputType $beneficiaryInputType
     *
     * @return Beneficiary|null
     * @throws Exception
     */
    private function tryToPairBeneficiaryInHousehold(
        Household $household,
        BeneficiaryInputType $beneficiaryInputType
    ): ?Beneficiary {
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
            $existingBeneficiariesByNationalId[] = $beneficiaryRepository->findIdentity(
                $nationalIdCard->getType(),
                $nationalIdCard->getNumber(),
                null,
                $household
            );
        }

        if (!empty($existingBeneficiariesByNationalId)) {
            $existingBeneficiariesByNationalId = array_merge(...$existingBeneficiariesByNationalId);
        }

        if (count($existingBeneficiariesByNationalId) > 1) {
            throw new Exception("too much duplicities (found " . count($existingBeneficiariesByNationalId) . ")");
        }

        if (!empty($existingBeneficiariesByNationalId)) {
            return $existingBeneficiariesByNationalId[0];
        }

        return null;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function createOrUpdateCountrySpecificAnswers(
        Household $household,
        CountrySpecificsAnswerInputType $inputType
    ): ?CountrySpecificAnswer {
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->find($inputType->getCountrySpecificId());

        if (!$countrySpecific instanceof CountrySpecific) {
            throw new EntityNotFoundException(
                'Country specific with id ' . $inputType->getCountrySpecificId() . ' not found.'
            );
        }

        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)
            ->findOneBy([
                "countrySpecific" => $countrySpecific,
                "household" => $household,
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

    public function remove(Household $household)
    {
        $household->setArchived(true);
        $this->em->persist($household);
        $this->em->flush();
    }

    /**
     * @param HouseholdUpdateInputType $inputType
     * @param Household $household
     * @param string $countryCode
     *
     * @throws EntityNotFoundException
     * @throws EnumValueNoFoundException
     */
    private function fillHousehold(HouseholdUpdateInputType $inputType, Household $household, string $countryCode): void
    {
        if ($inputType->getResidenceAddress()) {
            $household->addHouseholdLocation(
                $this->createResidenceAddress($inputType->getResidenceAddress(), $countryCode)
            );
        }

        if ($inputType->getTemporarySettlementAddress()) {
            $household->addHouseholdLocation(
                $this->createTemporarySettlementAddress($inputType->getTemporarySettlementAddress(), $countryCode)
            );
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
            ->setSupportDateReceived($inputType->getSupportDateReceived())
            ->setCountryIso3($countryCode);

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

            $nationalId = NationalId::fromNationalIdInputType($nationalIdInputType);
            $nationalId->setPerson($proxy);
            $proxy->addNationalId($nationalId);

            $this->em->persist($proxy);
            $this->em->persist($nationalId);
            $household->setProxy($proxy);
        }
    }
}
