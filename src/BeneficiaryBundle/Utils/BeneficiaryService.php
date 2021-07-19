<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use CommonBundle\Controller\ExportController;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\InputType\BenefciaryPatchInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdFilterInputType;
use NewApiBundle\InputType\HouseholdOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use PhpOption\Tests\PhpOptionRepo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use DistributionBundle\Utils\AssistanceBeneficiaryService;
use DistributionBundle\Entity\Assistance;

class BeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /** @var Beneficiary $beneficiary */
    private $beneficiary;

    /** @var AssistanceBeneficiaryService $dbs */
    private $dbs;


    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        RequestValidator $requestValidator,
        ValidatorInterface $validator,
        ContainerInterface $container,
        AssistanceBeneficiaryService $assistanceBeneficiary
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->requestValidator = $requestValidator;
        $this->validator = $validator;
        $this->container = $container;
        $this->beneficiary = new Beneficiary();
        $this->dbs = $assistanceBeneficiary;
    }


    /**
     * Get all vulnerability criteria
     * @return array
     */
    public function getAllVulnerabilityCriteria()
    {
        return $this->em->getRepository(VulnerabilityCriterion::class)->findAllActive();
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
     *
     * //TODO rename after old methods removal
     */
    public function newUpdate(Beneficiary $beneficiary, BeneficiaryInputType $inputType): Beneficiary
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
        }

        //national ids
        foreach ($beneficiaryPerson->getNationalIds() as $nationalId) {
            $this->em->remove($nationalId);
        }
        $beneficiaryPerson->getNationalIds()->clear();

        foreach ($inputType->getNationalIdCards() as $nationalIdInputType) {
            $nationalId = $this->createNationalId($nationalIdInputType);
            $nationalId->setPerson($beneficiaryPerson);
        }

        //vulnerability criteria
        $beneficiary->getVulnerabilityCriteria()->clear();
        foreach ($inputType->getVulnerabilityCriteria() as $vulnerabilityCriterionName) {
            /** @var VulnerabilityCriterion $criterion */
            $criterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneBy(['fieldString' => $vulnerabilityCriterionName]);
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

        $this->em->flush();

        return $beneficiary;
    }

    /**
     * @param Household $household
     * @param array $beneficiaryArray
     * @param $flush
     * @return Beneficiary|null|object
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function updateOrCreate(Household $household, array $beneficiaryArray, $flush)
    {
        if ($beneficiaryArray["gender"] === 'Male' || $beneficiaryArray["gender"] === 'M') {
            $beneficiaryArray["gender"] = Person::GENDER_MALE;
        } elseif ($beneficiaryArray["gender"] === 'Female' || $beneficiaryArray["gender"] === 'F') {
            $beneficiaryArray["gender"] = Person::GENDER_FEMALE;
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
            $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryArray["id"]);
            if (!$beneficiary instanceof Beneficiary) {
                throw new \Exception("Beneficiary was not found.");
            }
            if ($beneficiary->getHousehold() !== $household) {
                throw new \Exception("You are trying to update a beneficiary in the wrong household.");
            }
            
            // Clear vulnerability criteria, phones and national id
            $beneficiary->setVulnerabilityCriteria(null);
            $items = $this->em->getRepository(Phone::class)->findByPerson($beneficiary->getPerson());
            foreach ($items as $item) {
                $this->em->remove($item);
            }
            $items = $this->em->getRepository(NationalId::class)->findByPerson($beneficiary->getPerson());
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

        $beneficiary->setGender($beneficiaryArray["gender"])
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
        $vulnerabilityCriterion = $this->em->getRepository(VulnerabilityCriterion::class)->find($vulnerabilityCriterionId);

        if (!$vulnerabilityCriterion instanceof VulnerabilityCriterion) {
            throw new \Exception("This vulnerability doesn't exist.");
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
            $profile = $this->em->getRepository(Profile::class)->find($profile);
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
     * @return bool
     */
    public function remove(Beneficiary $beneficiary)
    {
        if ($beneficiary->isHead()) {
            return false;
        }

        foreach ($beneficiary->getImportBeneficiaries() as $importLink) {
            $this->em->remove($importLink);
        }

        $nationalIds = $this->em->getRepository(NationalId::class)->findByPerson($beneficiary->getPerson());
        $profile = $this->em->getRepository(Profile::class)->find($beneficiary->getProfile());
        foreach ($nationalIds as $nationalId) {
            $this->em->remove($nationalId);
        }

        $phones = $this->em->getRepository(Phone::class)->findByPerson($beneficiary->getPerson());
        foreach ($phones as $phone) {
            $this->em->remove($phone);
        }
        $this->em->remove($beneficiary);
        $this->em->remove($profile);
        $this->em->flush();
        return true;
    }

    /**
     * @param string $iso3
     * @return int
     */
    public function countAll(string $iso3)
    {
        $count = (int) $this->em->getRepository(Beneficiary::class)->countAllInCountry($iso3);
        return $count;
    }

    /**
     * @param string $iso3
     * @return int
     */
    public function countAllServed(string $iso3)
    {
        $count = (int) $this->em->getRepository(Beneficiary::class)->countServedInCountry($iso3);

        return $count;
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function exportToCsvDeprecated(string $type, string $countryIso3, $filters, $ids)
    {
        $households = null;
        $exportableTable = [];
        if ($ids) {
            $households = $this->em->getRepository(Household::class)->getAllByIds($ids);
        } else if ($filters) {
            $households = $this->container->get('beneficiary.household_service')->getAll($countryIso3, $filters)[1];
        } else {
            $exportableTable = $this->em->getRepository(Beneficiary::class)->getAllInCountry($countryIso3);	
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

        return $this->container->get('export_csv_service')->export($exportableTable, 'beneficiaryhousehoulds', $type);
    }

    /**
     * @param string                   $type
     * @param string                   $countryIso3
     * @param HouseholdFilterInputType $filter
     * @param Pagination               $pagination
     * @param HouseholdOrderInputType  $order
     *
     * @return mixed
     */
    public function exportToCsv(
        string $type,
        string $countryIso3,
        HouseholdFilterInputType $filter,
        Pagination $pagination,
        HouseholdOrderInputType $order
    )
    {
        $households = $this->em->getRepository(Household::class)->findByParams($countryIso3, $filter, $order, $pagination);

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

        return $this->container->get('export_csv_service')->export($exportableTable, 'beneficiaryhousehoulds', $type);
    }

    /**
     * Updates a beneficiary
     *
     * @param Beneficiary $beneficiary
     * @param array $beneficiaryData
     * @return Beneficiary
     * @throws \Exception
     */
    public function update(Beneficiary $beneficiary, array $beneficiaryData)
    {
        try {
            $this->updateReferral($beneficiary, $beneficiaryData);
            $this->em->persist($beneficiary);
        } catch (\Exception $e) {
            throw new \Exception('Error updating Beneficiary');
        }
        return $beneficiary;
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
