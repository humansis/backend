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
     * @param $countryISO3
     * @param DistributionData $distributionData
     *
     * @return array
     *
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($countryISO3, DistributionData $distributionData)
    {
        $spreadsheet = $this->exportCSVService->buildFile($countryISO3);
        $spreadsheet = $this->buildFile($countryISO3, $spreadsheet, $distributionData);

        $writer = new CsvWriter($spreadsheet);

        $dataPath = $this->container->getParameter('kernel.root_dir').'/../var';
        $filename = $dataPath.'/pattern_household_'.$countryISO3.'.csv';
        $writer->save($filename);

        //Récupération du contenu et suppression du fichier
        $fileContent = file_get_contents($filename);
        unlink($filename);
        $file = [
            'content' => $fileContent,
            'filename' => 'export_distribution_'.$distributionData->getName().'.csv',
        ];

        return $file;
    }

    /**
     * @param DistributionData $distributionData
     * @param UploadedFile     $uploadedFile
     *
     * @return bool
     *
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import(DistributionData $distributionData, UploadedFile $uploadedFile)
    {
        $reader = new CsvReader();
        $reader->setDelimiter(';');
        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();
        $sheetArray = $worksheet->toArray(null, true, true, true);
        $index = 1;
        $columnIdSync = null;
        // Remove useless lines (like headers)
        while ($index < Household::firstRow) {
            if ($index === Household::indexRowHeader) {
                $columnIdSync = $this->findColumnId($sheetArray[$index]);
            }
            unset($sheetArray[$index]);
            ++$index;
        }
        // Analyze each rows of the file
        $this->analyzeArray($distributionData, $sheetArray, $columnIdSync);

        return true;
    }

    /**
     * @param DistributionData $distributionData
     * @param array            $receiversArray
     * @param $columnIdSync
     *
     * @throws \Exception
     */
    public function analyzeArray(DistributionData $distributionData, array $receiversArray, $columnIdSync)
    {
        /** @var DistributionData $distributionData */
        $distributionData = $this->em->getRepository(DistributionData::class)->find($distributionData);
        /** @var DistributionBeneficiary $distributionBeneficiary */
        foreach ($distributionData->getDistributionBeneficiaries() as $distributionBeneficiary) {
            $isFound = false;
            if (0 === $distributionData->getType()) {
                $idFromDatabase = $distributionBeneficiary->getBeneficiary()->getHousehold()->getId();
            } elseif (1 === $distributionData->getType()) {
                $idFromDatabase = $distributionBeneficiary->getBeneficiary()->getId();
            } else {
                throw new
                \Exception("Error system : This type of distribution '{$distributionData->getType()}' is undefined.");
            }
            foreach ($receiversArray as $index => $receiver) {
                if (intval($receiver[$columnIdSync]) === $idFromDatabase) {
                    $isFound = true;
                    continue;
                }
            }
            if (!$isFound) {
                $this->em->remove($distributionBeneficiary);
            }
        }
        $this->em->flush();
    }

    /**
     * @param array $headerArray
     *
     * @return int|string
     *
     * @throws \Exception
     */
    public function findColumnId(array $headerArray)
    {
        if (empty($headerArray)) {
            throw new \Exception('Your CSV is malformed.');
        }
        $nameHeaderSync = DistributionData::NAME_HEADER_ID;
        foreach ($headerArray as $columnCSV => $nameHeader) {
            if ($nameHeaderSync === $nameHeader) {
                return $columnCSV;
            }
        }

        throw new \Exception("Your file is malformed. The column '$nameHeader' was not found.");
    }

    /**
     * @param $countryISO3
     * @param Spreadsheet      $spreadsheet
     * @param DistributionData $distributionData
     *
     * @return Spreadsheet
     *
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function buildFile($countryISO3, Spreadsheet $spreadsheet, DistributionData $distributionData)
    {
        $receivers = $this->buildDataBeneficiary($distributionData);

        $worksheet = $spreadsheet->getActiveSheet();
        $this->householdToCSVMapper->fromHouseholdToCSV($worksheet, $receivers, $countryISO3);

        return $spreadsheet;
    }

    /**
     * Return an array with households.
     * If the distribution is for beneficiary, households will contain only one beneficiary.
     *
     * @param DistributionData $distributionData
     *
     * @return array
     *
     * @throws \Exception
     */
    public function buildDataBeneficiary(DistributionData $distributionData)
    {
        $distributionBeneficiaries = $distributionData->getDistributionBeneficiaries();
        $receivers = [];
        foreach ($distributionBeneficiaries as $distributionBeneficiary) {
            /** @var Beneficiary $beneficiary */
            $beneficiary = $distributionBeneficiary->getBeneficiary();
            switch ($distributionData->getType()) {
                case 0:
                    $household = $beneficiary->getHousehold();
                    foreach ($this->em->getRepository(Beneficiary::class)->findByHousehold($household) as $beneficiaryHH) {
                        $isInside = false;
                        foreach ($household->getBeneficiaries() as $beneficiaryAlreadyInside) {
                            if ($beneficiaryAlreadyInside->getId() === $beneficiaryHH->getId()) {
                                $isInside = true;
                                break;
                            }
                        }
                        if (!$isInside) {
                            $household->addBeneficiary($beneficiaryHH);
                        }
                    }
                    break;

                case 1:
                    $household = $beneficiary->getHousehold();
                    foreach ($household->getBeneficiaries() as $beneficiaryHH) {
                        if ($beneficiary !== $beneficiaryHH) {
                            $household->removeBeneficiary($beneficiaryHH);
                        }
                    }
                    break;
                default:
                    throw new \Exception('The type of the distribution is unknown.');
            }
            $receivers[] = $household;
        }

        return $receivers;
    }

    /**
     * Defined the reader and transform CSV to array and check the difference between the database and the CSV.
     *
     * @param $countryIso3
     * @param $beneficiaries
     * @param DistributionData $distributionData
     * @param UploadedFile     $uploadedFile
     *
     * @return array
     *
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function parseCSV($countryIso3, $beneficiaries, DistributionData $distributionData, UploadedFile $uploadedFile)
    {
        $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
        $sheetArray = $spreadsheet->getSheet(0)->toArray();
        $headers = $sheetArray[0];
        array_shift($sheetArray);
        $arrayWithKeys = array();

        for ($j = 0; $j < count($sheetArray); $j++)
            for ($i = 0; $i < count($headers); $i++)
                $arrayWithKeys[$j][$headers[$i]] = $sheetArray[$j][$i];

        // Beneficiaries that are both in the file and the distribution, data will be updated
        $updateArray = array();
        // Beneficiaries that are in the distribution but not in the file
        $deleteArray = array();
        foreach($beneficiaries as $beneficiary) {
            $inFile = false;
            foreach($arrayWithKeys as $arrayBeneficiary) {
                if ($beneficiary->getGivenName() === $arrayBeneficiary['givenName']
                    && ($beneficiary->getFamilyName() === $arrayBeneficiary['familyName']
                        || $beneficiary->getFamilyName() === "")) {
                    $arrayBeneficiary['id'] = $beneficiary->getId();

                    array_push($updateArray, $arrayBeneficiary);
                    $inFile = true;
                }
            }
            if (! $inFile) {
                array_push($deleteArray, $beneficiary);
            }
        }

        $finalDeleteArray = array();
        for ($i = 0; $i < count($deleteArray); $i++){
            $finalDeleteArray[$i]['id'] = $deleteArray[$i]->getId();
            $finalDeleteArray[$i]['givenName'] = $deleteArray[$i]->getGivenName();
            $finalDeleteArray[$i]['familyName'] = $deleteArray[$i]->getFamilyName();
        }

        // Names that are in the file but not in the distribution
        // New beneficiaries in the database or update existing beneficiary
        $newAndAddArray = array_udiff($arrayWithKeys, $updateArray,
            function($array1, $array2) {
                if ($array1['givenName'] === $array2['givenName'] && $array1['familyName'] == $array2['familyName']) {
                    return 0;
                } else if ($array1['givenName'] > $array2['givenName']) {
                    return 1;
                } else {
                    return -1;
                }
            }
        );

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
                array_push($addArray, $beneficiary);
            } else {
                array_push($createArray, $beneficiaryArray);
            }
        }

        $finalAddArray = array();
        for ($i = 0; $i < count($addArray); $i++){
            $finalAddArray[$i]['id'] = $addArray[$i]->getId();
            $finalAddArray[$i]['givenName'] = $addArray[$i]->getGivenName();
            $finalAddArray[$i]['familyName'] = $addArray[$i]->getFamilyName();
        }

        $allArray = array(
            'created' => $createArray,
            'added'   => $finalAddArray,
            'deleted' => $finalDeleteArray,
            'updated' => $updateArray
        );

        return $allArray;
    }

    /**
     * Recover the array of the CSV and save the data to the dataBase.
     *
     * @param string $countryIso3
     * @param array $beneficiaries
     * @param DistributionData $distributionData
     * @param array     data
     *
     * @return array
     *
     * @throws \Exception
     */
    public function saveCSV(string $countryIso3, array $beneficiaries, DistributionData $distributionData, array $data)
    {
        $distributionProject = $distributionData->getProject();
        
        // Create
        foreach ($data['created'] as $beneficiaryToCreate) {
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
                        "status" => $beneficiaryToCreate['status'],
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
                ->findOneBy(["household" => $householdToCreate]);
            $this->em->persist($toCreate);
            
            // Add created beneficiary to distribution
            $newDistributionBeneficiary = new DistributionBeneficiary();
            $newDistributionBeneficiary->setBeneficiary($toCreate);
            $newDistributionBeneficiary->setDistributionData($distributionData);
            $this->em->persist($newDistributionBeneficiary);
        }
        
        // Add
        foreach ($data['added'] as $beneficiaryToAdd) {
            $beneficiaryToAdd = $this->em->getRepository(Beneficiary::class)->find($beneficiaryToAdd["id"]);
            dump($beneficiaryToAdd);
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
                        'beneficiary' => $beneficiary->getId(),
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
