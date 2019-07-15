<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\CampAddress;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use CommonBundle\Entity\Location;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class HouseholdService
 * @package BeneficiaryBundle\Utils
 */
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
    ) {
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
        $pageIndex = $filters['pageIndex'];
        $pageSize = $filters['pageSize'];
        $filter = $filters['filter'];
        $sort = $filters['sort'];

        $limitMinimum = $pageIndex * $pageSize;

        $households = $this->em->getRepository(Household::class)->getAllBy($iso3, $limitMinimum, $pageSize, $sort, $filter);
        $length = $households[0];
        $households = $households[1];

        return [$length, $households];
    }

    /**
     * @param array $householdArray
     * @param $projectsArray
     * @param bool $flush
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function create(array $householdArray, array $projectsArray, bool $flush = true)
    {
        $household = new Household();
        if (!empty($projectsArray) && (gettype($projectsArray[0]) === 'string' || gettype($projectsArray[0]) === 'integer')) {
            $projectsArray = $this->em->getRepository(Project::class)->findBy(["id" => $projectsArray]);
        }
        // Add projects
        foreach ($projectsArray as $project) {
            if (! $project instanceof Project) {
                throw new \Exception("The project could not be found.");
            }
            $household->addProject($project);
        }
        if (!empty($householdArray["beneficiaries"])) {
            $this->handleBeneficiaries($householdArray, $projectsArray, $household);
        }

        $household = $this->edit($householdArray, $projectsArray, $household, $flush);

        return $household;
    }

    /**
     * @param array $householdArray
     * @param $projectsArray
     * @param bool $flush
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function update(array $householdArray, array $projectsArray, $household = null, bool $flush = true)
    {
        if (!empty($projectsArray) && (gettype($projectsArray[0]) === 'string' || gettype($projectsArray[0]) === 'integer')) {
            $projectsArray = $this->em->getRepository(Project::class)->findBy(["id" => $projectsArray]);
        }
        // Add projects
        foreach ($projectsArray as $project) {
            if (! $project instanceof Project) {
                throw new \Exception("The project could not be found.");
            }
            if (! $household->getProjects()->contains($project)) {
                $household->addProject($project);
            }
        }

        if ($household->getHouseholdLocations()) {
            foreach ($household->getHouseholdLocations() as $initialHouseholdLocation) {
                $this->em->remove($initialHouseholdLocation);
            }
        }
        $this->em->flush();

        if (!empty($householdArray["beneficiaries"])) {
            $beneficiariesPersisted = $this->handleBeneficiaries($householdArray, $projectsArray, $household);
            $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findBy(["household" => $household]);
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
                $this->em->remove($beneficiaryToRemove);
            }
            
        }
        
        $household = $this->edit($householdArray, $projectsArray, $household, $flush);

        return $household;
    }

    /**
     * @param array $householdArray
     * @param $projectsArray
     * @param bool $flush
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function edit(array $householdArray, array $projectsArray, $household = null, bool $flush = true)
    {
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

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
                        throw new \Exception("Location was not found.");
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
                    throw new \Exception("Location was not found.");
                }
                $address = new Address();
                $address->setNumber($householdLocation['address']['number'])
                ->setStreet($householdLocation['address']['street'])
                ->setPostcode($householdLocation['address']['postcode'])
                ->setLocation($location);
                $newHouseholdLocation->setAddress($address);
            }
            $household->addHouseholdLocation($newHouseholdLocation);
            $this->em->persist($newHouseholdLocation);
        }
        
        
        $household->setNotes($householdArray["notes"])
        ->setLivelihood($householdArray["livelihood"])
        ->setLongitude($householdArray["longitude"])
        ->setLatitude($householdArray["latitude"])
        ->setIncomeLevel($householdArray["income_level"])
        ->setCopingStrategiesIndex($householdArray["coping_strategies_index"])
        ->setFoodConsumptionScore($householdArray["food_consumption_score"]);
        
        $this->em->persist($household);
        
        if (!empty($householdArray["country_specific_answers"])) {
            foreach ($householdArray["country_specific_answers"] as $country_specific_answer) {
                $this->addOrUpdateCountrySpecific($household, $country_specific_answer, false);
            }
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
    
    public function handleBeneficiaries(array $householdArray, array $projectsArray, &$household)
    {
        $hasHead = false;
        $beneficiariesPersisted = [];
        foreach ($householdArray["beneficiaries"] as $beneficiaryToSave) {
            try {
                if ($beneficiaryToSave['gender'] === 'Male') {
                    $beneficiaryToSave['gender'] = 1;
                } elseif ($beneficiaryToSave['gender'] === 'Female') {
                    $beneficiaryToSave['gender'] = 0;
                }
                
                if (! array_key_exists("id", $beneficiaryToSave) || !$beneficiaryToSave['id']) {
                    $beneficiary = $this->beneficiaryService->create($household, $beneficiaryToSave, false);
                    $household->addBeneficiary($beneficiary);
                } else {
                    $beneficiary = $this->beneficiaryService->update($household, $beneficiaryToSave, false);
                }
                $beneficiariesPersisted[] = $beneficiary;
            } catch (\Exception $exception) {
                throw $exception;
            }
            if ($beneficiary->getStatus()) {
                if ($hasHead) {
                    throw new \Exception("You have defined more than 1 head of household.");
                }
                $hasHead = true;
            }
            $this->em->persist($beneficiary);
        }

        return $beneficiariesPersisted;
    }

    /**
     * @param array $householdArray
     * @return array
     */
    public function removeBeneficiaries(array $householdArray)
    {
        $household = $this->em->getRepository(Household::class)->find($householdArray['id']);
        $beneficiaryIds = array_map(function ($beneficiary) {
            return $beneficiary['id'];
        }, $householdArray['beneficiaries']);

        // Remove beneficiaries that are not in the array
        foreach ($household->getBeneficiaries() as $beneficiary) {
            if (! in_array($beneficiary->getId(), $beneficiaryIds)) {
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
        if (! $household->getProjects()->contains($project)) {
            $household->addProject($project);
            $this->em->persist($household);
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

        if (!$countrySpecific instanceof CountrySpecific) {
            throw new \Exception("This country specific is unknown");
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

    public function removeMany(array $householdIds)
    {
        foreach ($householdIds as $householdId) {
            $household = $this->em->getRepository(Household::class)->find($householdId);
            $household->setArchived(true);
            $this->em->persist($household);
        }
        $this->em->flush();
        return "Households have been archived";
    }


    /**
     * @return mixed
     */
    public function exportToCsv()
    {
        $exportableTable = $this->em->getRepository(Household::class)->findAll();
        return  $this->container->get('export_csv_service')->export($exportableTable);
    }

    /**
     * @param array $householdsArray
     * @return array
     */
    public function getAllImported(array $householdsArray)
    {
        $householdsId = $householdsArray['households'];

        $households = array();

        foreach ($householdsId as $householdId) {
            $household = $this->em->getRepository(Household::class)->find($householdId);

            if ($household instanceof Household) {
                array_push($households, $household);
            }
        }

        return $households;
    }
}
