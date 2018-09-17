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

    /** @var array $headers An array that follows the csv format*/
    private $headers;

    /** @var string $filecontent*/
    private $filecontent;

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

    /**
     * @param $exportableTable
     * @param $name
     * @param $type
     * @return array|string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($exportableTable, $name, $type)
    {
        $rows = [];

        // step 1 : Convert the mapping as data

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

        $rowIndex = 1;

        // write headers
        if(is_array($this->headers)) {
            foreach ($this->headers as $key => $value) {

                foreach ($value as $colIndex => $header) {
                    $index = chr(ord('A')+ $colIndex ).$rowIndex;
                    $worksheet->setCellValue($index, $value[$colIndex]);
                }
                $rowIndex++;
            }
        }

        // get table headers titles
        reset($rows);
        $tableHeaders = array_keys($rows[0]);

        foreach ($tableHeaders as $key => $value) {
            $index = chr(ord('A')+ $key).$rowIndex;

            $worksheet->setCellValue($index, $value);
        }

        $rowIndex++;

        foreach ($rows as $key => $value) {

           foreach ($tableHeaders as $colIndex => $header) {
               $index = chr(ord('A')+ $colIndex ).$rowIndex;
               $worksheet->setCellValue($index, $value[$header]);
           }

           $rowIndex++;
        }

        // step 3 : scaning sheet into csv or excel

        $writer = new Csv($spreadsheet);
        $writer->setEnclosure('');

        $dataPath = $this->container->getParameter('kernel.root_dir') . '/../var';
        if($type == "csv"){
            $filename = $dataPath . '/'.$name.'.csv';
        }
        elseif($type == "excel"){
            $filename = $dataPath . '/'.$name.'.xls';
        }
        elseif($type == "ods"){
            $filename = $dataPath . '/'.$name.'.ods';
        }
        else{
            return "An error occured with the type file";
        }

        $writer->save($filename);
        $this->filecontent = file_get_contents($filename);

        unlink($filename);

        return [
            'content' => $this->filecontent,
            'filename' => $name,
            'filepath' => $filename
        ];
    }

    /**
     * @param array $headers This array should follow the csv format
     * @return ExportService
     */
    public function setHeaders(array $headers) {
        $this->headers = $headers;

        return $this;
    }


}