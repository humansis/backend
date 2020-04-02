<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\CampAddress;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\InstitutionLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\InstitutionConstraints;
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
 * Class InstitutionService
 * @package BeneficiaryBundle\Utils
 */
class InstitutionService
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
     * InstitutionService constructor.
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

        $institutions = $this->em->getRepository(Institution::class)->getAllBy($iso3, $limitMinimum, $pageSize, $sort);
        var_dump($institutions); die(__METHOD__);
        $length = $institutions[0];
        $institutions = $institutions[1];

        return [$length, $institutions];
    }

    /**
     * @param array $institutionArray
     * @param $projectsArray
     * @param bool $flush
     * @return Institution
     * @throws ValidationException
     * @throws \Exception
     */
    public function createOrEdit(array $institutionArray, array $projectsArray, $institution = null, bool $flush = true)
    {
        if (!empty($projectsArray) && (gettype($projectsArray[0]) === 'string' || gettype($projectsArray[0]) === 'integer')) {
            $projectsArray = $this->em->getRepository(Project::class)->findBy(["id" => $projectsArray]);
        }
        $actualAction = 'update';
        $this->requestValidator->validate(
            "institution",
            InstitutionConstraints::class,
            $institutionArray,
            'any'
        );

        /** @var Institution $institution */
        if (!$institution) {
            $actualAction = 'create';
            $institution = new Institution();
        }

        if ($institution->getInstitutionLocations()) {
            foreach ($institution->getInstitutionLocations() as $initialInstitutionLocation) {
                $this->em->remove($initialInstitutionLocation);
            }
        }
        $this->em->flush();

        foreach ($institutionArray['institution_locations'] as $institutionLocation) {
            $newInstitutionLocation = new InstitutionLocation();
            $newInstitutionLocation
                ->setLocationGroup($institutionLocation['location_group'])
                ->setType($institutionLocation['type']);

            if ($institutionLocation['type'] === InstitutionLocation::LOCATION_TYPE_CAMP) {
                // Try to find the camp with the name in the request
                $camp = $this->em->getRepository(Camp::class)->findOneBy(['name' => $institutionLocation['camp_address']['camp']['name']]);
                // Or create a camp with the name in the request
                if (!$camp instanceof Camp) {
                    $location = $this->locationService->getLocation($institutionArray['__country'], $institutionLocation['camp_address']['camp']['location']);
                    if (null === $location) {
                        throw new \Exception("Location was not found.");
                    }
                    $camp = new Camp();
                    $camp->setName($institutionLocation['camp_address']['camp']['name']);
                    $camp->setLocation($location);
                }
                $campAddress = new CampAddress();
                $campAddress->setTentNumber($institutionLocation['camp_address']['tent_number'])
                    ->setCamp($camp);
                $newInstitutionLocation->setCampAddress($campAddress);
            } else {
                $location = $this->locationService->getLocation($institutionArray['__country'], $institutionLocation['address']["location"]);
                if (null === $location) {
                    throw new \Exception("Location was not found.");
                }
                $address = new Address();
                $address->setNumber($institutionLocation['address']['number'])
                    ->setStreet($institutionLocation['address']['street'])
                    ->setPostcode($institutionLocation['address']['postcode'])
                    ->setLocation($location);
                $newInstitutionLocation->setAddress($address);
            }
            $institution->addInstitutionLocation($newInstitutionLocation);
            $this->em->persist($newInstitutionLocation);
        }


        $institution->setNotes($institutionArray["notes"])
            ->setLivelihood($institutionArray["livelihood"])
            ->setIncomeLevel($institutionArray["income_level"])
            ->setCopingStrategiesIndex($institutionArray["coping_strategies_index"])
            ->setFoodConsumptionScore($institutionArray["food_consumption_score"]);
        $institution->setLongitude($institutionArray["longitude"]);
        $institution->setLatitude($institutionArray["latitude"]);

        // Remove projects if the institution is not part of them anymore
        if ($actualAction === "update") {
            $oldProjects = $institution->getProjects()->toArray();
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
                $institution->removeProject($projectToRemove);
            }
        }

        // Add projects
        foreach ($projectsArray as $project) {
            if (! $project instanceof Project) {
                throw new \Exception("The project could not be found.");
            }
            if ($actualAction !== 'update' || ! $institution->getProjects()->contains($project)) {
                $institution->addProject($project);
            }
        }
        
        $this->em->persist($institution);

        if (!empty($institutionArray["beneficiaries"])) {
            $hasHead = false;
            $beneficiariesPersisted = [];
            if ($actualAction === "update") {
                $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findBy(["institution" => $institution]);
            }
            foreach ($institutionArray["beneficiaries"] as $beneficiaryToSave) {
                try {
                    if ($beneficiaryToSave['gender'] === 'Male') {
                        $beneficiaryToSave['gender'] = 1;
                    } elseif ($beneficiaryToSave['gender'] === 'Female') {
                        $beneficiaryToSave['gender'] = 0;
                    }

                    $beneficiary = $this->beneficiaryService->updateOrCreate($institution, $beneficiaryToSave, false);
                    if (! array_key_exists("id", $beneficiaryToSave)) {
                        $institution->addBeneficiary($beneficiary);
                    }
                    $beneficiariesPersisted[] = $beneficiary;
                } catch (\Exception $exception) {
                    throw $exception;
                }
                if ($beneficiary->getStatus()) {
                    if ($hasHead) {
                        throw new \Exception("You have defined more than 1 head of institution.");
                    }
                    $hasHead = true;
                }
                $this->em->persist($beneficiary);
            }
            
            // Remove beneficiaries that are not in the institution anymore
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
                    $institution->removeBeneficiary($beneficiaryToRemove);
                    $this->em->remove($beneficiaryToRemove);
                }
            }
        }
        
        if (!empty($institutionArray["country_specific_answers"])) {
            foreach ($institutionArray["country_specific_answers"] as $country_specific_answer) {
                $this->addOrUpdateCountrySpecific($institution, $country_specific_answer, false);
            }
        }
        
        if ($flush) {
            $this->em->flush();
            $institution = $this->em->getRepository(Institution::class)->find($institution->getId());
            $country_specific_answers = $this->em->getRepository(CountrySpecificAnswer::class)->findByInstitution($institution);
            foreach ($country_specific_answers as $country_specific_answer) {
                $institution->addCountrySpecificAnswer($country_specific_answer);
            }
        }


        return $institution;
    }

    /**
     * @param array $institutionArray
     * @return array
     */
    public function removeBeneficiaries(array $institutionArray)
    {
        $institution = $this->em->getRepository(Institution::class)->find($institutionArray['id']);
        $beneficiaryIds = array_map(function ($beneficiary) {
            return $beneficiary['id'];
        }, $institutionArray['beneficiaries']);

        // Remove beneficiaries that are not in the array
        foreach ($institution->getBeneficiaries() as $beneficiary) {
            if (! in_array($beneficiary->getId(), $beneficiaryIds)) {
                $this->em->remove($beneficiary);
            }
        }

        return $institutionArray;
    }

    /**
     * @param Institution $institution
     * @param Project $project
     */
    public function addToProject(Institution &$institution, Project $project)
    {
        if (! $institution->getProjects()->contains($project)) {
            $institution->addProject($project);
            $this->em->persist($institution);
        }
    }

    /**
     * @param Institution $institution
     * @param array $countrySpecificAnswerArray
     * @return array|CountrySpecificAnswer
     * @throws \Exception
     */
    public function addOrUpdateCountrySpecific(Institution $institution, array $countrySpecificAnswerArray, bool $flush)
    {
        $this->requestValidator->validate(
            "country_specific_answer",
            InstitutionConstraints::class,
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
                "institution" => $institution
            ]);

        if ($countrySpecificAnswerArray["answer"]) {
            if (!$countrySpecificAnswer instanceof CountrySpecificAnswer) {
                $countrySpecificAnswer = new CountrySpecificAnswer();
                $countrySpecificAnswer->setCountrySpecific($countrySpecific)
                    ->setInstitution($institution);
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

    public function remove(Institution $institution)
    {
        $institution->setArchived(true);
        $this->em->persist($institution);
        $this->em->flush();

        return $institution;
    }

    public function removeMany(array $institutionIds)
    {
        foreach ($institutionIds as $institutionId) {
            $institution = $this->em->getRepository(Institution::class)->find($institutionId);
            $institution->setArchived(true);
            $this->em->persist($institution);
        }
        $this->em->flush();
        return "Institutions have been archived";
    }


    /**
     * @return mixed
     */
    public function exportToCsv()
    {
        $exportableTable = $this->em->getRepository(Institution::class)->findAll();
        return  $this->container->get('export_csv_service')->export($exportableTable);
    }

    /**
     * @param array $institutionsArray
     * @return array
     */
    public function getAllImported(array $institutionsArray)
    {
        $institutionsId = $institutionsArray['institutions'];

        $institutions = array();

        foreach ($institutionsId as $institutionId) {
            $institution = $this->em->getRepository(Institution::class)->find($institutionId);

            if ($institution instanceof Institution) {
                array_push($institutions, $institution);
            }
        }

        return $institutions;
    }
}
