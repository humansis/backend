<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use BeneficiaryBundle\Utils\HouseholdService;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Assistance;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use NewApiBundle\DBAL\PersonGenderEnum;
use NewApiBundle\Enum\PersonGender;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Reader\Ods as OdsReader;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use BeneficiaryBundle\Utils\Mapper\CSVToArrayMapper;
use BeneficiaryBundle\Entity\HouseholdLocation;

/**
 * Class DistributionCSVService
 * @package DistributionBundle\Utils
 */
class DistributionCSVService
{
    /** @var EntityManagerInterface $em */
    private $em;
    
    /** @var HouseholdExportCSVService $exportCSVService */
    private $exportCSVService;
    
    /** @var ContainerInterface $container */
    private $container;
    
    
    
    /** @var HouseholdService $locationService */
    private $householdService;
    
    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var CSVToArrayMapper $CSVToArrayMapper */
    private $CSVToArrayMapper;

    /**
     * DistributionCSVService constructor.
     * @param EntityManagerInterface $entityManager
     * @param HouseholdExportCSVService $exportCSVService
     * @param ContainerInterface $container
     * @param HouseholdService $householdService
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param RequestValidator $requestValidator
     * @param CSVToArrayMapper $CSVToArrayMapper
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdExportCSVService $exportCSVService,
        ContainerInterface $container,
        HouseholdService $householdService,
        Serializer $serializer,
        ValidatorInterface $validator,
        RequestValidator $requestValidator,
        CSVToArrayMapper $CSVToArrayMapper
    ) {
        $this->em = $entityManager;
        $this->exportCSVService = $exportCSVService;
        $this->container = $container;
        $this->householdService = $householdService;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->requestValidator = $requestValidator;
        $this->CSVToArrayMapper = $CSVToArrayMapper;
    }

    /**
     * Defined the reader and transform CSV to array and check the difference between the database and the CSV.
     *
     * @param $countryIso3
     * @param $beneficiaries
     * @param UploadedFile $uploadedFile
     *
     * @return array
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function parseCSV($countryIso3, $beneficiaries, Assistance $assistance, UploadedFile $uploadedFile)
    {
        $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
        $worksheet = $spreadsheet->getSheet(0);
        $sheetArray = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . $worksheet->getHighestRow(), null, true, true, true);
        $headers = array_shift($sheetArray);
        $arrayWithKeys = array();
        foreach ($sheetArray as $beneficiaryIndex => $beneficiaryArray) {
            $beneficiaryWithKey = array();
            foreach ($headers as $index => $key) {
                if ($key == "gender") {
                    if (strcasecmp(trim($beneficiaryArray[$index]), 'Male') === 0 || strcasecmp(trim($beneficiaryArray[$index]), 'M') === 0) {
                        $beneficiaryArray[$index] = PersonGender::MALE;
                    } else {
                        $beneficiaryArray[$index] = PersonGender::FEMALE;
                    }
                }

                $beneficiaryWithKey[$key] = $beneficiaryArray[$index];
            }
            array_push($arrayWithKeys, $beneficiaryWithKey);
        }

        // Beneficiaries that are both in the file and the distribution, data will be updated
        $updateArray = array();
        // Beneficiaries that are in the distribution but not in the file
        $deleteArray = array();
        foreach ($beneficiaries as $beneficiary) {
            $inFile = false;
            foreach ($arrayWithKeys as $arrayBeneficiary) {
                if (($beneficiary->getLocalGivenName() === $arrayBeneficiary['localGivenName']
                        || $beneficiary->getLocalGivenName() === "")
                    && ($beneficiary->getLocalFamilyName() === $arrayBeneficiary['localFamilyName']
                        || $beneficiary->getLocalFamilyName() === "")) {
                    $arrayBeneficiary['id'] = $beneficiary->getId();
                    array_push($updateArray, $arrayBeneficiary);
                    $inFile = true;
                }
            }

            $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findOneBy(
                [
                    'beneficiary' => $beneficiary,
                    'assistance' => $assistance
                ]
            );
            if (! $inFile && !$assistanceBeneficiary->getRemoved()) {
                $beneficiaryToDelete = array(
                    'id' => $beneficiary->getId(),
                    'enGivenName' => $beneficiary->getEnGivenName(),
                    'enFamilyName' => $beneficiary->getEnFamilyName(),
                    'localGivenName' => $beneficiary->getLocalGivenName(),
                    'localFamilyName' => $beneficiary->getLocalFamilyName(),
                    'dateOfBirth' => $beneficiary->getDateOfBirthObject()->format('d-m-Y'),
                    'gender' => $beneficiary->getGender() ? PersonGender::valueToAPI($beneficiary->getGender()) : null,
                );
                array_push($deleteArray, $beneficiaryToDelete);
            }
        }

        // Names that are in the file but not in the distribution
        // New beneficiaries in the database or update existing beneficiary
        $newAndAddArray = array();
        foreach ($arrayWithKeys as $arrayWithKey) {
            $inDistribution = false;

            foreach ($updateArray as $value) {
                if ($arrayWithKey['localGivenName'] === $value['localGivenName'] && $arrayWithKey['localFamilyName'] === $value['localFamilyName']) {
                    $inDistribution = true;
                }
            }

            if (!$inDistribution) {
                array_push($newAndAddArray, $arrayWithKey);
            }
        }

        // Beneficiaries that will be created as a household of 1
        $createArray = array();
        // Beneficiaries in the database that will be added
        $addArray = array();
        
        foreach ($newAndAddArray as $beneficiaryArray) {
            $person = $this->em->getRepository(Person::class)->findOneBy(
                [
                    "localGivenName" => $beneficiaryArray['localGivenName'],
                    "localFamilyName" => $beneficiaryArray['localFamilyName']
                ]
            );
            $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneByPerson($person);
            if ($beneficiary instanceof Beneficiary) {
                // Check if the beneficiary is associate to the project of the distribution
                if (in_array($assistance->getProject(), $beneficiary->getHousehold()->getProjects()->getValues())) {
                    array_push($addArray, $beneficiaryArray);
                }
            } else {
                array_push($createArray, $beneficiaryArray);
            }
        }

        $allArray = array(
            'created' => $createArray,
            'added'   => $addArray,
            'deleted' => $deleteArray,
            'updated' => $updateArray
        );

        return $allArray;
    }

    /**
     * Recover the array of the CSV and save the data to the dataBase.
     *
     * @param string $countryIso3
     * @param Assistance $assistance
     * @param array     data
     *
     * @return array
     *
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function saveCSV(string $countryIso3, Assistance $assistance, array $data)
    {
        $distributionProject = $assistance->getProject();
        
        // Create
        foreach ($data['created'] as $beneficiaryToCreate) {
            if ($beneficiaryToCreate['head'] !== 'true') {
                throw new \Exception("You can only insert a head of the household in the file to import.");
            }

            // There the location is still filled with adm names and not id
            $locationArray = array(
                "country_iso3" => $countryIso3,
                "adm1" => $beneficiaryToCreate['adm1'],
                "adm2" => $beneficiaryToCreate['adm2'],
                "adm3" => $beneficiaryToCreate['adm3'],
                "adm4" => $beneficiaryToCreate['adm4']
            );

            $beneficiaryToCreate['location'] = $locationArray;
            $this->CSVToArrayMapper->mapLocation($beneficiaryToCreate);

            if ($beneficiaryToCreate['camp']) {
                if (!$beneficiaryToCreate['tent number']) {
                    throw new \Exception('You have to enter a tent number');
                }
                $campName = $beneficiaryToCreate['camp'];
                $householdLocations = [
                    [
                        'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                        'type' => HouseholdLocation::LOCATION_TYPE_CAMP,
                        'camp_address' => [
                            'camp' => [
                                'id' => null,
                                'name' => $campName,
                                'location' => $beneficiaryToCreate['location']
                            ],
                            'tent_number' =>  $beneficiaryToCreate['tent number'],
                        ]
                    ]
                ];
                $alreadyExistingCamp = $this->em->getRepository(Camp::class)->findOneBy(['name' => $campName]);
                if ($alreadyExistingCamp) {
                    $householdLocations[0]['camp_address']['camp']['id'] = $alreadyExistingCamp->getId();
                }
            } else if ($beneficiaryToCreate['addressNumber']) {
                if (!$beneficiaryToCreate['addressStreet'] || !$beneficiaryToCreate['addressPostcode']) {
                    throw new \Exception('The address is invalid');
                }
                $householdLocations = [
                    [
                        'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                        'type' => HouseholdLocation::LOCATION_TYPE_RESIDENCE,
                        'address' => [
                            'number' => $beneficiaryToCreate['addressNumber'],
                            'street' =>  $beneficiaryToCreate['addressStreet'],
                            'postcode' =>  $beneficiaryToCreate['addressPostcode'],
                            'location' => $beneficiaryToCreate['location']
                        ]
                    ]
                ];
            } else {
                throw new \Exception('There is no address of camp');
            }

            $phones = [];
            for ($i = 1; $i < 3; $i++) {
                if ($beneficiaryToCreate['type phone ' . $i] && $beneficiaryToCreate['prefix phone ' . $i] &&
                    $beneficiaryToCreate['phone ' . $i] && $beneficiaryToCreate['proxy phone ' . $i]
                ) {
                    array_push($phones, [
                        'prefix' => $beneficiaryToCreate['prefix phone ' . $i],
                        'type' => $beneficiaryToCreate['type phone ' . $i],
                        'proxy' => $beneficiaryToCreate['proxy phone ' . $i],
                        'number' => strval($beneficiaryToCreate['phone ' . $i])

                    ]);
                }
            }

            $nationalIds = [];
            if ($beneficiaryToCreate['ID Type'] && $beneficiaryToCreate['ID Number']) {
                array_push($nationalIds, [
                    'id_type' => $beneficiaryToCreate['ID Type'],
                    'id_number' => strval($beneficiaryToCreate['ID Number'])
                ]);
            }

            $vulnerabilityCriteria = [];
            $vulnerabilityCriteriaArray = array_map('trim', explode(';', $beneficiaryToCreate['vulnerabilityCriteria']));
            foreach ($vulnerabilityCriteriaArray as $item) {
                $vulnerabilityCriterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneByFieldString($item);
                if (!$vulnerabilityCriterion instanceof VulnerabilityCriterion) {
                    continue;
                }
                $vulnerabilityCriteria[] = ['id' => $vulnerabilityCriterion->getId()];
            }

            $householdToCreate = array(
                "__country" => $countryIso3,
                "livelihood" => $beneficiaryToCreate['livelihood'],
                "household_locations" => $householdLocations,
                "income_level" => $beneficiaryToCreate['incomeLevel'],
                "notes" => $beneficiaryToCreate['notes'],
                "latitude" => strval($beneficiaryToCreate['latitude']),
                "longitude" => strval($beneficiaryToCreate['longitude']),
                "country_specific_answers" => array(),
                "coping_strategies_index" => null,
                "food_consumption_score" => null,
                "beneficiaries" => array(
                    array(
                        "en_given_name" => $beneficiaryToCreate['enGivenName'],
                        "en_family_name" => $beneficiaryToCreate['enFamilyName'],
                        "local_given_name" => $beneficiaryToCreate['localGivenName'],
                        "local_family_name" => $beneficiaryToCreate['localFamilyName'],
                        "gender" => $beneficiaryToCreate['gender'],
                        "status" => 1,
                        "residency_status" => $beneficiaryToCreate['residencyStatus'],
                        "date_of_birth" => $beneficiaryToCreate['dateOfBirth'],
                        "profile" => array(
                            "photo" => ""
                        ),
                        "vulnerability_criteria" => $vulnerabilityCriteria,
                        "phones" => $phones,
                        "national_ids" => $nationalIds
                    )
                )
            );

            $this->CSVToArrayMapper->mapLivelihood($householdToCreate);

            $this->householdService->createOrEdit($householdToCreate, array($distributionProject));
            $person = $this->em->getRepository(Person::class)
                ->findOneBy([
                    "localGivenName" => $beneficiaryToCreate['localGivenName'],
                    'localFamilyName' => $beneficiaryToCreate['localFamilyName'],
                    'gender' => PersonGenderEnum::valueToDB($beneficiaryToCreate['gender'])
                ]);
            $toCreate = $this->em->getRepository(Beneficiary::class)->findOneByPerson($person);
            $this->em->persist($toCreate);
            
            // Add created beneficiary to distribution
            $newAssistanceBeneficiary = new AssistanceBeneficiary();
            $newAssistanceBeneficiary->setBeneficiary($toCreate)
                ->setAssistance($assistance)
                ->setRemoved(0)
                ->setJustification($beneficiaryToCreate['justification']);
            $this->em->persist($newAssistanceBeneficiary);
        }
        
        // Add
        foreach ($data['added'] as $beneficiaryToAdd) {
            $justification = $beneficiaryToAdd['justification'];
            $person = $this->em->getRepository(Person::class)->findOneBy(
                [
                    "localGivenName" => $beneficiaryToAdd['localGivenName'],
                    "localFamilyName" => $beneficiaryToAdd['localFamilyName']
                ]
            );
            $beneficiaryToAdd = $this->em->getRepository(Beneficiary::class)->findOneByPerson(
                $person
            );

            $household = $beneficiaryToAdd->getHousehold();
            if (! $household->getProjects()->contains($distributionProject)) {
                $household->addProject($distributionProject);
                $this->em->persist($household);
            }
            $assistanceBeneficiary = new AssistanceBeneficiary();
            $assistanceBeneficiary->setBeneficiary($beneficiaryToAdd)
                ->setAssistance($assistance)
                ->setRemoved(0)
                ->setJustification($justification);
            $this->em->persist($assistanceBeneficiary);
        }

        // Delete
        foreach ($data['deleted'] as $beneficiaryToRemove) {
            $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryToRemove['id']);
            $toRemove = $this->em->getRepository(AssistanceBeneficiary::class)
                ->findOneBy(
                    [
                        'beneficiary' => $beneficiary,
                        'assistance' => $assistance
                    ]
                );
            $toRemove->setRemoved(1)
                ->setJustification($beneficiaryToRemove['justification']);
            $this->em->persist($toRemove);
        }

        // Update
        foreach ($data['updated'] as $beneficiaryToUpdate) {
            $toUpdate = $this->em->getRepository(Beneficiary::class)
                ->find($beneficiaryToUpdate['id']);

            $assistanceBeneficiaryToUpdate = $this->em->getRepository(AssistanceBeneficiary::class)
                ->findOneBy(
                    [
                        'beneficiary' => $toUpdate,
                        'assistance' => $assistance
                    ]
                );
            
            if ($assistanceBeneficiaryToUpdate->getRemoved()) {
                $assistanceBeneficiaryToUpdate->setRemoved(0)
                    ->setJustification('');
                $this->em->persist($assistanceBeneficiaryToUpdate);
            }
            $toUpdate->setEnGivenName($beneficiaryToUpdate['enGivenName']);
            $toUpdate->setEnFamilyName($beneficiaryToUpdate['enFamilyName']);
            $toUpdate->setLocalGivenName($beneficiaryToUpdate['localGivenName']);
            $toUpdate->setLocalFamilyName($beneficiaryToUpdate['localFamilyName']);
            $toUpdate->setGender($beneficiaryToUpdate['gender']);
            $toUpdate->setStatus(($beneficiaryToUpdate['head']) === 'true' ? 1 : 0);
            $toUpdate->setResidencyStatus($beneficiaryToUpdate['residencyStatus']);
            $toUpdate->setDateOfBirth(\DateTime::createFromFormat('d-m-Y', $beneficiaryToUpdate['dateOfBirth']));
            

            $toUpdate->setVulnerabilityCriteria(null);
            if (strpos($beneficiaryToUpdate['vulnerabilityCriteria'], ",")) {
                $vulnerabilityCriteria = explode(",", $beneficiaryToUpdate['vulnerabilityCriteria']);
            } else {
                $vulnerabilityCriteria = [$beneficiaryToUpdate['vulnerabilityCriteria']];
            }

            foreach ($vulnerabilityCriteria as $criterion) {
                if ($criterion) {
                    $toUpdate->addVulnerabilityCriterion(
                        $this->getVulnerabilityCriterion($criterion)
                    );
                }
            }
            
            $phones = $this->em->getRepository(Phone::class)->findByPerson($toUpdate->getPerson());
            foreach ($phones as $phone) {
                $this->em->remove($phone);
            }
            $toUpdate->setPhones(null);

            foreach (['1', '2'] as $phoneIndex) {
                if ($beneficiaryToUpdate['phone ' . $phoneIndex] && $beneficiaryToUpdate['type phone ' . $phoneIndex] && $beneficiaryToUpdate['prefix phone ' . $phoneIndex]) {
                    $phone = new Phone();
                    $phone->setNumber($beneficiaryToUpdate['phone ' . $phoneIndex]);
                    $phone->setType($beneficiaryToUpdate['type phone ' . $phoneIndex]);
                    $phone->setPrefix($beneficiaryToUpdate['prefix phone ' . $phoneIndex]);
                    $phone->setProxy($beneficiaryToUpdate['proxy phone ' . $phoneIndex] === 1 ? true : false);
                    $phone->setPerson($toUpdate);
                    $toUpdate->addPhone($phone);
                }
            }

            $nationalIds = $this->em->getRepository(NationalId::class)->findByPerson($toUpdate->getPerson());
            foreach ($nationalIds as $nationalId) {
                $this->em->remove($nationalId);
            }
            $toUpdate->setNationalIds(null);

            if (!empty($beneficiaryToUpdate['ID Number']) && !empty($beneficiaryToUpdate['ID Type'])) {
                $newNationalId = new NationalId();
                $newNationalId->setIdNumber($beneficiaryToUpdate['ID Number']);
                $newNationalId->setIdType($beneficiaryToUpdate['ID Type']);
                $newNationalId->setPerson($toUpdate);
                $toUpdate->addNationalId($newNationalId);
            }

            $this->em->persist($toUpdate);

        }

        $assistance->setUpdatedOn(new \DateTime());

        $this->em->flush();
        
        return array(
            'result' => 'Beneficiary list updated.',
        );
    }

    /**
     * @param $vulnerabilityCriterionString
     * @return VulnerabilityCriterion
     * @throws \Exception
     */
    public function getVulnerabilityCriterion($vulnerabilityCriterionString)
    {
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        $vulnerabilityCriterion = $this->em->getRepository(VulnerabilityCriterion::class)->findBy(['fieldString' => $vulnerabilityCriterionString]);

        if (!$vulnerabilityCriterion[0] instanceof VulnerabilityCriterion) {
            throw new \Exception("This vulnerability doesn't exist.");
        }
        return $vulnerabilityCriterion[0];
    }
}
