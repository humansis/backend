<?php

namespace ReportingBundle\Utils\DataFillers;

use Doctrine\ORM\EntityManager;
use ReportingBundle\Entity\ReportingIndicator;

/**
 * Class DataFillersIndicator
 * @package ReportingBundle\Utils\DataFillers
 */
class DataFillersIndicator
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * DataFillersIndicator constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Open and parse csv before return it
     * @param string $csvFile
     * @return array
     */
    public function getCsv(string $csvFile)
    {
        //get the content of csv
        $file = file_get_contents($csvFile);
        //format content in array and return it
        return  array_map("str_getcsv", preg_split('/\r*\n+|\r+/', $file));
    }

    /**
     * Call function to parse CSV
     * And add data in the database
     */
    public function fillIndicator()
    {
        $filename = "src/ReportingBundle/Resources/data/CSV/reportingReference.csv";
        $contentFile = $this->getCsv($filename);
        foreach ($contentFile as $data) {
            $new = new ReportingIndicator();
            $filter = [];
            $new->setreference($data[0]);
            $new->setGraph($data[3]);
            $new->setCode($data[1]);
            array_push($filter, $data[2]);
            
            if (is_array($filter)) {
                if (!empty($filter)) {
                    $new->setFilters($filter);
                }
            }

            $this->em->persist($new);
            $this->em->flush();
        }
    }
}
