<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\HouseholdService;
use BeneficiaryBundle\Utils\Mapper\HouseholdToCSVMapper;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use JMS\Serializer\Serializer;
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

/**
 * Class DistributionCSVService
 * @package DistributionBundle\Utils
 */
class DistributionCSVService
{
    /** @var EntityManagerInterface $em */
    private $em;
    
    /** @var ExportCSVService $exportCSVService */
    private $exportCSVService;
    
    /** @var ContainerInterface $container */
    private $container;
    
    /** @var HouseholdToCSVMapper $householdToCSVMapper */
    private $householdToCSVMapper;
    
    /** @var HouseholdService $locationService */
    private $householdService;
    
    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /**
     * DistributionCSVService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ExportCSVService $exportCSVService
     * @param ContainerInterface $container
     * @param HouseholdToCSVMapper $householdToCSVMapper
     * @param HouseholdService $householdService
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param RequestValidator $requestValidator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ExportCSVService $exportCSVService,
        ContainerInterface $container,
        HouseholdToCSVMapper $householdToCSVMapper,
        HouseholdService $householdService,
        Serializer $serializer,
        ValidatorInterface $validator,
        RequestValidator $requestValidator
    ) {
        $this->em = $entityManager;
        $this->exportCSVService = $exportCSVService;
        $this->container = $container;
        $this->householdToCSVMapper = $householdToCSVMapper;
        $this->householdService = $householdService;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->requestValidator = $requestValidator;
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
    public function parseCSV($countryIso3, $beneficiaries, DistributionData $distributionData, UploadedFile $uploadedFile)
    {
        $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
        $worksheet = $spreadsheet->getSheet(0);
        $sheetArray = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . $worksheet->getHighestRow(), null, true, true, true);
        $headers = array_shift($sheetArray);
        $arrayWithKeys = array();
        foreach ($sheetArray as $beneficiaryArray) {

            $beneficiaryWithKey = array();
            foreach ($headers as $index => $key) {
                if ($key == "gender") {
                    if ($beneficiaryArray[$index] == 'Male')
                        $beneficiaryArray[$index] = 1;
                    else
                        $beneficiaryArray[$index] = 0;
                }

                $beneficiaryWithKey[$key] = $beneficiaryArray[$index];
            }
            array_push($arrayWithKeys, $beneficiaryWithKey);
        }

        // Beneficiaries that are both in the file and the distribution, data will be updated
        $updateArray = array();
        // Beneficiaries that are in the distribution but not in the file
        $deleteArray = array();
        foreach($beneficiaries as $beneficiary) {
            $inFile = false;
            foreach($arrayWithKeys as $arrayBeneficiary) {
                if (($beneficiary->getGivenName() === $arrayBeneficiary['givenName']
                        || $beneficiary->getGivenName() === "")
                    && ($beneficiary->getFamilyName() === $arrayBeneficiary['familyName']
                        || $beneficiary->getFamilyName() === "")) {
                    $arrayBeneficiary['id'] = $beneficiary->getId();
                    array_push($updateArray, $arrayBeneficiary);
                    $inFile = true;
                }
            }
            if (! $inFile) {
                $beneficiaryToDelete = array(
                    'id' => $beneficiary->getId(),
                    'givenName' => $beneficiary->getGivenName(),
                    'familyName' => $beneficiary->getFamilyName(),
                    'dateOfBirth' => $beneficiary->getDateOfBirth()->format('Y-m-d'),
                    'gender' => $beneficiary->getGender()
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
                if ($arrayWithKey['givenName'] === $value['givenName'] && $arrayWithKey['familyName'] === $value['familyName']) {
                    $inDistribution = true;
                }
            }

            if (!$inDistribution)
                array_push($newAndAddArray, $arrayWithKey);
        }

        // Beneficiaries that will be created as a household of 1
        $createArray = array();
        // Beneficiaries in the database that will be added
        $addArray = array();
        
        foreach ($newAndAddArray as $beneficiaryArray) {
            $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy(
                [
                    "givenName" => $beneficiaryArray['givenName'],
                    "familyName" => $beneficiaryArray['familyName']
                ]
            );
            if ($beneficiary instanceof Beneficiary) {
                // Check if the beneficiary is associate to the project of the distribution
                if (in_array($distributionData->getProject(), $beneficiary->getHousehold()->getProjects()->getValues())) {
                    array_push($addArray, $beneficiary);
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
     * @param DistributionData $distributionData
     * @param array     data
     *
     * @return array
     *
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function saveCSV(string $countryIso3, DistributionData $distributionData, array $data)
    {
        $distributionProject = $distributionData->getProject();
        
        // Create
        foreach ($data['created'] as $beneficiaryToCreate) {

            if($beneficiaryToCreate['status'] != 1)
                throw new \Exception("You must insert only a head of the household in the file to import.");

            // Define location array
            $adm1 = $this->em->getRepository(Adm1::class)->findOneBy(["name" => $beneficiaryToCreate['adm1']]);
            $adm2 = $this->em->getRepository(Adm2::class)->findOneBy(["name" => $beneficiaryToCreate['adm2']]);
            $adm3 = $this->em->getRepository(Adm3::class)->findOneBy(["name" => $beneficiaryToCreate['adm3']]);
            $adm4 = $this->em->getRepository(Adm4::class)->findOneBy(["name" => $beneficiaryToCreate['adm4']]);
            
            $adm4Name = $beneficiaryToCreate['adm4'];
            if ($adm4 instanceof Adm4) {
                $adm3 = $adm4->getAdm3();
            }
            if ($adm3 instanceof Adm3) {
                $adm3Name = $adm3->getName();
                $adm2 = $adm3->getAdm2();
            }
            if ($adm2 instanceof Adm2) {
                $adm2Name = $adm2->getName();
                $adm1 = $adm2->getAdm1();
            }
            if ($adm1 instanceof Adm1) {
                $country = $adm1->getCountryISO3();
                $adm1Name = $adm1->getName();
            }
            $locationArray = array(
                "country_iso3" => $country,
                "adm1" => $adm1Name,
                "adm2" => $adm2Name,
                "adm3" => $adm3Name,
                "adm4" => $adm4Name       
            );
            
            $householdToCreate = array(
                "__country" => $countryIso3,
                "address_street" => $beneficiaryToCreate['addressStreet'],
                "address_number" => strval($beneficiaryToCreate['addressNumber']),
                "address_postcode" => strval($beneficiaryToCreate['addressPostcode']),
                "livelihood" => $beneficiaryToCreate['livelihood'],
                "notes" => $beneficiaryToCreate['notes'],
                "latitude" => strval($beneficiaryToCreate['latitude']),
                "longitude" => strval($beneficiaryToCreate['longitude']),
                "location" => $locationArray,
                "country_specific_answers" => array(),
                "beneficiaries" => array(
                    array(
                        "given_name" => $beneficiaryToCreate['givenName'],
                        "family_name" => $beneficiaryToCreate['familyName'],
                        "gender" => $beneficiaryToCreate['gender'],
                        "status" => 1,
                        "residency_status" => $beneficiaryToCreate['residencyStatus'],
                        "date_of_birth" => $beneficiaryToCreate['dateOfBirth'],
                        "profile" => array(
                            "photo" => ""
                        ),
                        "vulnerability_criteria" => array(),
                        "phones" => array(),
                        "national_ids" => array()
                    )
                )
            );
            $this->householdService->createOrEdit($householdToCreate, array($distributionProject));
            $toCreate = $this->em->getRepository(Beneficiary::class)
                ->findOneBy(["givenName" => $beneficiaryToCreate['givenName'], 'familyName' => $beneficiaryToCreate['familyName'], 'gender' => $beneficiaryToCreate['gender']]);
            $this->em->persist($toCreate);
            
            // Add created beneficiary to distribution
            $newDistributionBeneficiary = new DistributionBeneficiary();
            $newDistributionBeneficiary->setBeneficiary($toCreate);
            $newDistributionBeneficiary->setDistributionData($distributionData);
            $this->em->persist($newDistributionBeneficiary);
        }
        
        // Add
        foreach ($data['added'] as $beneficiaryToAdd) {
            if ($beneficiaryToAdd instanceof Beneficiary)
                $beneficiaryToAdd = $this->em->getRepository(Beneficiary::class)->find($beneficiaryToAdd->getId());
            else
                $beneficiaryToAdd = $this->em->getRepository(Beneficiary::class)->find($beneficiaryToAdd['id']);

            $household = $beneficiaryToAdd->getHousehold();
            if (! $household->getProjects()->contains($distributionProject)) {
                $household->addProject($distributionProject);
                $this->em->persist($household);
            }
            $distributionBeneficiary = new DistributionBeneficiary();
            $distributionBeneficiary->setBeneficiary($beneficiaryToAdd);
            $distributionBeneficiary->setDistributionData($distributionData);
            $this->em->persist($distributionBeneficiary);
        }

        // Delete
        foreach ($data['deleted'] as $beneficiaryToRemove) {
            $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryToRemove['id']);
            $toRemove = $this->em->getRepository(DistributionBeneficiary::class)
                ->findOneBy(
                    [
                        'beneficiary' => $beneficiary,
                        'distributionData' => $distributionData
                    ]
                );
            $this->em->remove($toRemove);
        }

        // Update
        foreach ($data['updated'] as $beneficiaryToUpdate) {
            $toUpdate = $this->em->getRepository(Beneficiary::class)
                ->find($beneficiaryToUpdate['id']);
            
            $toUpdate->setGivenName($beneficiaryToUpdate['givenName']);
            $toUpdate->setFamilyName($beneficiaryToUpdate['familyName']);
            $toUpdate->setGender($beneficiaryToUpdate['gender']);
            $toUpdate->setStatus(($beneficiaryToUpdate['status']) ? $beneficiaryToUpdate['status'] : 0);
            $toUpdate->setResidencyStatus($beneficiaryToUpdate['residencyStatus']);
            $toUpdate->setDateOfBirth(new \DateTime($beneficiaryToUpdate['dateOfBirth']));
            
            $toUpdate->setVulnerabilityCriteria(null);
            if (strpos($beneficiaryToUpdate['vulnerabilityCriteria'], ",")) {
                $vulnerabilityCriteria = explode(",", $beneficiaryToUpdate['vulnerabilityCriteria']);
            } else {
                $vulnerabilityCriteria = [$beneficiaryToUpdate['vulnerabilityCriteria']];
            }

            foreach($vulnerabilityCriteria as $criterion) {
                if ($criterion) {
                    $toUpdate->addVulnerabilityCriterion(
                        $this->getVulnerabilityCriterion($criterion)
                    );
                }
            }
            
            $phones = $this->em->getRepository(Phone::class)->findByBeneficiary($toUpdate);
            foreach ($phones as $phone) {
                $this->em->remove($phone);
            }
            $toUpdate->setPhones(null);
            if (strpos($beneficiaryToUpdate['phones'], ",")) {
                $phones = explode(",", $beneficiaryToUpdate['phones']);
            } else {
                $phones = [$beneficiaryToUpdate['phones']];
            }
            foreach($phones as $phone) {
                if ($phone) {
                    $newPhone = new Phone();
                    $newPhone->setNumber($phone);
                    $newPhone->setType('mobile');
                    $newPhone->setProxy(false);
                    $newPhone->setBeneficiary($toUpdate);
                    $toUpdate->addPhone(
                        $newPhone
                    );
                }
            }

            $nationalIds = $this->em->getRepository(NationalId::class)->findByBeneficiary($toUpdate);
            foreach ($nationalIds as $nationalId)
            {
                $this->em->remove($nationalId);
            }
            $toUpdate->setNationalIds(null);
            if (strpos($beneficiaryToUpdate['nationalIds'], ",")) {
                $nationalIds = explode(",", $beneficiaryToUpdate['nationalIds']);
            } else {
                $nationalIds = [$beneficiaryToUpdate['nationalIds']];
            }
            foreach($nationalIds as $nationalId) {
                if ($nationalId) {
                    $newNationalId = new NationalId();
                    $newNationalId->setIdNumber($nationalId);
                    $newNationalId->setIdType('card');
                    $newNationalId->setBeneficiary($toUpdate);
                    $toUpdate->addNationalId(
                        $newNationalId
                    );
                }
            }

            $this->em->merge($toUpdate);
        }

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

        if (!$vulnerabilityCriterion[0] instanceof VulnerabilityCriterion)
            throw new \Exception("This vulnerability doesn't exist.");
        return $vulnerabilityCriterion[0];
    }
}
