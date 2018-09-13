<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\ExportCSVService;
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
    /** @var Serializer $serializer */
    private $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        ExportCSVService $exportCSVService,
        ContainerInterface $container,
        HouseholdToCSVMapper $householdToCSVMapper,
        Serializer $serializer
    ) {
        $this->em = $entityManager;
        $this->exportCSVService = $exportCSVService;
        $this->container = $container;
        $this->householdToCSVMapper = $householdToCSVMapper;
        $this->serializer = $serializer;
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
        $reader = new CsvReader();
        $reader->setDelimiter(',');
        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();
        $sheetArray = $worksheet->toArray(null, true, true, true);

        // Recover all the givenName and the familyName in the CSV file :
        $givenNameArray = array_map(function ($item) { return $item['L']; }, ($sheetArray));
        $familyNameArray = array_map(function ($item) { return $item['M']; }, ($sheetArray));

        $nameArray = array();

        for($i = 2; $i <= count($givenNameArray); $i++){
            array_push($nameArray, $givenNameArray[$i] . " " . $familyNameArray[$i]);
        }

        // Recover all the givenName and the familyName in the Beneficiary entity :
        $entityDatasArray = array_map(function ($item) {
            $tempGivenNameArray = array();
            $tempFamilyNameArray = array();

            array_push($tempGivenNameArray, $item->getGivenName());
            array_push($tempFamilyNameArray, $item->getFamilyName());

            return array($tempGivenNameArray[0], $tempFamilyNameArray[0]);
        }, ($beneficiaries));

        $givenNameEntityArray = array();
        $familyNameEntityArray = array();
        for ($i = 0; $i < count($entityDatasArray); ++$i) {
            array_push($givenNameEntityArray, $entityDatasArray[$i][0]);
            array_push($familyNameEntityArray, $entityDatasArray[$i][1]);
        }

        $beneficiariesInProject = $this->em->getRepository(Beneficiary::class)->getAllOfProject($distributionData->getProject()->getId());

        // Recover all the givenName and the familyName of the beneficiaries in the project :
        $givenNameBeneficiariesArray = array();
        $familyNameBeneficiariesArray = array();
        for ($i = 0; $i < count($beneficiariesInProject); ++$i) {

            array_push($givenNameBeneficiariesArray, $beneficiariesInProject[$i]->getGivenName());
            array_push($familyNameBeneficiariesArray, $beneficiariesInProject[$i]->getFamilyName());
        }

        $errorArray = array();
        $addArray = array();
        $deleteArray = array();

        for ($i = 2; $i <= count($sheetArray); ++$i) {
            $givenName = $sheetArray[$i]['L'];
            $familyName = $sheetArray[$i]['M'];

            if (!in_array($givenName, $givenNameEntityArray) || !in_array($familyName, $familyNameEntityArray)) {
                if (!in_array($givenName, $givenNameBeneficiariesArray) && !in_array($familyName, $familyNameBeneficiariesArray)) {
                    array_push($errorArray, [
                        'given name' => $sheetArray[$i]['L'],
                        'family name' => $sheetArray[$i]['M'],
                        'gender' => $sheetArray[$i]['N'],
                        'status' => $sheetArray[$i]['O'],
                        'date of birth' => $sheetArray[$i]['P'],
                        'vulnerability criteria' => $sheetArray[$i]['Q'],
                        'phones' => $sheetArray[$i]['R'],
                        'national IDs' => $sheetArray[$i]['S'],
                        'updated_on' => '',
                        'profile' => '',
                        ]
                    );
                } else {
                    array_push($addArray, [
                        $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $givenName, 'familyName' => $familyName]),
                        ]
                    );
                }
            }
        }

        foreach ($beneficiaries as $beneficiary) {
            $nameEntity = $beneficiary->getGivenName() . " " . $beneficiary->getFamilyName();

            if (in_array($nameEntity, $nameArray) == false) {
                array_push($deleteArray, [
                    $this->em->getRepository(Beneficiary::class)->findOneBy(['givenName' => $beneficiary->getGivenName(), 'familyName' => $beneficiary->getFamilyName()]),
                    ]
                );
            }
        }

        $allArray = array(
            'errors' => $errorArray,
            'added' => $addArray,
            'deleted' => $deleteArray
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

        $addArray = $allArray["added"];
        $deleteArray = $allArray["deleted"];

        foreach ($addArray as $beneficiary){
            $distributionBeneficiary->setBeneficiary($beneficiary[0]);
            $distributionBeneficiary->setDistributionData($distributionData);

            $this->em->persist($distributionBeneficiary);
            $this->em->flush();
        }

        foreach ($deleteArray as $value){
            $db = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['beneficiary' => $value[0]->getId(), 'distributionData' => $distributionData->getId()]);
            $this->em->remove($db[0]);
            $this->em->flush();
        }

        return array(
            'result' => "Elements added / suppressed"
        );
    }
}
