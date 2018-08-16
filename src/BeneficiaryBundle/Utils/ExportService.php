<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\DependencyInjection\ContainerInterface;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;



Class ExportService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var Beneficiary $beneficiary */
    private $beneficiary;

    /**
     * ExportService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->beneficiary = new Beneficiary();
    }

    public function export($exportableTable) {


        dump("ceci une variable exportabletable",$exportableTable);


        $rows = [];

        // step 1 : convertir le mapping en données

        foreach ($exportableTable as $value) {
            if( $value instanceof ExportableInterface) {
                dump("ceci est la valeur", $value);
                array_push($rows, $value->getMappedValueForExport());
                dump($rows);
            }
        }

        // step 2 : sheet construction

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $worksheet = $spreadsheet->getActiveSheet();

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

        dump($fileContent);
        return $writer;


    }

















}