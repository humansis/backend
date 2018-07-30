<?php


namespace DistributionBundle\Utils;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\Mapper\HouseholdToCSVMapper;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
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

    public function __construct(
        EntityManagerInterface $entityManager,
        ExportCSVService $exportCSVService,
        ContainerInterface $container,
        HouseholdToCSVMapper $householdToCSVMapper
    )
    {
        $this->em = $entityManager;
        $this->exportCSVService = $exportCSVService;
        $this->container = $container;
        $this->householdToCSVMapper = $householdToCSVMapper;
    }

    /**
     * @param $countryISO3
     * @param DistributionData $distributionData
     * @return array
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($countryISO3, DistributionData $distributionData)
    {
        $spreadsheet = $this->exportCSVService->buildFile($countryISO3);
        $spreadsheet = $this->buildFile($countryISO3, $spreadsheet, $distributionData);

        $writer = new CsvWriter($spreadsheet);

        $dataPath = $this->container->getParameter('kernel.root_dir') . '/../var';
        $filename = $dataPath . '/pattern_household_' . $countryISO3 . '.csv';
        $writer->save($filename);

        //Récupération du contenu et suppression du fichier
        $fileContent = file_get_contents($filename);
        unlink($filename);
        $file = [$fileContent, 'export_distribution_' . $distributionData->getName() . '.csv'];
        return $file;
    }

    /**
     * @param DistributionData $distributionData
     * @param UploadedFile $uploadedFile
     * @return bool
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import(DistributionData $distributionData, UploadedFile $uploadedFile)
    {
        $reader = new CsvReader();
        $reader->setDelimiter(",");
        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();
        $sheetArray = $worksheet->toArray(null, true, true, true);
        $index = 1;
        $columnIdSync = null;
        while ($index < Household::firstRow)
        {
            if ($index === Household::indexRowHeader)
                $columnIdSync = $this->findColumnId($sheetArray[$index]);
            unset($sheetArray[$index]);
            $index++;
        }
        $this->analyzeArray($distributionData, $sheetArray, $columnIdSync);

        return true;
    }

    /**
     * @param DistributionData $distributionData
     * @param array $receiversArray
     * @param $columnIdSync
     * @throws \Exception
     */
    public function analyzeArray(DistributionData $distributionData, array $receiversArray, $columnIdSync)
    {
        /** @var DistributionData $distributionData */
        $distributionData = $this->em->getRepository(DistributionData::class)->find($distributionData);
        /** @var DistributionBeneficiary $distributionBeneficiary */
        foreach ($distributionData->getDistributionBeneficiaries() as $distributionBeneficiary)
        {
            $isFound = false;
            if (0 === $distributionData->getType())
                $idFromDatabase = $distributionBeneficiary->getBeneficiary()->getHousehold()->getId();
            elseif (1 === $distributionData->getType())
                $idFromDatabase = $distributionBeneficiary->getBeneficiary()->getId();
            else
                throw new
                \Exception("Error system : This type of distribution '{$distributionData->getType()}' is undefined.");
            foreach ($receiversArray as $index => $receiver)
            {
                if (intval($receiver[$columnIdSync]) === $idFromDatabase)
                {
                    $isFound = true;
                    continue;
                }
            }
            if (!$isFound)
            {
                $this->em->remove($distributionBeneficiary);
            }
        }
        $this->em->flush();
    }

    /**
     * @param array $headerArray
     * @return int|string
     * @throws \Exception
     */
    public function findColumnId(array $headerArray)
    {
        if (empty($headerArray))
            throw new \Exception("Your CSV is malformed.");

        $nameHeaderSync = DistributionData::NAME_HEADER_ID;
        foreach ($headerArray as $columnCSV => $nameHeader)
        {
            if ($nameHeaderSync === $nameHeader)
                return $columnCSV;
        }

        throw new \Exception("Your file is malformed. The column '$nameHeader' was not found.");
    }

    /**
     * @param $countryISO3
     * @param Spreadsheet $spreadsheet
     * @param DistributionData $distributionData
     * @return Spreadsheet
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
     * If the distribution is for beneficiary, households will contain only one beneficiary
     * @param DistributionData $distributionData
     * @return array
     * @throws \Exception
     */
    public function buildDataBeneficiary(DistributionData $distributionData)
    {
        $distributionBeneficiaries = $distributionData->getDistributionBeneficiaries();
        $receivers = [];
        foreach ($distributionBeneficiaries as $distributionBeneficiary)
        {
            /** @var Beneficiary $beneficiary */
            $beneficiary = $distributionBeneficiary->getBeneficiary();

            switch ($distributionData->getType())
            {
                case 0:
                    $household = $beneficiary->getHousehold();
                    foreach ($this->em->getRepository(Beneficiary::class)->findByHousehold($household) as $beneficiaryHH)
                    {
                        $household->addBeneficiary($beneficiaryHH);
                    }
                    break;

                case 1:
                    $household = $beneficiary->getHousehold();
                    foreach ($household->getBeneficiaries() as $beneficiaryHH)
                    {
                        if ($beneficiary !== $beneficiaryHH)
                            $household->removeBeneficiary($beneficiaryHH);
                    }
                    break;
                default:
                    throw new \Exception("The type of the distribution is unknown.");
            }
            $receivers[] = $household;
        }

        return $receivers;
    }

}