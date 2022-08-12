<?php

namespace NewApiBundle\Utils;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Phone;
use NewApiBundle\Entity\Profile;
use NewApiBundle\Entity\Referral;
use NewApiBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Form\HouseholdConstraints;
use NewApiBundle\Repository\BeneficiaryRepository;
use NewApiBundle\Repository\HouseholdRepository;
use NewApiBundle\Repository\NationalIdRepository;
use NewApiBundle\Repository\PhoneRepository;
use NewApiBundle\Repository\ProfileRepository;
use NewApiBundle\Repository\VulnerabilityCriterionRepository;
use NewApiBundle\Controller\ExportController;
use NewApiBundle\Exception\ExportNoDataException;
use NewApiBundle\Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\BenefciaryPatchInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdFilterInputType;
use NewApiBundle\InputType\HouseholdOrderInputType;
use NewApiBundle\Request\Pagination;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var ValidatorInterface $validator */
    private $validator;

    /**
     * @var ExportService
     */
    private $exportService;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var HouseholdRepository
     */
    private $householdRepository;

    /**
     * @var VulnerabilityCriterionRepository
     */
    private $vulnerabilityCriterionRepository;

    /**
     * @var PhoneRepository
     */
    private $phoneRepository;

    /**
     * @var NationalIdRepository
     */
    private $nationalIdRepository;

    /**
     * @var ProfileRepository
     */
    private $profileRepository;

    public function __construct(
        EntityManagerInterface           $entityManager,
        RequestValidator                 $requestValidator,
        ValidatorInterface               $validator,
        ExportService                    $exportService,
        BeneficiaryRepository            $beneficiaryRepository,
        HouseholdRepository              $householdRepository,
        VulnerabilityCriterionRepository $vulnerabilityCriterionRepository,
        PhoneRepository                  $phoneRepository,
        NationalIdRepository             $nationalIdRepository,
        ProfileRepository                $profileRepository
    ) {
        $this->em = $entityManager;
        $this->requestValidator = $requestValidator;
        $this->validator = $validator;
        $this->exportService = $exportService;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->householdRepository = $householdRepository;
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
        $this->phoneRepository = $phoneRepository;
        $this->nationalIdRepository = $nationalIdRepository;
        $this->profileRepository = $profileRepository;
    }

    public function createPhone(PhoneInputType $inputType): Phone
    {
        $phone = new Phone();

        $number = $inputType->getNumber();
        if (preg_match('/^0/', $number)) {
            $number = substr($number, 1);
        }

        $phone->setType($inputType->getType());
        $phone->setPrefix($inputType->getPrefix());
        $phone->setNumber($number);
        $phone->setProxy($inputType->getProxy());

        $this->em->persist($phone);

        return $phone;
    }

    public function createNationalId(NationalIdCardInputType $inputType): NationalId
    {
        $nationalId = new NationalId();

        $nationalId->setIdType($inputType->getType());
        $nationalId->setIdNumber($inputType->getNumber());

        $this->em->persist($nationalId);

        return $nationalId;
    }

    /**
     * @param Beneficiary          $beneficiary
     * @param BeneficiaryInputType $inputType
     *
     * @return Beneficiary
     */
    public function update(Beneficiary $beneficiary, BeneficiaryInputType $inputType): Beneficiary
    {
        $beneficiaryPerson = $beneficiary->getPerson();

        $beneficiaryPerson->setGender($inputType->getGender())
            ->setDateOfBirth($inputType->getDateOfBirth())
            ->setEnGivenName($inputType->getEnGivenName())
            ->setEnFamilyName($inputType->getEnFamilyName())
            ->setEnParentsName($inputType->getEnParentsName())
            ->setLocalGivenName($inputType->getLocalGivenName())
            ->setLocalFamilyName($inputType->getLocalFamilyName())
            ->setLocalParentsName($inputType->getLocalParentsName());

        $beneficiary->setHead($inputType->isHead())
            ->setResidencyStatus($inputType->getResidencyStatus())
            ->setUpdatedOn(new \DateTime()); //TODO use doctrine lifecycle callback

        //phones
        foreach ($beneficiaryPerson->getPhones() as $oldPhone) {
            $this->em->remove($oldPhone);
        }
        $beneficiaryPerson->getPhones()->clear();

        foreach ($inputType->getPhones() as $phoneInputType) {
            $phone = $this->createPhone($phoneInputType);
            $phone->setPerson($beneficiaryPerson);
            $beneficiaryPerson->addPhone($phone);
        }

        //national ids
        foreach ($beneficiaryPerson->getNationalIds() as $nationalId) {
            $this->em->remove($nationalId);
        }
        $beneficiaryPerson->getNationalIds()->clear();

        foreach ($inputType->getNationalIdCards() as $nationalIdInputType) {
            $nationalId = $this->createNationalId($nationalIdInputType);
            $nationalId->setPerson($beneficiaryPerson);
            $beneficiaryPerson->addNationalId($nationalId);
        }

        //vulnerability criteria
        $beneficiary->getVulnerabilityCriteria()->clear();
        foreach ($inputType->getVulnerabilityCriteria() as $vulnerabilityCriterionName) {
            /** @var VulnerabilityCriterion $criterion */
            $criterion = $this->vulnerabilityCriterionRepository->findOneBy(['fieldString' => $vulnerabilityCriterionName]);
            $beneficiary->addVulnerabilityCriterion($criterion);
        }

        //referral
        $referral = $beneficiaryPerson->getReferral();

        if (is_null($referral)) {
            if (!is_null($inputType->getReferralType())) {
                $referral = new Referral();
                $this->em->persist($referral);
            }
        } else {
            if (!is_null($inputType->getReferralType())) {
                $referral->setType($inputType->getReferralType());
                $referral->setComment($inputType->getReferralComment());
            } else {
                $this->em->remove($referral);
            }
        }

        $this->em->persist($beneficiary);

        return $beneficiary;
    }

    public function create(BeneficiaryInputType $inputType): Beneficiary
    {
        $beneficiary = new Beneficiary();
        $beneficiary
            ->setHead($inputType->isHead())
            ->setResidencyStatus($inputType->getResidencyStatus())
            ->setUpdatedOn(new \DateTime());

        foreach ($inputType->getVulnerabilityCriteria() as $id => $vulnerability_criterion) {
            $beneficiary->addVulnerabilityCriterion($this->getVulnerabilityCriterion($vulnerability_criterion));
        }

        $person = $beneficiary->getPerson();
        $person->setGender($inputType->getGender())
            ->setDateOfBirth($inputType->getDateOfBirth())
            ->setEnFamilyName($inputType->getEnFamilyName())
            ->setEnGivenName($inputType->getEnGivenName())
            ->setEnParentsName($inputType->getEnParentsName())
            ->setLocalFamilyName($inputType->getLocalFamilyName())
            ->setLocalGivenName($inputType->getLocalGivenName())
            ->setLocalParentsName($inputType->getLocalParentsName())
            ->setUpdatedOn(new \DateTime())
            ->setProfile(new Profile())
        ;
        $person->getProfile()->setPhoto('');

        foreach ($inputType->getPhones() as $phoneInputType) {
            $phone = $this->createPhone($phoneInputType);
            $person->addPhone($phone);
            $phone->setPerson($person);
            $this->em->persist($phone);
        }

        foreach ($inputType->getNationalIdCards() as $nationalIdArray) {
            $nationalId = $this->createNationalId($nationalIdArray);
            $person->addNationalId($nationalId);
            $nationalId->setPerson($person);
            $this->em->persist($nationalId);
        }

        // $this->createProfile($person, $inputType->getProfile()); TODO

        $previousReferral = $person->getReferral();
        if ($previousReferral) {
            $this->em->remove($previousReferral);
        }
        if ($inputType->hasReferral()) {
            $referral = new Referral();
            $referral->setType($inputType->getReferralType())
                ->setComment($inputType->getReferralComment());
            $person->setReferral($referral);
            $this->em->persist($referral);
        }

        $this->em->persist($beneficiary);

        return $beneficiary;
    }

    /**
     * @param Household $household
     * @param array $beneficiaryArray
     * @param $flush
     * @return Beneficiary|null|object
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     * @deprecated dont use at all
     */
    public function updateOrCreate(Household $household, array $beneficiaryArray, $flush)
    {
        if ($beneficiaryArray["gender"] === 'Male' || $beneficiaryArray["gender"] === 'M') {
            $beneficiaryArray["gender"] = 1;
        } elseif ($beneficiaryArray["gender"] === 'Female' || $beneficiaryArray["gender"] === 'F') {
            $beneficiaryArray["gender"] = 0;
        }

        if (array_key_exists('phone1_type', $beneficiaryArray)) {
            unset($beneficiaryArray['phone1_type']);
            unset($beneficiaryArray['phone1_prefix']);
            unset($beneficiaryArray['phone1_number']);
            unset($beneficiaryArray['phone1_proxy']);
        }

        if (array_key_exists('phone2_type', $beneficiaryArray)) {
            unset($beneficiaryArray['phone2_type']);
            unset($beneficiaryArray['phone2_prefix']);
            unset($beneficiaryArray['phone2_number']);
            unset($beneficiaryArray['phone2_proxy']);
        }

        if (array_key_exists('national_id_type', $beneficiaryArray)) {
            unset($beneficiaryArray['national_id_type']);
            unset($beneficiaryArray['national_id_number']);
        }
        
        if (strrpos($beneficiaryArray['date_of_birth'], '/') !== false) {
            $beneficiaryArray['date_of_birth'] = str_replace('/', '-', $beneficiaryArray['date_of_birth']);
        }
 

        $this->requestValidator->validate(
            "beneficiary",
            HouseholdConstraints::class,
            $beneficiaryArray,
            'any'
        );

        if (array_key_exists("id", $beneficiaryArray) && $beneficiaryArray['id'] !== null) {
            $beneficiary = $this->beneficiaryRepository->find($beneficiaryArray["id"]);
            if (!$beneficiary instanceof Beneficiary) {
                throw new \Exception("Beneficiary was not found.");
            }
            if ($beneficiary->getHousehold() !== $household) {
                throw new \Exception("You are trying to update a beneficiary in the wrong household.");
            }
            
            // Clear vulnerability criteria, phones and national id
            $beneficiary->setVulnerabilityCriteria(null);
            $items = $this->phoneRepository->findByPerson($beneficiary->getPerson());
            foreach ($items as $item) {
                $this->em->remove($item);
            }
            $items = $this->nationalIdRepository->findByPerson($beneficiary->getPerson());
            foreach ($items as $item) {
                $this->em->remove($item);
            }

            if ($flush) {
                $this->em->flush();
            }
        } else {
            $beneficiary = new Beneficiary();
            $beneficiary->setHousehold($household);
        }

        $beneficiary->setGender(PersonGender::valueFromAPI($beneficiaryArray["gender"]))
            ->setDateOfBirth(\DateTime::createFromFormat('d-m-Y', $beneficiaryArray["date_of_birth"]))
            ->setEnFamilyName($beneficiaryArray["en_family_name"])
            ->setEnGivenName($beneficiaryArray["en_given_name"])
            ->setLocalFamilyName($beneficiaryArray["local_family_name"])
            ->setLocalGivenName($beneficiaryArray["local_given_name"])
            ->setStatus($beneficiaryArray["status"])
            ->setResidencyStatus($beneficiaryArray["residency_status"])
            ->setUpdatedOn(new \DateTime());

        $beneficiary->getPerson()
            ->setLocalParentsName($beneficiaryArray['local_parents_name'] ?? null)
            ->setEnParentsName($beneficiaryArray['en_parents_name'] ?? null);

        $errors = $this->validator->validate($beneficiary);
        if (count($errors) > 0) {
            $errorsMessage = "";
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                if ("" !== $errorsMessage) {
                    $errorsMessage .= " ";
                }
                $errorsMessage .= $error->getMessage();
            }
            throw new \Exception($errorsMessage);
        }


        foreach ($beneficiaryArray["vulnerability_criteria"] as $vulnerability_criterion) {
            $beneficiary->addVulnerabilityCriterion($this->getVulnerabilityCriterion($vulnerability_criterion["id"]));
        }
        foreach ($beneficiaryArray["phones"] as $phoneArray) {
            if (!empty($phoneArray["type"]) && !empty($phoneArray["prefix"]) && !empty($phoneArray["number"])) {
                $phone = $this->getOrSavePhone($beneficiary, $phoneArray, false);
                $beneficiary->addPhone($phone);
            }
        }

        foreach ($beneficiaryArray["national_ids"] as $nationalIdArray) {
            if (!empty($nationalIdArray["id_type"]) && !empty($nationalIdArray["id_number"])) {
                $nationalId = $this->getOrSaveNationalId($beneficiary, $nationalIdArray, false);
                $beneficiary->addNationalId($nationalId);
            }
        }

        $this->getOrSaveProfile($beneficiary, $beneficiaryArray["profile"], false);
        $this->updateReferral($beneficiary, $beneficiaryArray);

        $this->em->persist($beneficiary);
        if ($flush) {
            $this->em->flush();
        }

        return $beneficiary;
    }

    /**
     * @param $vulnerabilityCriterionId
     * @return VulnerabilityCriterion
     * @throws \Exception
     */
    public function getVulnerabilityCriterion($vulnerabilityCriterionId)
    {
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        $vulnerabilityCriterion = $this->vulnerabilityCriterionRepository->findOneBy(['fieldString' => $vulnerabilityCriterionId]);

        if (!$vulnerabilityCriterion) {
            $vulnerabilityCriterion = $this->vulnerabilityCriterionRepository->find($vulnerabilityCriterionId);
        }

        if (!$vulnerabilityCriterion instanceof VulnerabilityCriterion) {
            throw new \Exception("Vulnerability $vulnerabilityCriterionId doesn't exist.");
        }
        return $vulnerabilityCriterion;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param array $phoneArray
     * @param $flush
     * @return Phone|null|object
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function getOrSavePhone(Beneficiary $beneficiary, array $phoneArray, $flush)
    {
        if (!$phoneArray['proxy'] || ($phoneArray['proxy'] && $phoneArray['proxy'] === 'N')) {
            $phoneArray['proxy'] = false;
        } elseif ($phoneArray['proxy'] && $phoneArray['proxy'] === 'Y') {
            $phoneArray['proxy'] = true;
        }
            
        if (preg_match('/^0/', $phoneArray['number'])) {
            $phoneArray['number'] = substr($phoneArray['number'], 1);
        }

        $this->requestValidator->validate(
            "phone",
            HouseholdConstraints::class,
            $phoneArray,
            'any'
        );


        $phone = new Phone();
        $phone->setPerson($beneficiary->getPerson())
            ->setType($phoneArray["type"])
            ->setNumber($phoneArray["number"])
            ->setPrefix($phoneArray["prefix"])
            ->setProxy(array_key_exists("proxy", $phoneArray) ? $phoneArray["proxy"] : false);

        $this->em->persist($phone);
        if ($flush) {
            $this->em->flush();
        }

        return $phone;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param array $nationalIdArray
     * @param $flush
     * @return NationalId|null|object
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function getOrSaveNationalId(Beneficiary $beneficiary, array $nationalIdArray, $flush)
    {
        $this->requestValidator->validate(
            "nationalId",
            HouseholdConstraints::class,
            $nationalIdArray,
            'any'
        );
        $nationalId = new NationalId();
        $nationalId->setPerson($beneficiary->getPerson())
            ->setIdType($nationalIdArray["id_type"])
            ->setIdNumber($nationalIdArray["id_number"]);

        $this->em->persist($nationalId);
        if ($flush) {
            $this->em->flush();
        }

        return $nationalId;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param array $profileArray
     * @param $flush
     * @return Profile|null|object
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function getOrSaveProfile(Beneficiary $beneficiary, array $profileArray, $flush)
    {
        $this->requestValidator->validate(
            "profile",
            HouseholdConstraints::class,
            $profileArray,
            'any'
        );

        $profile = $beneficiary->getProfile();
        if (null === $profile) {
            $profile = new Profile();
        } else {
            $profile = $this->profileRepository->find($profile);
        }

        /** @var Profile $profile */
        $profile->setPhoto($profileArray["photo"]);
        $this->em->persist($profile);

        $beneficiary->setProfile($profile);
        $this->em->persist($beneficiary);

        if ($flush) {
            $this->em->flush();
        }

        return $profile;
    }

    /**
     * @param Beneficiary $beneficiary
     */
    public function remove(Beneficiary $beneficiary): void
    {
        $beneficiary->setArchived();
    }

    /**
     * @param string $iso3
     * @return int
     */
    public function countAll(string $iso3): int
    {
        return (int) $this->beneficiaryRepository->countAllInCountry($iso3);
    }

    /**
     * @param string $iso3
     * @return int
     */
    public function countAllServed(string $iso3): int
    {
        return (int) $this->beneficiaryRepository->countServedInCountry($iso3);
    }

    /**
     * @param string $type
     * @param string $countryIso3
     * @param        $filters
     * @param        $ids
     *
     * @return string
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function exportToCsvDeprecated(string $type, string $countryIso3, $filters, $ids): string
    {
        $households = null;
        $exportableTable = [];
        if ($ids) {
            $households = $this->householdRepository->getAllByIds($ids);
        } else if ($filters) {
            // $households = $this->householdService->getAll($countryIso3, $filters)[1];
            // This should be not used this way
            throw new \Exception('Using deprecated method.');
        } else {
            $exportableTable = $this->beneficiaryRepository->getAllInCountry($countryIso3);
        }

        if ('csv' !== $type && count($households) > ExportController::EXPORT_LIMIT) {
            $count = count($households);
            throw new BadRequestHttpException("Too much households ($count) to export. Limit is ".ExportController::EXPORT_LIMIT);
        }
        if ('csv' === $type && count($households) > ExportController::EXPORT_LIMIT_CSV) {
            $count = count($households);
            throw new BadRequestHttpException("Too much households ($count) to export. Limit for CSV is ".ExportController::EXPORT_LIMIT_CSV);
        }
        
        if ($households) {
            foreach ($households as $household) {
                foreach ($household->getBeneficiaries() as $beneficiary) {
                    array_push($exportableTable, $beneficiary);
                }
            }
        }

        if ('csv' !== $type && count($exportableTable) > ExportController::EXPORT_LIMIT) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException("Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit is ".ExportController::EXPORT_LIMIT);
        }
        if ('csv' === $type && count($exportableTable) > ExportController::EXPORT_LIMIT_CSV) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException("Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit for CSV is ".ExportController::EXPORT_LIMIT_CSV);
        }

        try {
            return $this->exportService->export($exportableTable, 'beneficiaryhousehoulds', $type);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException("No data to export.");
        }
    }

    /**
     * @param string                   $type
     * @param string                   $countryIso3
     * @param HouseholdFilterInputType $filter
     * @param Pagination               $pagination
     * @param HouseholdOrderInputType  $order
     *
     * @return string
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportToCsv(
        string $type,
        string $countryIso3,
        HouseholdFilterInputType $filter,
        Pagination $pagination,
        HouseholdOrderInputType $order
    ): string {
        $households = $this->householdRepository->findByParams($countryIso3, $filter, $order, $pagination);

        if ('csv' !== $type && count($households) > ExportController::EXPORT_LIMIT) {
            $count = count($households);
            throw new BadRequestHttpException("Too much households ($count) to export. Limit is ".ExportController::EXPORT_LIMIT);
        }
        if ('csv' === $type && count($households) > ExportController::EXPORT_LIMIT_CSV) {
            $count = count($households);
            throw new BadRequestHttpException("Too much households ($count) to export. Limit for CSV is ".ExportController::EXPORT_LIMIT_CSV);
        }

        $exportableTable = [];
        if ($households) {
            foreach ($households as $household) {
                foreach ($household->getBeneficiaries() as $beneficiary) {
                    array_push($exportableTable, $beneficiary);
                }
            }
        }

        if ('csv' !== $type && count($exportableTable) > ExportController::EXPORT_LIMIT) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException("Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit is ".ExportController::EXPORT_LIMIT);
        }
        if ('csv' === $type && count($exportableTable) > ExportController::EXPORT_LIMIT_CSV) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException("Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit for CSV is ".ExportController::EXPORT_LIMIT_CSV);
        }

        try {
            return $this->exportService->export($exportableTable, 'beneficiaryhousehoulds', $type);
        } catch (ExportNoDataException $e) {
            throw new BadRequestHttpException("No data to export.");
        }
    }

    public function patch(Beneficiary $beneficiary, BenefciaryPatchInputType $inputType)
    {
        if (($inputType->getReferralType() || $inputType->getReferralComment()) && null == $beneficiary->getPerson()->getReferral()) {
            $beneficiary->getPerson()->setReferral(new Referral());
        }

        if ($inputType->getReferralComment()) {
            $beneficiary->getPerson()->getReferral()->setComment($inputType->getReferralComment());
        }

        if ($inputType->getReferralType()) {
            $beneficiary->getPerson()->getReferral()->setType($inputType->getReferralType());
        }

        $this->em->persist($beneficiary->getPerson()->getReferral());
        $this->em->persist($beneficiary->getPerson());
        $this->em->flush();

        return $beneficiary;
    }

    public function updateReferral(Beneficiary $beneficiary, array $beneficiaryData) {
        if (array_key_exists('referral_type', $beneficiaryData) && array_key_exists('referral_comment', $beneficiaryData) &&
            $beneficiaryData['referral_type'] && $beneficiaryData['referral_comment']) {
            $previousReferral = $beneficiary->getReferral();
            if ($previousReferral) {
                $this->em->remove($previousReferral);
            }
            $referral = new Referral();
            $referral->setType($beneficiaryData['referral_type'])
                ->setComment($beneficiaryData['referral_comment']);
            $beneficiary->setReferral($referral);
            $this->em->persist($referral);
        }
    }
}
