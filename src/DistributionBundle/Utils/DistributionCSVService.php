<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\HouseholdConstraints;
use BeneficiaryBundle\Utils\Mapper\HouseholdToCSVMapper;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
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

class DistributionCSVService
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var ContainerInterface $container */
    private $container;
    /** @var HouseholdToCSVMapper $householdToCSVMapper */
    private $householdToCSVMapper;
    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /**
     * DistributionCSVService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param HouseholdToCSVMapper $householdToCSVMapper
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param RequestValidator $requestValidator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        HouseholdToCSVMapper $householdToCSVMapper,
        Serializer $serializer,
        ValidatorInterface $validator,
        RequestValidator $requestValidator
    ) {
        $this->em = $entityManager;
        $this->container = $container;
        $this->householdToCSVMapper = $householdToCSVMapper;
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
        $reader->setDelimiter(',');
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
        // If it's the first step, we transform CSV to array mapped for corresponding to the entity DistributionData
        // LOADING CSV
        dump($uploadedFile->getClientOriginalExtension());
        if($uploadedFile->getClientOriginalExtension() == "csv"){
            $reader = new CsvReader();
            $reader->setDelimiter(',');
        }
        else if($uploadedFile->getClientOriginalExtension() == "xls") {
            $reader = new XlsReader();
        }
        else if($uploadedFile->getClientOriginalExtension() == "ods") {
            $reader = new OdsReader();
        }
        else{
            return ["Error with the extension of the file imported"];
        }

        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();
        $sheetArray = $worksheet->toArray(null, true, true, true);

        // Recover all the givenName and the familyName in the CSV file :
        $givenNameArray = array_map(function ($item) { return $item['L']; }, ($sheetArray));
        $familyNameArray = array_map(function ($item) { return $item['M']; }, ($sheetArray));

        $nameArray = array();

        // We put the given name and family name from CSV file on a same line instead of in two distinct arrays :
        for ($i = 2; $i <= count($givenNameArray); ++$i) {
            array_push($nameArray, $givenNameArray[$i].' '.$familyNameArray[$i]);
        }

        // Recover all the givenName and the familyName in the Beneficiary entity :
        $entityDatasArray = array_map(function ($item) {
            $tempGivenNameArray = array();
            $tempFamilyNameArray = array();

            array_push($tempGivenNameArray, $item->getGivenName());
            array_push($tempFamilyNameArray, $item->getFamilyName());

            return array($tempGivenNameArray[0], $tempFamilyNameArray[0]);
        }, ($beneficiaries));

        // We store them in two distinct arrays :
        $nameDistributionBeneficiaryEntity = array();
        for ($i = 0; $i < count($entityDatasArray); ++$i) {
            array_push($nameDistributionBeneficiaryEntity, $entityDatasArray[$i][0].' '.$entityDatasArray[$i][1]);
        }

        $beneficiariesInProject = $this->em->getRepository(Beneficiary::class)->getAllOfProject($distributionData->getProject()->getId());

        // Recover all the givenName and the familyName of the beneficiaries in the project :
        $nameBeneficiaryInProjectEntity = array();
        for ($i = 0; $i < count($beneficiariesInProject); ++$i) {
            array_push($nameBeneficiaryInProjectEntity, $beneficiariesInProject[$i]->getGivenName().' '.$beneficiariesInProject[$i]->getFamilyName());
        }

        $errorArray = array();
        $addArray = array();
        $deleteArray = array();
        $presentStoreIdBeneficiaryArray = array();
        $presentStoreCSV = array();

        // We search if the givenName and familyName from the CSV file are in the Beneficiary table :
        for ($i = 2; $i <= count($sheetArray); ++$i) {
            $nameCSV = $sheetArray[$i]['L'].' '.$sheetArray[$i]['M'];

            if (!in_array($nameCSV, $nameDistributionBeneficiaryEntity)) {
                // We check if the beneficiary is present in the project :
                if (!in_array($nameCSV, $nameBeneficiaryInProjectEntity)) {
                    array_push($errorArray, [
                        'given_name' => $sheetArray[$i]['L'],
                        'family_name' => $sheetArray[$i]['M'],
                        'gender' => $sheetArray[$i]['N'],
                        'status' => $sheetArray[$i]['O'],
                        'date_birth' => $sheetArray[$i]['P'],
                        'vul_crit' => $sheetArray[$i]['Q'],
                        'phones' => $sheetArray[$i]['R'],
                        'national_id' => $sheetArray[$i]['S'],
                        ]
                    );
                } else {
                    array_push($addArray, $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $sheetArray[$i]['L'], 'familyName' => $sheetArray[$i]['M']]));
                }
            }
            else{
                array_push($presentStoreIdBeneficiaryArray, $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $sheetArray[$i]['L'], 'familyName' => $sheetArray[$i]['M']]));
                array_push($presentStoreCSV, [
                        'givenName' => $sheetArray[$i]['L'],
                        'familyName' => $sheetArray[$i]['M'],
                        'gender' => $sheetArray[$i]['N'],
                        'status' => $sheetArray[$i]['O'],
                        'dateBirth' => $sheetArray[$i]['P'],
                        'vulCrit' => $sheetArray[$i]['Q'],
                        'phones' => $sheetArray[$i]['R'],
                        'nationalId' => $sheetArray[$i]['S'],
                    ]
                );
            }
        }

        foreach ($beneficiaries as $beneficiary) {
            $nameEntity = $beneficiary->getGivenName().' '.$beneficiary->getFamilyName();

            if (in_array($nameEntity, $nameArray) == false) {
                array_push($deleteArray, $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $beneficiary->getGivenName(), 'familyName' => $beneficiary->getFamilyName()]));
            }
        }

        $allArray = array(
            'errors' => $errorArray,
            'added' => $addArray,
            'deleted' => $deleteArray,
            'presentStoreId' => $presentStoreIdBeneficiaryArray,
            'presentStoreCSV' => $presentStoreCSV,
        );

        return $allArray;
    }

    /**
     * Recover the array of the CSV and save the data to the dataBase.
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
    public function saveCSV($countryIso3, $beneficiaries, DistributionData $distributionData, UploadedFile $uploadedFile)
    {
        $allArray = $this->parseCSV($countryIso3, $beneficiaries, $distributionData, $uploadedFile);
        $distributionBeneficiary = new DistributionBeneficiary();

        $addArray = $allArray['added'];
        $deleteArray = $allArray['deleted'];
        $presentStoreIdBeneficiaryArray = $allArray['presentStoreId'];
        $presentStoreCSV = $allArray['presentStoreCSV'];

        foreach ($addArray as $beneficiary) {
            if($beneficiary != null){
                $distributionBeneficiary->setBeneficiary($beneficiary);
                $distributionBeneficiary->setDistributionData($distributionData);

                $this->em->persist($distributionBeneficiary);
                $this->em->flush();
            }
        }

        foreach ($deleteArray as $value) {
            $db = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['beneficiary' => $value->getId(), 'distributionData' => $distributionData->getId()]);
            $this->em->remove($db[0]);
            $this->em->flush();
        }

        for ($i = 0; $i < count($presentStoreIdBeneficiaryArray); $i++){
            $beneficiaryId = $presentStoreIdBeneficiaryArray[$i]->getId();
            $beneficiaryNewGivenName = $presentStoreCSV[$i]['givenName'];
            $beneficiaryNewFamilyName = $presentStoreCSV[$i]['familyName'];
            $beneficiaryNewGender = $presentStoreCSV[$i]['gender'];
            $beneficiaryNewStatus = $presentStoreCSV[$i]['status'];
            $beneficiaryNewDateBirth = $presentStoreCSV[$i]['dateBirth'];
            $beneficiaryNewVulCrit = $presentStoreCSV[$i]['vulCrit'];
            $beneficiaryNewPhones = $presentStoreCSV[$i]['phones'];
            $beneficiaryNewNatId = $presentStoreCSV[$i]['nationalId'];

            $editedBeneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryId);

            $editedBeneficiary->setVulnerabilityCriteria(null);
            $items = $this->em->getRepository(Phone::class)->findByBeneficiary($editedBeneficiary);
            foreach ($items as $item)
            {
                $this->em->remove($item);
            }
            $items = $this->em->getRepository(NationalId::class)->findByBeneficiary($editedBeneficiary);
            foreach ($items as $item)
            {
                $this->em->remove($item);
            }

            $this->em->flush();


            $editedBeneficiary->setGender($beneficiaryNewGender)
                ->setDateOfBirth(new \DateTime($beneficiaryNewDateBirth))
                ->setFamilyName($beneficiaryNewFamilyName)
                ->setGivenName($beneficiaryNewGivenName)
                ->setStatus($beneficiaryNewStatus);

            $errors = $this->validator->validate($editedBeneficiary);
            if (count($errors) > 0)
            {
                $errorsArray = [];
                foreach ($errors as $error)
                {
                    $errorsArray[] = $error->getMessage();
                }
                throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
            }

            $editedBeneficiary->addVulnerabilityCriterion($this->getVulnerabilityCriterion($beneficiaryNewVulCrit));

            if($beneficiaryNewPhones)
                $this->getOrSavePhone($editedBeneficiary, $beneficiaryNewPhones);

            if($beneficiaryNewNatId)
                $this->getOrSaveNationalId($editedBeneficiary, $beneficiaryNewNatId);

            $this->em->merge($editedBeneficiary);
            $this->em->flush();
        }

        return array(
            'result' => 'Elements added / suppressed / modified',
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

    /**
     * @param Beneficiary $beneficiary
     * @param string $phoneNumber
     * @return Phone|null|object
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function getOrSavePhone(Beneficiary $beneficiary, string $phoneNumber)
    {
        $this->requestValidator->validate(
            "phone",
            HouseholdConstraints::class,
            $phoneNumber,
            'any'
        );
        $phone = $this->em->getRepository(Phone::class)->findOneBy(['beneficiary' => $beneficiary->getId()]);
        $phone->setNumber($phoneNumber);

        $this->em->merge($phone);
        $this->em->flush();

        return $phone;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param string $nationalIdString
     * @return NationalId|null|object
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function getOrSaveNationalId(Beneficiary $beneficiary, string $nationalIdString)
    {
        $this->requestValidator->validate(
            "nationalId",
            HouseholdConstraints::class,
            $nationalIdString,
            'any'
        );
        $nationalId = $this->em->getRepository(NationalId::class)->findOneBy(['beneficiary' => $beneficiary->getId()]);
        $nationalId->setIdNumber($nationalIdString);

        $this->em->merge($nationalId);
        $this->em->flush();

        return $nationalId;
    }
}
