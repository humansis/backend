<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Utils\ImportProvider\DefaultApiProvider;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Utils\LocationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ApiImportService
 * @package BeneficiaryBundle\Utils
 */
class ApiImportService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var DefaultApiProvider $apiProvider */
    private $apiProvider;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var LocationService $locationService */
    private $locationService;

    /**
     * HouseholdService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        ValidatorInterface $validator
    )
    {
        $this->em = $entityManager;
        $this->container= $container;
        $this->validator = $validator;
        $this->locationService = $this->container->get('location_service');
    }


    /**
     * Get beneficiaries from the API in the current country
     * @param  string $countryISO3
     * @param int $countryCode
     * @param bool $flush
     * @return array
     * @throws \Exception
     */
    public function getBeneficiaries(string $countryISO3, int $countryCode, bool $flush)
    {
        try {
            $this->apiProvider = $this->getApiProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        try {
            $allBeneficiaries = $this->apiProvider->getBeneficiaries($countryCode);
            $oldEquityNumber = "";
            $beneficiariesInHousehold = array();

            foreach ($allBeneficiaries as $allBeneficiary){
                if($oldEquityNumber != $allBeneficiary['equityCardNo'] && $oldEquityNumber != ""){

                    $household = $this->createAndInitHousehold($beneficiariesInHousehold);

                    if($household != 'beneficiariesExist'){
                        $location = $this->getLocation($this->locationService, $countryCode, $countryISO3);
                        $household->setLocation($location);

                        $this->em->persist($household);

                        $this->insertBeneficiaries($household, $beneficiariesInHousehold);

                        $countrySpecificAnswer = $this->setCountrySpecificAnswer($countryISO3, $household, $beneficiariesInHousehold);

                        $this->em->persist($countrySpecificAnswer);

                        if($flush)
                            $this->em->flush();

                        $this->setHousehold($household);

                        unset($beneficiariesInHousehold);
                        $beneficiariesInHousehold = array();
                    }
                }

                $oldEquityNumber = $allBeneficiary['equityCardNo'];
                array_push($beneficiariesInHousehold, $allBeneficiary);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        return ['message' => 'Insertion successfull'];
    }


    /**
     * Get the API provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @return DefaultApiProvider|object
     * @throws \Exception
     */
    private function getApiProviderForCountry(string $countryISO3)
    {
        $provider = $this->container->get('beneficiary.' . strtolower($countryISO3) . '_api_provider');

        if (! ($provider instanceof DefaultApiProvider)) {
            throw new \Exception("The API provider for " . $countryISO3 . "is not properly defined");
        }
        return $provider;
    }

    /**
     * @param array $beneficiariesInHousehold
     * @return Household|string
     * @throws \Exception
     */
    private function createAndInitHousehold(array $beneficiariesInHousehold){

        $dateOfBirth = new DateTime($beneficiariesInHousehold[0]['dateOfBirth']);
        $familyName = $beneficiariesInHousehold[0]['familyName'];
        $givenName = $beneficiariesInHousehold[0]["givenName"];
        $status = $beneficiariesInHousehold[0]['headHousehold'];
        $sex = $beneficiariesInHousehold[0]['sex'];

        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $givenName, 'familyName' => $familyName, 'gender' => $sex, 'status' => $status, 'dateOfBirth' => $dateOfBirth]);

        if($beneficiary)
            return "beneficiariesExist";

        /** @var Household $household */
        $household = new Household();
        $household->setNotes(null)
            ->setLivelihood(null)
            ->setLongitude(null)
            ->setLatitude(null)
            ->setAddressStreet(null)
            ->setAddressPostcode(null)
            ->setAddressNumber(null);

        $errors = $this->validator->validate($household);
        if (count($errors) > 0)
        {
            $errorsMessage = "";
            /** @var ConstraintViolation $error */
            foreach ($errors as $error)
            {
                if ("" !== $errorsMessage)
                    $errorsMessage .= " ";
                $errorsMessage .= $error->getMessage();
            }

            throw new \Exception($errorsMessage);
        }

        return $household;
    }

    /**
     * @param LocationService $locationService
     * @param int $countryCode
     * @param string $countryISO3
     * @return \CommonBundle\Entity\Location|null|object
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    private function getLocation(LocationService $locationService, int $countryCode, string $countryISO3){
        $countryISO3FirstLetters = "";

        for ($i = 0; $i < 2; $i++){
            $countryISO3FirstLetters = $countryISO3FirstLetters . $countryISO3[$i];
        }

        $adm3 = $this->em->getRepository(Adm3::class)->findOneBy(['code' => $countryISO3FirstLetters . $countryCode]);
        if($adm3 == null){
            $countryISO3FirstLetters = $countryISO3FirstLetters . "0";
            $adm3 = $this->em->getRepository(Adm3::class)->findOneBy(['code' => $countryISO3FirstLetters . $countryCode]);

            if($adm3 == null)
                throw new \Exception("Adm3 was not found.");
        }
        $adm2 = $this->em->getRepository(Adm2::class)->find($adm3->getAdm2());
        $adm1 = $this->em->getRepository(Adm1::class)->find($adm2->getAdm1());

        $householdArray = array(
            'location' => array(
                'adm1' => $adm1->getName(),
                'adm2' => $adm2->getName(),
                'adm3' => $adm3->getName()
            )
        );

        $location = $locationService->getOrSaveLocation($countryISO3, $householdArray["location"]);

        if (null === $location)
            throw new \Exception("Location was not found.");

        return $location;
    }

    /**
     * @param Household $household
     * @param array $beneficiariesInHousehold
     * @throws \Exception
     */
    private function insertBeneficiaries(Household $household, array $beneficiariesInHousehold){

        if (!empty($beneficiariesInHousehold))
        {
            $hasHead = false;
            $beneficiariesPersisted = [];
            foreach ($beneficiariesInHousehold as $beneficiaryToSave)
            {
                try
                {
                    $beneficiary = $this->createBeneficiary($household, $beneficiaryToSave);
                    $beneficiariesPersisted[] = $beneficiary;
                }
                catch (\Exception $exception)
                {
                    throw new \Exception($exception->getMessage());
                }
                if ($beneficiary->getStatus())
                {
                    if ($hasHead)
                    {
                        throw new \Exception("You have defined more than 1 head of household.");
                    }
                    $hasHead = true;
                }
                $this->em->persist($beneficiary);
            }
        }
    }

    /**
     * @param string $countryISO3
     * @param Household $household
     * @param array $beneficiariesInHousehold
     * @return CountrySpecificAnswer
     */
    private function setCountrySpecificAnswer(string $countryISO3, Household $household, array $beneficiariesInHousehold){
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->findOneBy(['fieldString' => 'ID Poor', 'countryIso3' => $countryISO3]);

        $countrySpecificAnswer = new CountrySpecificAnswer();
        $countrySpecificAnswer->setCountrySpecific($countrySpecific)
            ->setHousehold($household)
            ->setAnswer($beneficiariesInHousehold[0]['IDPoor']);

        return $countrySpecificAnswer;
    }

    /**
     * @param Household $household
     */
    private function setHousehold(Household $household){
        $household = $this->em->getRepository(Household::class)->find($household->getId());
        $country_specific_answer = $this->em->getRepository(CountrySpecificAnswer::class)->findByHousehold($household);
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);

        $household->addCountrySpecificAnswer($country_specific_answer[0]);

        foreach ($beneficiaries as $beneficiary)
        {
            $phones = $this->em->getRepository(Phone::class)
                ->findByBeneficiary($beneficiary);
            $nationalIds = $this->em->getRepository(NationalId::class)
                ->findByBeneficiary($beneficiary);
            foreach ($phones as $phone)
            {
                $beneficiary->addPhone($phone);
            }
            foreach ($nationalIds as $nationalId)
            {
                $beneficiary->addNationalId($nationalId);
            }
            $household->addBeneficiary($beneficiary);
        }
    }

    private function createBeneficiary(Household $household, array $beneficiaryArray){

        $beneficiary = new Beneficiary();
        $beneficiary->setHousehold($household);

        $beneficiary->setGender($beneficiaryArray["sex"])
            ->setDateOfBirth(new \DateTime($beneficiaryArray["dateOfBirth"]))
            ->setFamilyName($beneficiaryArray["familyName"])
            ->setGivenName($beneficiaryArray["givenName"])
            ->setStatus($beneficiaryArray["headHousehold"])
            ->setUpdatedOn(new \DateTime());

        $this->createProfile($beneficiary);

        $this->em->persist($beneficiary);

        return $beneficiary;
    }

    private function createProfile(Beneficiary $beneficiary){

        $profile = new Profile();

        /** @var Profile $profile */
        $profile->setPhoto("");
        $this->em->persist($profile);

        $beneficiary->setProfile($profile);
        $this->em->persist($beneficiary);

        return $profile;
    }
}