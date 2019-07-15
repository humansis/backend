<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use PhpOption\Tests\PhpOptionRepo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use DistributionBundle\Utils\DistributionBeneficiaryService;
use DistributionBundle\Entity\DistributionData;

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

    /** @var DistributionBeneficiaryService $dbs */
    private $dbs;


    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        RequestValidator $requestValidator,
        ValidatorInterface $validator,
        ContainerInterface $container,
        DistributionBeneficiaryService $distributionBeneficiary
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->requestValidator = $requestValidator;
        $this->validator = $validator;
        $this->container = $container;
        $this->beneficiary = new Beneficiary();
        $this->dbs = $distributionBeneficiary;
    }


    /**
     * Get all vulnerability criteria
     * @return array
     */
    public function getAllVulnerabilityCriteria()
    {
        return $this->em->getRepository(VulnerabilityCriterion::class)->findAll();
    }


    /**
     * @param Household $household
     * @param array $beneficiaryArray
     * @param $flush
     * @return Beneficiary|null|object
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function update(Household $household, array $beneficiaryArray, $flush)
    {
        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryArray["id"]);
        if (!$beneficiary instanceof Beneficiary) {
            throw new \Exception("Beneficiary was not found.");
        }
        if ($beneficiary->getHousehold() !== $household) {
            throw new \Exception("You are trying to update a beneficiary in the wrong household.");
        }
        
        // Clear vulnerability criteria, phones and national id
        $beneficiary->setVulnerabilityCriteria(null);
        $items = $this->em->getRepository(Phone::class)->findByBeneficiary($beneficiary);
        foreach ($items as $item) {
            $this->em->remove($item);
        }
        $items = $this->em->getRepository(NationalId::class)->findByBeneficiary($beneficiary);
        foreach ($items as $item) {
            $this->em->remove($item);
        }

        if ($flush) {
            $this->em->flush();
        }

        $beneficiary = $this->edit($household, $beneficiaryArray, $beneficiary, $flush);
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
    public function create(Household $household, array $beneficiaryArray, $flush)
    {
        $beneficiary = new Beneficiary();
        $beneficiary->setHousehold($household);
        $beneficiary = $this->edit($household, $beneficiaryArray, $beneficiary, $flush);
        return $beneficiary;
    }


    /**
     * @param Household $household
     * @param Beneficiary $beneficiary
     * @param array $beneficiaryArray
     * @param $flush
     * @return Beneficiary|null|object
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function edit(Household $household, array $beneficiaryArray, Beneficiary $beneficiary, $flush)
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

        $beneficiary->setGender($beneficiaryArray["gender"])
            ->setDateOfBirth(\DateTime::createFromFormat('d-m-Y', $beneficiaryArray["date_of_birth"]))
            ->setEnFamilyName($beneficiaryArray["en_family_name"])
            ->setEnGivenName($beneficiaryArray["en_given_name"])
            ->setLocalFamilyName($beneficiaryArray["local_family_name"])
            ->setLocalGivenName($beneficiaryArray["local_given_name"])
            ->setStatus($beneficiaryArray["status"])
            ->setResidencyStatus($beneficiaryArray["residency_status"])
            ->setUpdatedOn(new \DateTime());

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
                $phone = $this->savePhone($beneficiary, $phoneArray, false);
                $beneficiary->addPhone($phone);
            }
        }

        foreach ($beneficiaryArray["national_ids"] as $nationalIdArray) {
            if (!empty($nationalIdArray["id_type"]) && !empty($nationalIdArray["id_number"])) {
                $nationalId = $this->saveNationalId($beneficiary, $nationalIdArray, false);
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
    public function savePhone(Beneficiary $beneficiary, array $phoneArray, $flush)
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
        $phone->setBeneficiary($beneficiary)
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
    public function saveNationalId(Beneficiary $beneficiary, array $nationalIdArray, $flush)
    {
        $this->requestValidator->validate(
            "nationalId",
            HouseholdConstraints::class,
            $nationalIdArray,
            'any'
        );
        $nationalId = new NationalId();
        $nationalId->setBeneficiary($beneficiary)
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
        if ($beneficiary->getStatus() === 1) {
            return false;
        }

        $nationalIds = $this->em->getRepository(NationalId::class)->findByBeneficiary($beneficiary);
        $profile = $this->em->getRepository(Profile::class)->find($beneficiary->getProfile());
        foreach ($nationalIds as $nationalId) {
            $this->em->remove($nationalId);
        }

        $phones = $this->em->getRepository(Phone::class)->findByBeneficiary($beneficiary);
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
     * @param DistributionData $distributionData
     * @param string $type
     * @return mixed
     */
    public function exportToCsvBeneficiariesInDistribution(DistributionData $distributionData, string $type)
    {
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getNotRemovedofDistribution($distributionData);
        return $this->container->get('export_csv_service')->export($beneficiaries, 'beneficiaryInDistribution', $type);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryIso3, $filters, $ids)
    {
        $households = null;
        if ($ids) {
            $households = $this->em->getRepository(Household::class)->getAllByIds($ids);
        } else if ($filters) {
            $households = $this->container->get('beneficiary.household_service')->getAll($countryIso3, $filters)[1];
        } else {
            $exportableTable = $this->em->getRepository(Beneficiary::class)->getAllInCountry($countryIso3);	
        }
        
        if ($households) {
            $exportableTable = [];
            foreach ($households as $household) {
                foreach ($household->getBeneficiaries() as $beneficiary) {
                    array_push($exportableTable, $beneficiary);
                }
            }
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
    public function updateFromDistribution(Beneficiary $beneficiary, array $beneficiaryData)
    {
        try {
            $this->updateReferral($beneficiary, $beneficiaryData);
            $this->em->persist($beneficiary);
        } catch (\Exception $e) {
            throw new \Exception('Error updating Beneficiary');
        }
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
