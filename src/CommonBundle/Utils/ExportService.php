<?php

namespace CommonBundle\Utils;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\DependencyInjection\ContainerInterface;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use Symfony\Component\HttpFoundation\Response;


Class ExportService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * ExportService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function export($exportableTable, $name)
    {
        $rows = [];

        // step 1 : convertir le mapping en données

        foreach ($exportableTable as $value) {
            if(is_object($value)) {
                if( $value instanceof ExportableInterface) {
                    array_push($rows, $value->getMappedValueForExport());
                }
            } else if(is_array($value)) {
                array_push($rows, $value);
            } else {
                throw new \Exception("The table to export contains a not allowed content ($value). Allowed content: array, ".ExportableInterface::class."");
            }
        }


        // step 2 : sheet construction

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $worksheet = $spreadsheet->getActiveSheet();

        if(count($rows) === 0) {
            throw new \Exception("No data to export", Response::HTTP_NO_CONTENT);
        }
        // get headers title

        $headers = array_keys($rows[0]);


        $rowIndex = 1;

        foreach ($headers as $key => $value) {
            $index = chr(ord('A')+ $key).$rowIndex;

            $worksheet->setCellValue($index, $value);
        }

        $rowIndex = 2;

        foreach ($rows as $key => $value) {

           foreach ($headers as $colIndex => $header) {
               $index = chr(ord('A')+ $colIndex ).$rowIndex;
               $worksheet->setCellValue($index, $value[$header]);
           }
            $rowIndex++;
        }

        // step 3 : scaning sheet into csv

        $writer = new Csv($spreadsheet);

        $dataPath = $this->container->getParameter('kernel.root_dir') . '/../var';
        $filename = $dataPath . '/test.csv';

        $writer->save($filename);
        $fileContent = file_get_contents($filename);

        unlink($filename);

        return [$fileContent,'' . $name. '.csv'];
    }

















}