<?php

namespace ReportingBundle\Utils\Computers;

use ReportingBundle\Utils\Computers\ComputerInterface;
use ReportingBundle\Utils\Model\IndicatorInterface;

use ReportingBundle\Utils\DataRetrievers\CountryDataRetriever;
use ReportingBundle\Utils\DataRetrievers\ProjectDataRetriever;
use ReportingBundle\Utils\DataRetrievers\AssistanceRetriever;

use Doctrine\ORM\EntityManager;

/**
 * Class Computer
 * @package ReportingBundle\Utils\Computers
 */
class Computer implements ComputerInterface
{

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var ProjectDataRetriever
     */
    private $project;


    /**
     * Computer constructor.
     * @param EntityManager $em
     * @param ProjectDataRetriever $project
     */
    public function __construct(EntityManager $em, ProjectDataRetriever $project)
    {
        $this->em = $em;
        $this->project = $project;
    }

    /**
     * Search in all data retrievers if the code exists
     * Call the good function after find it
     * @param IndicatorInterface $indicator
     * @param array $filters
     * @return mixed
     */
    public function compute(IndicatorInterface $indicator, array $filters = [])
    {
        $filters['__'] = [
                    'functionName' => $indicator->getCode()
        ];

        if (preg_match("#^BMS_C#", $indicator->getCode())) {
            if (is_callable(array(new CountryDataRetriever($this->em), $indicator->getCode()))) {

                return call_user_func_array([new CountryDataRetriever($this->em), $indicator->getCode()], [$filters]);
            }
        }

        if (preg_match("#^BMS_P#", $indicator->getCode())) {
            if (is_callable(array(new ProjectDataRetriever($this->em), $indicator->getCode()))) {
                return call_user_func_array([new ProjectDataRetriever($this->em), $indicator->getCode()], [$filters]);
            }
        }

        if (preg_match("#^BMS_D#", $indicator->getCode())) {
            if (is_callable(array(new AssistanceRetriever($this->em, $this->project), $indicator->getCode()))) {
                return call_user_func_array([new AssistanceRetriever($this->em, $this->project), $indicator->getCode()], [$filters]);
            }
        }
    }
}
