<?php


namespace DistributionBundle\Utils;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\Mapper;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DistributionCSVService
{
    /** @var EntityManagerInterface $em */
    private $em;
    /** @var ExportCSVService $exportCSVService */
    private $exportCSVService;
    /** @var ContainerInterface $container */
    private $container;
    /** @var Mapper $mapper */
    private $mapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        ExportCSVService $exportCSVService,
        ContainerInterface $container,
        Mapper $mapper
    )
    {
        $this->em = $entityManager;
        $this->exportCSVService = $exportCSVService;
        $this->container = $container;
        $this->mapper = $mapper;
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

        $writer = new Csv($spreadsheet);

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

        $this->mapper->fromHouseholdToCSV($worksheet, $receivers, $countryISO3);

        dump($worksheet->toArray(true, null, null, null));
        return $spreadsheet;
    }

    /**
     * Return an array with households.
     * If the distribution is for beneficiary, households will contain only one beneficiary
     * @param DistributionData $distributionData
     * @return array
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
                    $receivers[] = $beneficiary->getHousehold();
                    break;

                case 1:
                    $household = $beneficiary->getHousehold();
                    foreach ($household->getBeneficiaries() as $beneficiaryHH)
                    {
                        if ($beneficiary !== $beneficiaryHH)
                            $household->removeBeneficiary($beneficiaryHH);
                    }
                    $receivers[] = $household;
                    break;
            }
        }

        return $receivers;
    }

}