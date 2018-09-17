<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use CommonBundle\Entity\Location;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use BeneficiaryBundle\Form\HouseholdConstraints;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HouseholdService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var LocationService $locationService */
    private $locationService;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;


    /**
     * HouseholdService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param BeneficiaryService $beneficiaryService
     * @param RequestValidator $requestValidator
     * @param LocationService $locationService
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        BeneficiaryService $beneficiaryService,
        RequestValidator $requestValidator,
        LocationService $locationService,
        ValidatorInterface $validator,
        ContainerInterface $container
    )
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
        $this->locationService = $locationService;
        $this->validator = $validator;
        $this->container= $container;
    }

    /**
     * @param string $iso3
     * @param array $filters
     * @return mixed
     */
    public function getAll(string $iso3, array $filters)
    {
        $households = $this->em->getRepository(Household::class)->getAllBy($iso3, $filters);
        /** @var Household $household */
        foreach ($households as $household)
        {
            $numberDependents = 0;
            /** @var Beneficiary $beneficiary */
            foreach ($household->getBeneficiaries() as $beneficiary)
            {
                if ($beneficiary->getStatus() != 1)
                {
                    $numberDependents++;
                    $household->removeBeneficiary($beneficiary);
                }
            }
            $household->setNumberDependents($numberDependents);
        }
        return $households;
    }


    /**
     * @param array $householdArray
     * @param $project
     * @param bool $flush
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function create(array $householdArray, Project $project, bool $flush = true)
    {
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

        /** @var Household $household */
        $household = new Household();
        $household->setNotes($householdArray["notes"])
            ->setLivelihood($householdArray["livelihood"])
            ->setLongitude($householdArray["longitude"])
            ->setLatitude($householdArray["latitude"])
            ->setAddressStreet($householdArray["address_street"])
            ->setAddressPostcode($householdArray["address_postcode"])
            ->setAddressNumber($householdArray["address_number"]);

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

        // Save or update location instance
        $location = $this->locationService->getOrSaveLocation($householdArray['__country'], $householdArray["location"]);
        if (null === $location)
            throw new \Exception("Location was not found.");
        $household->setLocation($location);
        $project = $this->em->getRepository(Project::class)->find($project);
        if (!$project instanceof Project)
            throw new \Exception("This project is not found");
        $household->addProject($project);


        $this->em->persist($household);

        if (!empty($householdArray["beneficiaries"]))
        {
            $hasHead = false;
            $beneficiariesPersisted = [];
            foreach ($householdArray["beneficiaries"] as $beneficiaryToSave)
            {
                try
                {
                    $beneficiary = $this->beneficiaryService->updateOrCreate($household, $beneficiaryToSave, false);
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

        if (!empty($householdArray["country_specific_answers"]))
        {
            foreach ($householdArray["country_specific_answers"] as $country_specific_answer)
            {
                $this->addOrUpdateCountrySpecific($household, $country_specific_answer, false);
            }
        }
        if ($flush)
        {
            $this->em->flush();
            $household = $this->em->getRepository(Household::class)->find($household->getId());
            $country_specific_answers = $this->em->getRepository(CountrySpecificAnswer::class)->findByHousehold($household);
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
            foreach ($country_specific_answers as $country_specific_answer)
            {
                $household->addCountrySpecificAnswer($country_specific_answer);
            }
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

        return $household;
    }

    /**
     * @param Household $household
     * @param Project $project
     * @param array $householdArray
     * @param bool $updateBeneficiary => If true, we update the beneficiaries inside the array
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function update(Household $household, Project $project, array $householdArray, bool $updateBeneficiary = true)
    {
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)->find($household);
        $household->setNotes($householdArray["notes"])
            ->setLivelihood($householdArray["livelihood"])
            ->setLongitude($householdArray["longitude"])
            ->setLatitude($householdArray["latitude"])
            ->setAddressStreet($householdArray["address_street"])
            ->setAddressPostcode($householdArray["address_postcode"])
            ->setAddressNumber($householdArray["address_number"]);

        $project = $this->em->getRepository(Project::class)->find($project);
        if (!$project instanceof Project)
            throw new \Exception("This project is not found");


        if (!in_array($project, $household->getProjects()->toArray()))
            $household->addProject($project);

        // Save or update location instance
        $location = $this->locationService->getOrSaveLocation($householdArray['__country'], $householdArray["location"]);
        $household->setLocation($location);

        $this->em->persist($household);

        if (!empty($householdArray["beneficiaries"]))
        {
            foreach ($householdArray["beneficiaries"] as $beneficiaryToSave)
            {
                if ($updateBeneficiary)
                {
                    $beneficiary = $this->beneficiaryService->updateOrCreate($household, $beneficiaryToSave, false);
                    $this->em->persist($beneficiary);
                }
            }
        }

        if (!empty($householdArray["country_specific_answers"]))
        {
            foreach ($householdArray["country_specific_answers"] as $country_specific_answer)
            {
                $this->addOrUpdateCountrySpecific($household, $country_specific_answer, false);
            }
        }

        $this->em->flush();

        return $household;
    }

    /**
     * @param Household $household
     * @param Project $project
     */
    public function addToProject(Household &$household, Project $project)
    {
        if (!in_array($project, $household->getProjects()->toArray()))
        {
            $household->addProject($project);
            $this->em->persist($household);
            $this->em->flush();
        }
    }

    /**
     * @param Household $household
     * @param array $countrySpecificAnswerArray
     * @return array|CountrySpecificAnswer
     * @throws \Exception
     */
    public function addOrUpdateCountrySpecific(Household $household, array $countrySpecificAnswerArray, bool $flush)
    {
        $this->requestValidator->validate(
            "country_specific_answer",
            HouseholdConstraints::class,
            $countrySpecificAnswerArray,
            'any'
        );
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->find($countrySpecificAnswerArray["country_specific"]["id"]);
        if (!$countrySpecific instanceof CountrySpecific)
            throw new \Exception("This country specific is unknown");

        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)
            ->findOneBy([
                "countrySpecific" => $countrySpecific,
                "household" => $household
            ]);
        if (!$countrySpecificAnswer instanceof CountrySpecificAnswer)
        {
            $countrySpecificAnswer = new CountrySpecificAnswer();
            $countrySpecificAnswer->setCountrySpecific($countrySpecific)
                ->setHousehold($household);
        }

        $countrySpecificAnswer->setAnswer($countrySpecificAnswerArray["answer"]);

        $this->em->persist($countrySpecificAnswer);
        if ($flush)
            $this->em->flush();

        return $countrySpecificAnswer;
    }

    /**
     * @param Household $household
     * @return Household
     */
    public function remove(Household $household)
    {
        $household->setArchived(true);
        $this->em->persist($household);
        $this->em->flush();

        return $household;
    }

    /**
     * @return mixed
     */
    public function exportToCsv() {

        $exportableTable = $this->em->getRepository(Household::class)->findAll();
        return  $this->container->get('export_csv_service')->export($exportableTable);

    }
}